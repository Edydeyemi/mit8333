<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfficerPerformanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        $officers = User::where('role', 'officer')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $rows = [];

        foreach ($officers as $officer) {
            $totalAssigned = ServiceRequest::where('assigned_officer_id', $officer->id)->count();
            $resolvedCount = ServiceRequest::where('assigned_officer_id', $officer->id)
                ->where('status', 'resolved')
                ->count();
            $activeCount = ServiceRequest::where('assigned_officer_id', $officer->id)
                ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
                ->count();

            $slaRate = $this->metricsService->calculateSlaCompliance($officer->id);

            $rows[] = [
                $officer->name,
                $officer->email,
                $officer->department ?? 'Campus Facility Maintenance',
                $totalAssigned,
                $activeCount,
                $resolvedCount,
                $slaRate . '%',
            ];
        }

        return collect($rows);
    }

    /**
     * Headings for export.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Officer Name',
            'Email',
            'Department',
            'Total Tasks Assigned',
            'Active Tasks',
            'Resolved Tasks',
            'SLA Compliance Rate',
        ];
    }

    /**
     * Styles for sheet.
     *
     * @return array<mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0A192F'],
                ],
            ],
        ];
    }
}

