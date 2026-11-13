<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Prepare query for export.
     */
    public function query(): Builder
    {
        $query = ServiceRequest::with(['category', 'user', 'assignedOfficer']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('service_category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->filters['from_date']));
        }

        if (!empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->filters['to_date']));
        }

        return $query->latest();
    }

    /**
     * Define column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Reference Code',
            'Title',
            'Category',
            'Priority',
            'Status',
            'Building',
            'Wing / Floor',
            'Room / Lab',
            'Submitted By',
            'Submitter Email',
            'Assigned Officer',
            'Created Date',
            'Resolved Date',
        ];
    }

    /**
     * Map data row.
     *
     * @param ServiceRequest $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->tracking_code,
            $row->title,
            $row->category?->name ?? 'Uncategorized',
            strtoupper($row->priority),
            strtoupper(str_replace('_', ' ', $row->status)),
            $row->building,
            $row->wing_floor,
            $row->room_number,
            $row->user?->name ?? 'N/A',
            $row->user?->email ?? 'N/A',
            $row->assignedOfficer?->name ?? 'Unassigned',
            $row->created_at?->format('Y-m-d H:i') ?? '',
            $row->resolved_at?->format('Y-m-d H:i') ?? '',
        ];
    }

    /**
     * Apply styles to worksheet.
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
                    'startColor' => ['argb' => 'FF0A192F'], // Navy 900
                ],
            ],
        ];
    }
}

