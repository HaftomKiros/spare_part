<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\SparePart;
use App\Models\Unit;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $query = SparePart::with('category', 'unit');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('part_number', 'like', "%{$request->search}%")
                  ->orWhere('oem_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->category) {
            $query->where('part_category_id', $request->category);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->stock_status === 'low') {
            $query->lowStock();
        } elseif ($request->stock_status === 'out') {
            $query->outOfStock();
        }

        $parts      = $query->latest()->paginate(20)->withQueryString();
        $categories = PartCategory::active()->orderBy('name')->get();

        return view('catalog.spare-parts.index', compact('parts', 'categories'));
    }

    public function create()
    {
        $categories = PartCategory::active()->with('children')->rootCategories()->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $vehicles   = VehicleModel::active()->with('vehicleType')->orderBy('model_name')->get();

        // Auto-generate part number
        $lastPart = SparePart::orderBy('id', 'desc')->first();
        $nextNum  = $lastPart ? (int) substr($lastPart->part_number, 3) + 1 : 1;
        $partNumber = 'SP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('catalog.spare-parts.create', compact('categories', 'units', 'vehicles', 'partNumber'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id'          => 'required|exists:units,id',
            'part_number'      => 'required|string|max:50|unique:spare_parts,part_number',
            'oem_number'       => 'nullable|string|max:100',
            'name'             => 'required|string|max:200',
            'description'      => 'nullable|string|max:1000',
            'buying_price'     => 'nullable|numeric|min:0',
            'selling_price_min'=> 'nullable|numeric|min:0',
            'selling_price_max'=> 'nullable|numeric|min:0',
            'reorder_level'    => 'required|integer|min:0',
            'current_stock'    => 'required|integer|min:0',
            'location'         => 'nullable|string|max:100',
            'status'           => 'required|in:active,inactive',
            'compatible_vehicles' => 'nullable|array',
            'compatible_vehicles.*' => 'exists:vehicle_models,id',
        ]);

        // Auto-assign default category (first available)
        $data['part_category_id'] = PartCategory::orderBy('id')->value('id');

        $compatibleVehicles = $data['compatible_vehicles'] ?? [];
        unset($data['compatible_vehicles']);

        $part = SparePart::create($data);

        if (!empty($compatibleVehicles)) {
            $part->compatibleVehicles()->sync($compatibleVehicles);
        }

        return redirect()->route('catalog.spare-parts.index')
            ->with('success', "Spare part '{$part->name}' created successfully.");
    }

    public function show(SparePart $sparePart)
    {
        $sparePart->load('category', 'unit', 'compatibleVehicles.vehicleType');
        $recentMovements = $sparePart->stockMovements()->with('user')->latest()->limit(10)->get();
        $unsoldStock = (int) \Illuminate\Support\Facades\DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'spare_part')
            ->where('pi.spare_part_id', $sparePart->id)
            ->where('p.status', 'received')
            ->selectRaw('COALESCE(SUM(pi.quantity - pi.total_sold), 0) as total_unsold')
            ->value('total_unsold');
        return view('catalog.spare-parts.show', compact('sparePart', 'recentMovements', 'unsoldStock'));
    }

    public function edit(SparePart $sparePart)
    {
        $categories = PartCategory::active()->with('children')->rootCategories()->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $vehicles   = VehicleModel::active()->with('vehicleType')->orderBy('model_name')->get();
        $sparePart->load('compatibleVehicles');
        $unsoldStock = (int) \Illuminate\Support\Facades\DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'spare_part')
            ->where('pi.spare_part_id', $sparePart->id)
            ->where('p.status', 'received')
            ->selectRaw('COALESCE(SUM(pi.quantity - pi.total_sold), 0) as total_unsold')
            ->value('total_unsold');
        return view('catalog.spare-parts.edit', compact('sparePart', 'categories', 'units', 'vehicles', 'unsoldStock'));
    }

    public function update(Request $request, SparePart $sparePart)
    {
        $data = $request->validate([
            'unit_id'          => 'required|exists:units,id',
            'part_number'      => 'required|string|max:50|unique:spare_parts,part_number,' . $sparePart->id,
            'oem_number'       => 'nullable|string|max:100',
            'name'             => 'required|string|max:200',
            'description'      => 'nullable|string|max:1000',
            'buying_price'     => 'nullable|numeric|min:0',
            'selling_price_min'=> 'nullable|numeric|min:0',
            'selling_price_max'=> 'nullable|numeric|min:0',
            'reorder_level'    => 'required|integer|min:0',
            'location'         => 'nullable|string|max:100',
            'status'           => 'required|in:active,inactive',
            'compatible_vehicles'   => 'nullable|array',
            'compatible_vehicles.*' => 'exists:vehicle_models,id',
        ]);

        // Preserve existing category on update
        $data['part_category_id'] = $sparePart->part_category_id
            ?? PartCategory::orderBy('id')->value('id');

        $compatibleVehicles = $data['compatible_vehicles'] ?? [];
        unset($data['compatible_vehicles']);

        $sparePart->update($data);
        $sparePart->compatibleVehicles()->sync($compatibleVehicles);

        return redirect()->route('catalog.spare-parts.index')
            ->with('success', 'Spare part updated successfully.');
    }

    public function destroy(SparePart $sparePart)
    {
        if ($sparePart->saleItems()->exists() || $sparePart->purchaseItems()->exists()) {
            return back()->with('error', 'Cannot delete: this part has sales/purchase records. Deactivate it instead.');
        }

        $sparePart->delete();
        return redirect()->route('catalog.spare-parts.index')
            ->with('success', 'Spare part deleted.');
    }
}
