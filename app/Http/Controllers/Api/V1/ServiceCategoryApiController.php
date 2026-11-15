<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceCategoryApiController extends Controller
{
    use ApiResponse;

    /**
     * List all active maintenance service categories.
     */
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'icon',
                'created_at',
            ]);

        return $this->successResponse($categories, 'Service categories retrieved successfully.');
    }
}
