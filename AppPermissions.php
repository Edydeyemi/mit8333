<?php

declare(strict_types=1);

return [
    'permissions' => [
        // User & Personnel Management
        'users.manage' => 'Create, update, suspend and manage campus users',
        'users.view' => 'View personnel directory and user profiles',
        'officers.add' => 'Provision maintenance technicians and officers',
        'officers.modify' => 'Update technician profiles and specialties',
        'officers.suspend' => 'Suspend technician access and task assignments',

        // Roles & Permissions Management
        'roles.manage' => 'Create, edit, and delete system roles and assign permissions',
        'roles.view' => 'View system roles and permission matrices',

        // Service Categories Management
        'categories.manage' => 'Create, update, toggle active status, and delete service categories',
        'categories.view' => 'View facility service categories list',

        // Tasks, Work Orders & Dispatch
        'tasks.assign' => 'Dispatch and assign tickets to maintenance officers',
        'tasks.reassign' => 'Reassign active tasks to alternative technicians',
        'tasks.cancel' => 'Cancel service requests and work orders',
        'tasks.report' => 'Log incident reports on behalf of campus community',
        'tasks.view' => 'View assigned maintenance tasks and work orders',
        'tasks.update' => 'Update progress status and attach resolution proof',

        // Service Requests (Requester / Public)
        'requests.create' => 'Submit facility service and maintenance requests',
        'requests.view' => 'Track submitted service requests and history',
        'requests.cancel' => 'Cancel own submitted service requests',

        // Analytics, Audits & Notifications
        'reports.generate' => 'Generate and export facility analytics and Excel reports',
        'reports.view' => 'View SLA metrics, incident volume, and audit stream',
        'notifications.view' => 'Access and manage system and dispatch notifications',
    ],
];
