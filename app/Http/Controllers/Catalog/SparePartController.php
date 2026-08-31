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
        $query = SparePart::with('unit', 'compatibleVehicles');

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

    // ── Export all spare parts to Excel ────────────────────────────
    public function exportExcel()
    {
        $parts = SparePart::with('unit', 'compatibleVehicles')->orderBy('name')->get();
        return $this->buildSpExcel($parts, 'spare-parts-export.xlsx', false);
    }

    public function exportTemplate()
    {
        return $this->buildSpExcel(collect(), 'spare-parts-template.xlsx', true);
    }

    private function buildSpExcel($parts, string $filename, bool $templateOnly)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Spare Parts');
        $headers = ['Part Number *', 'Part Name *', 'OEM Number', 'Unit *', 'Shelf', 'Min Selling Price', 'Max Selling Price', 'Reorder Level', 'Status', 'Compatible Vehicle Models (comma separated)'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            ]);
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($i === 9 ? 40 : 20);
        }
        // Example row
        foreach (['SP-0001','Piston Ring Set','OEM123','Pcs','A-3','400','500','5','active','Bajaj Boxer, Bajaj Pulsar 150'] as $i => $v) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}2", $v);
        }
        $sheet->getStyle('A2:J2')->applyFromArray(['font' => ['italic' => true, 'color' => ['argb' => 'FF94A3B8']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']]]);
        if (!$templateOnly) {
            $row = 3;
            foreach ($parts as $p) {
                $vehicles = $p->compatibleVehicles->map(fn($v) => $v->brand . ' ' . $v->model_name)->join(', ');
                $vals = [$p->part_number, $p->name, $p->oem_number, $p->unit?->abbreviation, $p->location, $p->selling_price_min, $p->selling_price_max, $p->reorder_level, $p->status, $vehicles];
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
            $unitCache = \App\Models\Unit::pluck('id', 'abbreviation')->merge(\App\Models\Unit::pluck('id', 'name'))->toArray();
            $defaultCatId = \App\Models\PartCategory::orderBy('id')->value('id');
            $vmCache = \App\Models\VehicleModel::selectRaw("id, CONCAT(brand, ' ', model_name) as full_name")->pluck('id', 'full_name')->toArray();
            foreach ($rows as $i => $row) {
                if ($i <= 2) continue;
                $partNum = trim($row['A'] ?? ''); $name = trim($row['B'] ?? '');
                if (!$partNum || !$name) continue;
                $unitAbbr = trim($row['D'] ?? '');
                $unitId   = $unitCache[$unitAbbr] ?? array_values($unitCache)[0] ?? null;
                if (!$unitId) { $errors[] = "Row {$i}: Unit '{$unitAbbr}' not found."; continue; }
                $data = ['part_number' => $partNum, 'name' => $name, 'oem_number' => trim($row['C'] ?? '') ?: null, 'unit_id' => $unitId, 'location' => trim($row['E'] ?? '') ?: null, 'selling_price_min' => is_numeric($row['F'] ?? '') ? (float)$row['F'] : 0, 'selling_price_max' => is_numeric($row['G'] ?? '') ? (float)$row['G'] : 0, 'reorder_level' => is_numeric($row['H'] ?? '') ? (int)$row['H'] : 5, 'status' => in_array(strtolower(trim($row['I'] ?? '')), ['active','inactive']) ? strtolower(trim($row['I'])) : 'active', 'part_category_id' => $defaultCatId, 'current_stock' => 0];
                $existing = SparePart::where('part_number', $partNum)->first();
                if ($existing) { $existing->update($data); $part = $existing; $updated++; } else { $part = SparePart::create($data); $created++; }
                // Sync compatible vehicles
                $vmStr = trim($row['J'] ?? '');
                if ($vmStr) {
                    $vmIds = [];
                    foreach (explode(',', $vmStr) as $vmName) {
                        $vmName = trim($vmName);
                        if (isset($vmCache[$vmName])) $vmIds[] = $vmCache[$vmName];
                    }
                    if ($vmIds) $part->compatibleVehicles()->sync($vmIds);
                }
            }
            $msg = "Import complete: {$created} created, {$updated} updated." . ($errors ? ' Errors: '.implode('; ', array_slice($errors,0,3)) : '');
            return back()->with($errors ? 'warning' : 'success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}