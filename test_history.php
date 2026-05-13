<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$election = \App\Models\Election::latest()->first();
$votes = $election->votes()->with('candidate')->get();
$history = $votes->map(function ($vote) {
    return [
        'voterId' => $vote->voter_id,
        'candidateId' => $vote->candidate_id,
        'candidateName' => $vote->candidate->name ?? 'Unknown',
        'time' => $vote->created_at->toIso8601String()
    ];
});
echo json_encode($history);
