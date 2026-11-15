@extends('layouts.app')

@section('title', 'Notification Center | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('content')
    <div class="page-content">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-navy-900">Notifications & Dispatch Alerts</h4>
                <p class="text-muted small mb-0">System communications, work order assignments, SLA escalations, and
                    incident updates</p>
            </div>
            <div class="d-flex gap-2">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-navy btn-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check2-all"></i> Mark All as Read
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Feedback Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-gold p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Total Notifications</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalCount }}</div>
                    <small class="text-muted">Lifecycle communications</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-rose p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Unread & Pending</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-exclamation-circle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $unreadCount }}</div>
                    <small class="text-danger"><i class="bi bi-clock"></i> Requires your attention</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-emerald p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Read Archive</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $readCount }}</div>
                    <small class="text-success"><i class="bi bi-archive"></i> Acknowledged alerts</small>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card-modern p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.notifications.index', ['status' => 'all']) }}"
                        class="btn btn-sm {{ $status === 'all' ? 'btn-navy' : 'btn-subtle' }}">
                        All Alerts ({{ $totalCount }})
                    </a>
                    <a href="{{ route('admin.notifications.index', ['status' => 'unread']) }}"
                        class="btn btn-sm {{ $status === 'unread' ? 'btn-navy' : 'btn-subtle' }}">
                        Unread ({{ $unreadCount }})
                    </a>
                    <a href="{{ route('admin.notifications.index', ['status' => 'read']) }}"
                        class="btn btn-sm {{ $status === 'read' ? 'btn-navy' : 'btn-subtle' }}">
                        Read Archive ({{ $readCount }})
                    </a>
                </div>

                <span class="badge bg-secondary text-navy-900 border p-2 small">
                    Showing <strong>{{ $notifications->count() }}</strong> of {{ $notifications->total() }} alerts
                </span>
            </div>
        </div>

        <!-- Notification Cards Stream -->
        <div class="card-modern">
            <div class="card-body-headworld p-0">
                @forelse($notifications as $notif)
                    @php
                        $data = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?? []);
                        $isUnread = is_null($notif->read_at);
                        $icon = $data['icon'] ?? 'bi-bell-fill';
                        $title = $data['title'] ?? 'System Communication';
                        $message = $data['message'] ?? 'Notification received.';
                        $category = $data['category'] ?? 'general';
                        $url = $data['url'] ?? null;
                    @endphp
                    <div class="p-3 border-bottom d-flex align-items-start gap-3 {{ $isUnread ? 'bg-light bg-opacity-50' : '' }}"
                        style="{{ $isUnread ? 'border-left: 4px solid var(--gold-500, #d4af37);' : '' }}">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $isUnread ? 'bg-warning bg-opacity-25 text-warning' : 'bg-light text-muted' }}"
                            style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-navy-900 d-flex align-items-center gap-2">
                                    {{ $title }}
                                    @if($isUnread)
                                        <span class="badge bg-gold text-navy-900" style="font-size: 0.65rem;">NEW</span>
                                    @endif
                                </h6>
                                <small class="text-muted font-mono" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Recently' }}
                                </small>
                            </div>
                            <p class="text-muted small mb-2">{{ $message }}</p>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-secondary text-muted border text-uppercase font-mono"
                                    style="font-size: 0.68rem;">
                                    {{ $category }}
                                </span>
                                @if($url)
                                    <a href="{{ $url }}" class="btn btn-xs btn-subtle text-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> View Related Record
                                    </a>
                                @endif
                                @if($isUnread)
                                    <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-subtle text-muted">
                                            <i class="bi bi-check2"></i> Mark Read
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        No notifications found in this mailbox.
                    </div>
                @endforelse

                <!-- Server-Side Pagination Bar -->
                @if($notifications->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            Showing {{ $notifications->firstItem() }} to {{ $notifications->lastItem() }} of
                            {{ $notifications->total() }} notifications
                        </span>
                        <div>
                            {{ $notifications->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection