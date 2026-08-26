<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = Expense::with('category', 'user', 'warehouse')
            ->where(function ($q) use ($accessibleIds) {
                // Show warehouse-specific expenses for accessible warehouses
                // OR company-wide expenses (warehouse_id = null)
                $q->whereIn('warehouse_id', $accessibleIds)
                  ->orWhereNull('warehouse_id');
            });

        // Non-managers only see their own expenses
        if (! $user->seesAllUsers()) {
            $query->where('user_id', $user->id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('expense_number', 'like', "%{$request->search}%")
                  ->orWhere('reference_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->category_id) {
            $query->where('expense_category_id', $request->category_id);
        }
        if ($request->date_from) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses   = $query->latest('expense_date')->paginate(20)->withQueryString();
        $totalAmount = (clone $query)->sum('amount');
        $categories  = ExpenseCategory::active()->orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'totalAmount', 'categories'));
    }

    public function create()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $warehouses = auth()->user()->accessibleWarehouses()->get();
        $number     = Expense::generateNumber();

        return view('expenses.create', compact('categories', 'warehouses', 'number'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'warehouse_id'        => 'nullable|exists:warehouses,id',
            'title'               => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'expense_date'        => 'required|date',
            'payment_method'      => 'required|in:cash,bank_transfer,cheque',
            'reference_number'    => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        // Enforce: non-admins can only assign to their accessible warehouses
        if ($data['warehouse_id']) {
            $accessibleIds = auth()->user()->accessibleWarehouseIds();
            if (! in_array($data['warehouse_id'], $accessibleIds)) {
                $data['warehouse_id'] = null;
            }
        }

        $data['expense_number'] = Expense::generateNumber();
        $data['user_id']        = auth()->id();

        Expense::create($data);

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$data['title']}' recorded successfully.");
    }

    public function show(Expense $expense)
    {
        $expense->load('category', 'user', 'warehouse');
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $warehouses = auth()->user()->accessibleWarehouses()->get();

        return view('expenses.edit', compact('expense', 'categories', 'warehouses'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'warehouse_id'        => 'nullable|exists:warehouses,id',
            'title'               => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'expense_date'        => 'required|date',
            'payment_method'      => 'required|in:cash,bank_transfer,cheque',
            'reference_number'    => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        if ($data['warehouse_id']) {
            $accessibleIds = auth()->user()->accessibleWarehouseIds();
            if (! in_array($data['warehouse_id'], $accessibleIds)) {
                $data['warehouse_id'] = null;
            }
        }

        $expense->update($data);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted.');
    }
}
