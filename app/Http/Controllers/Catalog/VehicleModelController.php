<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\VehicleStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'vehicle_type_id'   => 'required|exists:vehicle_types,id',
            'brand'             => 'required|string|max:100',
            'model_name'        => 'required|string|max:100',
            'model_code'        => 'nullable|string|max:50',
            'year'              => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'engine_cc'         => 'nullable|string|max:50',
            'selling_price_min' => 'nullable|numeric|min:0',
            'selling_price_max' => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:active,inactive',
            'opening_stock'     => 'nullable|integer|min:0',
            'reorder_level'     => 'nullable|integer|min:0',
        ]);

        $openingStock = (int) ($data['opening_stock'] ?? 0);
        $reorderLevel = (int) ($data['reorder_level'] ?? 2);

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
        $unsoldStock = (int) \Illuminate\Support\Facades\DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'vehicle')
            ->where('pi.vehicle_model_id', $vehicleModel->id)
            ->where('p.status', 'received')
            ->selectRaw('COALESCE(SUM(pi.quantity - pi.total_sold), 0) as total_unsold')
            ->value('total_unsold');
        return view('catalog.vehicle-models.show', compact('vehicleModel', 'recentMovements', 'unsoldStock'));
    }

    public function edit(VehicleModel $vehicleModel)
    {
        $types = VehicleType::active()->get();
        $vehicleModel->load('stock');
        $unsoldStock = (int) \Illuminate\Support\Facades\DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'vehicle')
            ->where('pi.vehicle_model_id', $vehicleModel->id)
            ->where('p.status', 'received')
            ->selectRaw('COALESCE(SUM(pi.quantity - pi.total_sold), 0) as total_unsold')
            ->value('total_unsold');
        return view('catalog.vehicle-models.edit', compact('vehicleModel', 'types', 'unsoldStock'));
    }

    public function update(Request $request, VehicleModel $vehicleModel)
    {
        $data = $request->validate([
            'vehicle_type_id'   => 'required|exists:vehicle_types,id',
            'brand'             => 'required|string|max:100',
            'model_name'        => 'required|string|max:100',
            'model_code'        => 'nullable|string|max:50',
            'year'              => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'engine_cc'         => 'nullable|string|max:50',
            'selling_price_min' => 'nullable|numeric|min:0',
            'selling_price_max' => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:active,inactive',
            'reorder_level'     => 'nullable|integer|min:0',
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

    // ── Export all vehicle models to Excel ─────────────────────────
    public function exportExcel()
    {
        $models = VehicleModel::with('vehicleType')->orderBy('brand')->orderBy('model_name')->get();
        return $this->buildVmExcel($models, 'vehicle-models-export.xlsx', false);
    }

    // ── Export blank template ───────────────────────────────────────
    public function exportTemplate()
    {
        return $this->buildVmExcel(collect(), 'vehicle-models-template.xlsx', true);
    }

    private function buildVmExcel($models, string $filename, bool $templateOnly)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vehicle Models');
        $headers = ['Brand *', 'Model Name *', 'Model Code', 'Vehicle Type *', 'Year', 'Engine CC', 'Buying Price', 'Min Selling Price', 'Max Selling Price', 'Status'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            ]);
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth(20);
        }
        // Example row
        foreach (['Bajaj','Pulsar 150','P150','Two Wheeler','2023','150','45000','60000','75000','active'] as $i => $v) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}2", $v);
        }
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF94A3B8']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
        ]);
        if (!$templateOnly) {
            $row = 3;
            foreach ($models as $m) {
                $vals = [$m->brand, $m->model_name, $m->model_code, $m->vehicleType?->name, $m->year, $m->engine_cc, $m->buying_price, $m->selling_price_min, $m->selling_price_max, $m->status];
                foreach ($vals as $i => $v) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$row}", $v ?? '');
                }
                if ($row % 2 === 0) $sheet->getStyle("A{$row}:J{$row}")->applyFromArray(['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']]]);
                $row++;
            }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(fn() => $writer->save('php://output'), $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Import from Excel ───────────────────────────────────────────
    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $created = 0; $updated = 0; $errors = [];
            $typeCache = \App\Models\VehicleType::pluck('id', 'name')->toArray();
            foreach ($rows as $i => $row) {
                if ($i <= 2) continue;
                $brand = trim($row['A'] ?? ''); $modelName = trim($row['B'] ?? ''); $typeStr = trim($row['D'] ?? '');
                if (!$brand || !$modelName) continue;
                $typeId = $typeCache[$typeStr] ?? null;
                if (!$typeId && $typeStr) { $vt = \App\Models\VehicleType::firstOrCreate(['name' => $typeStr], ['status' => 'active']); $typeId = $vt->id; $typeCache[$typeStr] = $typeId; }
                if (!$typeId) { $errors[] = "Row {$i}: Vehicle type required."; continue; }
                $data = ['vehicle_type_id' => $typeId, 'brand' => $brand, 'model_name' => $modelName, 'model_code' => trim($row['C'] ?? '') ?: null, 'year' => is_numeric($row['E'] ?? '') ? (int)$row['E'] : null, 'engine_cc' => is_numeric($row['F'] ?? '') ? (int)$row['F'] : null, 'buying_price' => is_numeric($row['G'] ?? '') ? (float)$row['G'] : 0, 'selling_price_min' => is_numeric($row['H'] ?? '') ? (float)$row['H'] : 0, 'selling_price_max' => is_numeric($row['I'] ?? '') ? (float)$row['I'] : 0, 'status' => in_array(strtolower(trim($row['J'] ?? '')), ['active','inactive']) ? strtolower(trim($row['J'])) : 'active'];
                $existing = VehicleModel::where('brand', $brand)->where('model_name', $modelName)->first();
                if ($existing) { $existing->update($data); $updated++; } else { VehicleModel::create($data); $created++; }
            }
            $msg = "Import complete: {$created} created, {$updated} updated." . ($errors ? ' Errors: '.implode('; ', array_slice($errors,0,3)) : '');
            return back()->with($errors ? 'warning' : 'success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}