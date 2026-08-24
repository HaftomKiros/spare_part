<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PartCategory::withCount('spareParts')
            ->with('parent');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $categories = $query->latest()->paginate(15)->withQueryString();
        return view('catalog.part-categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = PartCategory::active()->rootCategories()->orderBy('name')->get();
        return view('catalog.part-categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'parent_id'   => 'nullable|exists:part_categories,id',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:100',
            'status'      => 'required|in:active,inactive',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . uniqid();

        PartCategory::create($data);

        return redirect()->route('catalog.part-categories.index')
            ->with('success', 'Part category created successfully.');
    }

    public function edit(PartCategory $partCategory)
    {
        $parents = PartCategory::active()->rootCategories()
            ->where('id', '!=', $partCategory->id)
            ->orderBy('name')->get();
        return view('catalog.part-categories.edit', compact('partCategory', 'parents'));
    }

    public function update(Request $request, PartCategory $partCategory)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'parent_id'   => 'nullable|exists:part_categories,id',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:100',
            'status'      => 'required|in:active,inactive',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . $partCategory->id;
        $partCategory->update($data);

        return redirect()->route('catalog.part-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(PartCategory $partCategory)
    {
        if ($partCategory->spareParts()->exists()) {
            return back()->with('error', 'Cannot delete: category has spare parts. Reassign them first.');
        }
        if ($partCategory->children()->exists()) {
            return back()->with('error', 'Cannot delete: category has sub-categories. Remove them first.');
        }

        $partCategory->delete();
        return redirect()->route('catalog.part-categories.index')
            ->with('success', 'Category deleted.');
    }
}
