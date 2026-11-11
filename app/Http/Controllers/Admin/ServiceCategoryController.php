<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    /**
     * Display a paginated listing of service categories with server-side filtering.
     */
    public function index(Request $request): View
    {
        $query = ServiceCategory::query();

        // Search filter
        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Status filter
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $totalCount = ServiceCategory::count();
        $activeCount = ServiceCategory::where('is_active', true)->count();
        $inactiveCount = ServiceCategory::where('is_active', false)->count();

        $categories = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.service_categories.index', compact(
            'categories',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'search',
            'status'
        ));
    }

    /**
     * Store a newly created service category in storage.
     */
    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $category = ServiceCategory::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'bi-tools',
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Service category '{$category->name}' was successfully created.");
    }

    /**
     * Update the specified service category in storage.
     */
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $validated = $request->validated();

        $serviceCategory->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? $serviceCategory->icon ?? 'bi-tools',
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Service category '{$serviceCategory->name}' was successfully updated.");
    }

    /**
     * Toggle the active/inactive status of a service category.
     */
    public function toggleStatus(ServiceCategory $serviceCategory): RedirectResponse
    {
        $serviceCategory->is_active = !$serviceCategory->is_active;
        $serviceCategory->save();

        $statusLabel = $serviceCategory->is_active ? 'activated' : 'marked as inactive';

        return redirect()->back()
            ->with('success', "Service category '{$serviceCategory->name}' has been {$statusLabel}.");
    }

    /**
     * Remove the specified service category from storage.
     */
    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        $name = $serviceCategory->name;
        $serviceCategory->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Service category '{$name}' was successfully deleted.");
    }
}

