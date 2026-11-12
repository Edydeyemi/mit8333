<div class="kanban-card">
    <div class="kanban-card-top">
        <span class="badge bg-secondary text-dark border small fw-bold font-mono">{{ $task->tracking_code }}</span>
        <span class="{{ $task->priority_badge_class }}">
            {{ ucfirst($task->priority) }}
        </span>
    </div>

    <div class="kanban-card-title fw-bold text-navy-900 mb-1">
        <a href="{{ route('facility.tasks.show', $task->slug) }}" class="text-navy-900 text-decoration-none">
            {{ $task->title }}
        </a>
    </div>

    <div class="kanban-card-meta small text-muted mb-2">
        <span><i class="bi bi-geo-alt me-1"></i> {{ $task->building }} ({{ $task->room_number }})</span>
    </div>

    @if ($task->statusUpdates->isNotEmpty())
        <div class="small text-muted mb-2 p-1 px-2 bg-light rounded border d-flex justify-content-between align-items-center"
            style="font-size:0.75rem;">
            <span><i class="bi bi-clock-history me-1"></i> Last update:
                {{ $task->statusUpdates->first()->created_at->diffForHumans() }}</span>
            @if ($task->statusUpdates->first()->attachments->isNotEmpty())
                <span title="{{ $task->statusUpdates->first()->attachments->count() }} attached files"><i
                        class="bi bi-paperclip"></i> {{ $task->statusUpdates->first()->attachments->count() }}</span>
            @endif
        </div>
    @endif

    <div class="kanban-card-footer d-flex justify-content-between align-items-center pt-2 border-top">
        <span class="text-muted small">
            <i class="bi {{ $task->category?->icon ?: 'bi-tools' }}"
                style="color: {{ $task->category?->color ?: '#0a192f' }};"></i>
            {{ $task->category?->name ?: 'General' }}
        </span>

        <div class="dropdown">
            <button class="btn btn-sm btn-subtle py-0 px-2" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                Action <i class="bi bi-chevron-down ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                @if ($task->status !== 'in_progress')
                    <li>
                        <a class="dropdown-item small" href="javascript:void(0)"
                            onclick="openUpdateModal('{{ $task->slug }}', '{{ $task->tracking_code }}', 'in_progress', 'Technician on site. Starting diagnostic and maintenance work.')">
                            <i class="bi bi-play-circle text-primary me-2"></i> Move to In Progress
                        </a>
                    </li>
                @endif
                @if ($task->status !== 'on_hold')
                    <li>
                        <a class="dropdown-item small" href="javascript:void(0)"
                            onclick="openUpdateModal('{{ $task->slug }}', '{{ $task->tracking_code }}', 'on_hold', '')">
                            <i class="bi bi-pause-circle text-warning me-2"></i> Move to On Hold
                        </a>
                    </li>
                @endif
                @if ($task->status !== 'resolved')
                    <li>
                        <a class="dropdown-item small" href="javascript:void(0)"
                            onclick="openUpdateModal('{{ $task->slug }}', '{{ $task->tracking_code }}', 'resolved', 'Inspected fault location, replaced worn components, verified nominal operation, and performed safety tests.')">
                            <i class="bi bi-check2-circle text-success me-2"></i> Mark Resolved & Sign-Off
                        </a>
                    </li>
                @endif
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item small" href="{{ route('facility.tasks.show', $task->slug) }}">
                        <i class="bi bi-eye me-2"></i> View Full Details
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>