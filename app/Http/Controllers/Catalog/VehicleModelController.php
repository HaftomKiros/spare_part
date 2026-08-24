<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\VehicleStock;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleModel::with('vehicleType', 'stock');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('model_name', 'like', "%{$request->search}%")
                  ->orWhere('model_code', 'like', "%{$request->search}%")
                  ->orWhere('brand', 'like', "%{$request->search}%");
            });
        }
        if ($request->type) {
            $query->where('vehicle_type_id', $request->type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $models = $query->latest()->paginate(15)->withQueryString();
        $types  = VehicleType::active()->get();

        return view('catalog.vehicle-models.index', compact('models', 'types'));
    }

    public function create()
    {
        $types = VehicleType::active()->get();
        return view('catalog.vehicle-models.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'brand'           => 'required|string|max:100',
            'model_name'      => 'required|string|max:100',
            'model_code'      => 'nullable|string|max:50',
            'year'            => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'engine_cc'       => 'nullable|string|max:50',
            'buying_price'    => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'description'     => 'nullable|string|max:1000',
            'status'          => 'required|in:active,inactive',
            'opening_stock'   => 'nullable|integer|min:0',
            'reorder_level'   => 'nullable|integer|min:0',
        ]);

        $openingStock  = (int) ($data['opening_stock'] ?? 0);
        $reorderLevel  = (int) ($data['reorder_level'] ?? 2);

        unset($data['opening_stock'], $data['reorder_level']);

        $model = VehicleModel::create($data);

        // Create stock record
        VehicleStock::create([
            'vehicle_model_id' => $model->id,
            'current_stock'    => $openingStock,
            'reorder_level'    => $reorderLevel,
        ]);

        return redirect()->route('catalog.vehicle-models.index')
            ->with('success', "Vehicle model '{$model->full_name}' created successfully.");
    }

    public function show(VehicleModel $vehicleModel)
    {
        $vehicleModel->load('vehicleType', 'stock', 'spareParts.category');
        $recentMovements = $vehicleModel->stockMovements()->with('user')->latest()->limit(10)->get();
        return view('catalog.vehicle-models.show', compact('vehicleModel', 'recentMovements'));
    }

    public function edit(VehicleModel $vehicleModel)
    {
        $types = VehicleType::active()->get();
        $vehicleModel->load('stock');
        return view('catalog.vehicle-models.edit', compact('vehicleModel', 'types'));
    }

    public function update(Request $request, VehicleModel $vehicleModel)
    {
        $data = $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'brand'           => 'required|string|max:100',
            'model_name'      => 'required|string|max:100',
            'model_code'      => 'nullable|string|max:50',
            'year'            => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'engine_cc'       => 'nullable|string|max:50',
            'buying_price'    => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'description'     => 'nullable|string|max:1000',
            'status'          => 'required|in:active,inactive',
            'reorder_level'   => 'nullable|integer|min:0',
        ]);

        $reorderLevel = (int) ($data['reorder_level'] ?? 2);
        unset($data['reorder_level']);

        $vehicleModel->update($data);

        if ($vehicleModel->stock) {
            $vehicleModel->stock->update(['reorder_level' => $reorderLevel]);
        }

        return redirect()->route('catalog.vehicle-models.index')
            ->with('success', 'Vehicle model updated successfully.');
    }

    public function destroy(VehicleModel $vehicleModel)
    {
        if ($vehicleModel->saleItems()->exists() || $vehicleModel->purchaseItems()->exists()) {
            return back()->with('error', 'Cannot delete: this model has sales/purchase records. Deactivate it instead.');
        }

        $vehicleModel->stock()?->delete();
        $vehicleModel->delete();

        return redirect()->route('catalog.vehicle-models.index')
            ->with('success', 'Vehicle model deleted.');
    }
}
