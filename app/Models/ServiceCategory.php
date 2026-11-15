<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'sla_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot model events to auto-generate unique slug.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ServiceCategory $category): void {
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $category->slug = $slug;
            }
        });
    }

    /**
     * Get route key name for implicit model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Service requests filed under this category.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_category_id');
    }

    /**
     * Virtual SLA resolution hours accessor.
     */
    public function getSlaHoursAttribute(): int
    {
        return match ($this->slug) {
            'plumbing-water' => 12,
            'electrical-lighting' => 24,
            'classroom-equipment' => 24,
            'damaged-furniture' => 48,
            'internet-problems' => 24,
            'hostel-maintenance' => 36,
            default => 24,
        };
    }

    /**
     * Scope query to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

