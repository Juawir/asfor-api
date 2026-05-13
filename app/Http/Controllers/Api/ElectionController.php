<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionController extends Controller
{
    // Get the current election (latest active or latest completed)
    public function current(Request $request)
    {
        $election = Election::with(['candidates.user'])->latest()->first();

        if (!$election) {
            return response()->json(null);
        }

        // Auto-cleanup: jika election aktif tidak punya kandidat (corrupt), hapus dan kembalikan null
        if ($election->candidates->isEmpty() && $election->status === 'active') {
            \Log::info('Auto-deleting corrupt election id=' . $election->id . ' (0 candidates)');
            $election->delete();
            return response()->json(null);
        }

        $voterIds = $election->votes()->pluck('voter_id')->toArray();
        $history = $election->votes()->with('candidate')->get()->map(function ($vote) {
            return [
                'voterId' => (string) $vote->voter_id,
                'candidateId' => (string) $vote->candidate_id,
                'candidateName' => $vote->candidate->name ?? 'Unknown',
                'time' => $vote->created_at->toIso8601String()
            ];
        });

        // Format the response to match Flutter's expectations
        $candidates = $election->candidates->map(function ($candidate) {
            // Guard: user may have been deleted
            return [
                'userId' => (string) $candidate->user_id,
                'name' => $candidate->user->name ?? 'Unknown',
                'division' => $candidate->user->division ?? '',
                'visiMisi' => $candidate->visi_misi ?? '',
                'votes' => $candidate->votes()->count()
            ];
        });

        return response()->json([
            'id' => (string) $election->id,
            'title' => $election->title,
            'status' => $election->status,
            'candidates' => $candidates,
            'voterIds' => array_map('strval', $voterIds),
            'history' => $history,
            'createdAt' => $election->created_at->toIso8601String()
        ]);
    }

    // Create a new election
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'candidates' => 'required|array|min:2',
            'candidates.*.userId' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            // Check if there's an active election, end it
            Election::where('status', 'active')->update(['status' => 'completed']);

            $election = Election::create([
                'title' => $request->title,
                'status' => 'active'
            ]);

            foreach ($request->candidates as $c) {
                ElectionCandidate::create([
                    'election_id' => $election->id,
                    'user_id' => $c['userId']
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Election created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Election Creation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create election: ' . $e->getMessage()], 500);
        }
    }

    // Cast a vote
    public function vote(Request $request, $id)
    {
        $election = Election::findOrFail($id);

        if ($election->status !== 'active') {
            return response()->json(['message' => 'Pemilihan sudah berakhir'], 400);
        }

        $request->validate([
            'candidateId' => 'required|exists:users,id'
        ]);

        $userId = $request->user()->id;

        if (ElectionVote::where('election_id', $election->id)->where('voter_id', $userId)->exists()) {
            return response()->json(['message' => 'Anda sudah memberikan suara'], 400);
        }

        // Verify candidate belongs to this election
        $candidate = ElectionCandidate::where('election_id', $election->id)->where('user_id', $request->candidateId)->first();
        if (!$candidate) {
            return response()->json(['message' => 'Kandidat tidak ditemukan'], 400);
        }

        ElectionVote::create([
            'election_id' => $election->id,
            'candidate_id' => $request->candidateId, // This points to user_id of the candidate
            'voter_id' => $userId
        ]);

        return response()->json(['message' => 'Vote cast successfully']);
    }

    // End election
    public function end($id)
    {
        if (!request()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $election = Election::findOrFail($id);
        $election->update(['status' => 'completed']);

        return response()->json(['message' => 'Election ended']);
    }

    // Delete election
    public function destroy($id)
    {
        if (!request()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $election = Election::findOrFail($id);
        $election->delete();

        return response()->json(['message' => 'Election deleted']);
    }
}
