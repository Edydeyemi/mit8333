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
        Schema::table('service_request_attachments', function (Blueprint $table): void {
            $table->foreignId('status_update_id')
                ->nullable()
                ->after('service_request_id')
                ->constrained('service_request_status_updates')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_request_attachments', function (Blueprint $table): void {
            $table->dropForeign(['status_update_id']);
            $table->dropColumn('status_update_id');
        });
    }
};

