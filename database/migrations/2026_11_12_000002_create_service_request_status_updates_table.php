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
        Schema::create('service_request_status_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('previous_status', 32)->nullable();
            $table->string('status', 32);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'created_at'], 'sr_status_req_created_idx');
            $table->index('user_id', 'sr_status_user_idx');
            $table->index('status', 'sr_status_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_status_updates');
    }
};
