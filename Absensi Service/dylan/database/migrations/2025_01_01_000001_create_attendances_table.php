<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 50);
            $table->date('date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha']);
            $table->text('note')->nullable();
            $table->timestamps();

            // Prevent duplicate attendance for same employee on same day
            $table->unique(['employee_id', 'date'], 'unique_employee_date');

            // Index for common queries
            $table->index('employee_id');
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
