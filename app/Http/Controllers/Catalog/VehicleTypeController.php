<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleType::withCount('vehicleModels');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $types = $query->latest()->paginate(15)->withQueryString();
        return view('catalog.vehicle-types.index', compact('types'));
    }

    public function create()
    {
        return view('catalog.vehicle-types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:vehicle_types,name',
            'wheel_count' => 'required|integer|in:2,3',
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:active,inactive',
        ]);

        $data['slug'] = Str::slug($data['name']);

        VehicleType::create($data);

        return redirect()->route('catalog.vehicle-types.index')
            ->with('success', 'Vehicle type created successfully.');
    }

    public function show(VehicleType $vehicleType)
    {
        $vehicleType->load('vehicleModels.stock');
        return view('catalog.vehicle-types.show', compact('vehicleType'));
    }

    public function edit(VehicleType $vehicleType)
    {
        return view('catalog.vehicle-types.edit', compact('vehicleType'));
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:vehicle_types,name,' . $vehicleType->id,
            'wheel_count' => 'required|integer|in:2,3',
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:active,inactive',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $vehicleType->update($data);

        return redirect()->route('catalog.vehicle-types.index')
            ->with('success', 'Vehicle type updated successfully.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        if ($vehicleType->vehicleModels()->exists()) {
            return back()->with('error', 'Cannot delete: this type has vehicle models attached. Remove models first.');
        }

        $vehicleType->delete();
        return redirect()->route('catalog.vehicle-types.index')
            ->with('success', 'Vehicle type deleted.');
    }
}
