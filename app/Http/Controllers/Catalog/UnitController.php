<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::withCount('spareParts');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('abbreviation', 'like', "%{$request->search}%");
        }

        $units = $query->latest()->paginate(15)->withQueryString();
        return view('catalog.units.index', compact('units'));
    }

    public function create()
    {
        return view('catalog.units.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100|unique:units,name',
            'abbreviation' => 'required|string|max:20',
            'description'  => 'nullable|string|max:300',
        ]);

        Unit::create($data);
        return redirect()->route('catalog.units.index')
            ->with('success', 'Unit of measure created successfully.');
    }

    public function edit(Unit $unit)
    {
        return view('catalog.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100|unique:units,name,' . $unit->id,
            'abbreviation' => 'required|string|max:20',
            'description'  => 'nullable|string|max:300',
        ]);

        $unit->update($data);
        return redirect()->route('catalog.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->spareParts()->exists()) {
            return back()->with('error', 'Cannot delete: unit is used by spare parts.');
        }

        $unit->delete();
        return redirect()->route('catalog.units.index')
            ->with('success', 'Unit deleted.');
    }
}
