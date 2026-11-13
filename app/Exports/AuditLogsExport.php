<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = AuditLog::with('user');

        if (!empty($this->filters['auditable_type'])) {
            $query->where('auditable_type', $this->filters['auditable_type']);
        }

        if (!empty($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('actor_id', $this->filters['user_id']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->filters['from_date']));
        }

        if (!empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->filters['to_date']));
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Audit ID',
            'Timestamp (UTC)',
            'Actor Name',
            'Actor Email',
            'Action Protocol',
            'Auditable Entity',
            'Entity ID',
            'Audit Summary / Details',
            'IP Address',
        ];
    }

    /**
     * @param AuditLog $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->created_at?->format('Y-m-d H:i:s') ?? '',
            $row->user?->name ?? 'System',
            $row->user?->email ?? 'N/A',
            strtoupper(str_replace('_', ' ', $row->action)),
            class_basename($row->auditable_type ?? 'N/A'),
            $row->auditable_id ?? 'N/A',
            $row->details ?? json_encode($row->changes),
            $row->ip_address ?? 'N/A',
        ];
    }

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
