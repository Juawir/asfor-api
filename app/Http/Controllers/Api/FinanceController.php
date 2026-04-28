<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Finance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    use ApiResponse;

    private function authorizeFinanceAccess($user)
    {
        if (!$user->isAdmin() && $user->division !== 'Bidang Usaha') {
            abort(403, 'Unauthorized action. Only Admin or Bidang Usaha can un-access finances.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeFinanceAccess($request->user());

        $query = Finance::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('month')) {
            $query->whereMonth('date', $request->month);
        }
        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }
        if ($request->has('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        return $this->successResponse($query->orderBy('date', 'desc')->get(), 'Finances retrieved successfully');
    }

    public function store(Request $request)
    {
        $this->authorizeFinanceAccess($request->user());

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $finance = Finance::create($validated);

        return $this->successResponse($finance, 'Finance created successfully', 201);
    }

    public function show(Request $request, Finance $finance)
    {
        $this->authorizeFinanceAccess($request->user());
        return $this->successResponse($finance, 'Finance retrieved successfully');
    }

    public function update(Request $request, Finance $finance)
    {
        $this->authorizeFinanceAccess($request->user());

        $validated = $request->validate([
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'category' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $finance->update($validated);

        return $this->successResponse($finance, 'Finance updated successfully');
    }

    public function destroy(Request $request, Finance $finance)
    {
        $this->authorizeFinanceAccess($request->user());
        $finance->delete();
        return response()->json(null, 204);
    }

    public function summary(Request $request)
    {
        $this->authorizeFinanceAccess($request->user());

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        $query = Finance::whereYear('date', $year);
        if ($month) {
            $query->whereMonth('date', $month);
        }

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        
        $total = $totalIncome + $totalExpense;
        $incomePercentage = $total > 0 ? round(($totalIncome / $total) * 100, 2) : 0;
        $expensePercentage = $total > 0 ? round(($totalExpense / $total) * 100, 2) : 0;

        $categories = (clone $query)
            ->select('category', 'type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category', 'type')
            ->get();

        return $this->successResponse([
            'year' => $year,
            'month' => $month,
            'summary' => [
                'income' => [
                    'total' => $totalIncome,
                    'percentage' => $incomePercentage
                ],
                'expense' => [
                    'total' => $totalExpense,
                    'percentage' => $expensePercentage
                ],
                'balance' => $totalIncome - $totalExpense
            ],
            'categories' => $categories
        ], 'Finance summary retrieved successfully');
    }
}
