<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view the list of service requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('requests.view') || $user->can('reports.view') || $user->can('tasks.view');
    }

    /**
     * Determine whether the user can view the specific service request.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->can('reports.view')) {
            return true;
        }

        if ($serviceRequest->assigned_officer_id === $user->id && $user->can('tasks.view')) {
            return true;
        }

        return $serviceRequest->user_id === $user->id && $user->can('requests.view');
    }

    /**
     * Determine whether the user can create a service request.
     */
    public function create(User $user): bool
    {
        return $user->can('requests.create');
    }

    /**
     * Determine whether the user can cancel the service request.
     */
    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->user_id === $user->id
            && $user->can('requests.cancel')
            && in_array($serviceRequest->status, ['submitted', 'assigned'], true);
    }

    /**
     * Determine whether the user can assign or reassign the service request.
     */
    public function assign(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('tasks.assign') || $user->can('tasks.reassign');
    }

    /**
     * Determine whether the user can update the status of the service request.
     */
    public function updateStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->assigned_officer_id === $user->id && $user->can('tasks.update');
    }
}

