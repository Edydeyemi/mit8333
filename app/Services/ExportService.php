<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\AuditLogsExport;
use App\Exports\OfficerPerformanceExport;
use App\Exports\ServiceRequestsExport;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {
    }

    /**
     * Export filtered service requests to CSV.
     *
     * @param array<string, mixed> $filters
     */
    public function exportServiceRequests(array $filters = []): BinaryFileResponse
    {
        $filename = 'headworld_service_requests_' . date('Y-m-d_His') . '.csv';
        return Excel::download(new ServiceRequestsExport($filters), $filename, ExcelWriter::CSV);
    }

    /**
     * Export officer KPI performance metrics to CSV.
     */
    public function exportOfficerPerformance(): BinaryFileResponse
    {
        $filename = 'headworld_officer_kpis_' . date('Y-m-d_His') . '.csv';
        return Excel::download(new OfficerPerformanceExport($this->metricsService), $filename, ExcelWriter::CSV);
    }

    /**
     * Export audit trail log to CSV.
     *
     * @param array<string, mixed> $filters
     */
    public function exportAuditLogs(array $filters = []): BinaryFileResponse
    {
        $filename = 'headworld_audit_trail_' . date('Y-m-d_His') . '.csv';
        return Excel::download(new AuditLogsExport($filters), $filename, ExcelWriter::CSV);
    }
}

