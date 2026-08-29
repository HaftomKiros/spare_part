<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Collection;

class SalesReportExport
{
    protected Collection $sales;
    protected object     $summary;
    protected float      $totalProfit;
    protected string     $dateFrom;
    protected string     $dateTo;
    protected ?string    $itemType;
    protected ?string    $warehouseName;

    public function __construct(
        Collection $sales,
        object     $summary,
        float      $totalProfit,
        string     $dateFrom,
        string     $dateTo,
        ?string    $itemType = null,
        ?string    $warehouseName = null
    ) {
        $this->sales         = $sales;
        $this->summary       = $summary;
        $this->totalProfit   = $totalProfit;
        $this->dateFrom      = $dateFrom;
        $this->dateTo        = $dateTo;
        $this->itemType      = $itemType;
        $this->warehouseName = $warehouseName;
    }

    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Report');

        // ── Header branding ──────────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Sales Report');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:I2');
        $typeLabel = $this->itemType === 'vehicle' ? 'Vehicles Only'
                   : ($this->itemType === 'spare_part' ? 'Spare Parts Only' : 'All Items');
        $warehouse = $this->warehouseName ?? 'All Warehouses';
        $period    = \Carbon\Carbon::parse($this->dateFrom)->format('M d, Y')
                   . ' — '
                   . \Carbon\Carbon::parse($this->dateTo)->format('M d, Y');
        $sheet->setCellValue('A2', "Period: {$period} | Type: {$typeLabel} | Warehouse: {$warehouse}");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Summary cards row ─────────────────────────────────────────────
        $row = 4;
        $summaryHeaders = ['Invoices', 'Revenue (Br)', 'Discounts (Br)', 'Tax (Br)', 'Collected (Br)', 'Outstanding (Br)', 'Net Profit (Br)'];
        $summaryValues  = [
            number_format($this->summary->total_invoices),
            number_format($this->summary->gross_revenue, 2),
            number_format($this->summary->total_discounts, 2),
            number_format($this->summary->total_tax, 2),
            number_format($this->summary->total_collected, 2),
            number_format($this->summary->total_outstanding, 2),
            number_format($this->totalProfit, 2),
        ];
        foreach ($summaryHeaders as $i => $h) {
            $col = chr(65 + $i); // A, B, C ...
            $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF6366F1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $row++;
        foreach ($summaryValues as $i => $v) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}{$row}", $v);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
            ]);
        }

        // ── Table header ─────────────────────────────────────────────────
        $row += 2;
        $headers = ['Invoice #', 'Customer', 'Date', 'Items', 'Total (Br)', 'Paid (Br)', 'Balance (Br)', 'Payment', 'Warehouse'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // ── Table rows ────────────────────────────────────────────────────
        foreach ($this->sales as $s) {
            $row++;
            $itemCount = $s->items ? $s->items->count() : '—';
            $data = [
                $s->invoice_number,
                $s->customer_name ?? 'Walk-in',
                $s->sale_date->format('M d, Y'),
                $itemCount,
                number_format($s->total, 2),
                number_format($s->paid_amount, 2),
                $s->balance > 0 ? number_format($s->balance, 2) : '—',
                ucfirst(str_replace('_', ' ', $s->payment_method ?? '')),
                $s->warehouse?->name ?? '—',
            ];
            foreach ($data as $i => $v) {
                $col = chr(65 + $i);
                $sheet->setCellValue("{$col}{$row}", $v);
            }
            // Alternate row shading
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                ]);
            }
        }

        // ── Totals row ────────────────────────────────────────────────────
        $row++;
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("E{$row}", number_format($this->summary->gross_revenue, 2));
        $sheet->setCellValue("F{$row}", number_format($this->summary->total_collected, 2));
        $sheet->setCellValue("G{$row}", number_format($this->summary->total_outstanding, 2));
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
        ]);

        // ── Column widths ─────────────────────────────────────────────────
        $widths = [18, 22, 14, 8, 14, 14, 14, 16, 20];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }

        // ── Borders on table ──────────────────────────────────────────────
        $tableStart = 7;
        $sheet->getStyle("A{$tableStart}:I{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ]);

        $writer   = new Xlsx($spreadsheet);
        $filename = 'sales-report-' . $this->dateFrom . '-' . $this->dateTo . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
