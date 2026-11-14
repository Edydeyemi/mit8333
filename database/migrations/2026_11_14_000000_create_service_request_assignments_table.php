<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_request_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->enum('status', ['active', 'reassigned', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'status'], 'sra_req_status_idx');
            $table->index(['officer_id', 'status'], 'sra_officer_status_idx');
            $table->index('assigned_at', 'sra_assigned_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_assignments');
    }
};
