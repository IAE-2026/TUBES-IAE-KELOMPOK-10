<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('created_by_email')->nullable()->after('note');
            $table->string('created_by_name')->nullable()->after('created_by_email');
            $table->string('local_role', 50)->nullable()->after('created_by_name');
            $table->string('audit_status', 30)->nullable()->after('local_role');
            $table->string('audit_receipt_number')->nullable()->after('audit_status');
            $table->uuid('central_event_id')->nullable()->after('audit_receipt_number');
            $table->string('event_routing_key')->nullable()->after('central_event_id');
            $table->timestamp('event_published_at')->nullable()->after('event_routing_key');

            $table->index('audit_receipt_number');
            $table->index('central_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['audit_receipt_number']);
            $table->dropIndex(['central_event_id']);
            $table->dropColumn([
                'created_by_email',
                'created_by_name',
                'local_role',
                'audit_status',
                'audit_receipt_number',
                'central_event_id',
                'event_routing_key',
                'event_published_at',
            ]);
        });
    }
};
