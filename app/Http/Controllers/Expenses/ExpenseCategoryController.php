<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->paginate(20);
        return view('expenses.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:255',
        ]);
        $data['is_active'] = true;
        ExpenseCategory::create($data);

        return back()->with('success', "Category '{$data['name']}' created.");
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $expenseCategory->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete: category has expenses. Reassign them first.');
        }
        $expenseCategory->delete();
        return back()->with('success', 'Category deleted.');
    }
}
