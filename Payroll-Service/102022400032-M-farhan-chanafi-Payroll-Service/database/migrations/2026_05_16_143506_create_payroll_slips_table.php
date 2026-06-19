<?php

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
        Schema::create('payroll_slips', function (Blueprint $table) {
    $table->id();
    $table->string('nip');
    $table->string('employee_name');
    $table->integer('tahun');
    $table->integer('bulan');
    $table->decimal('gaji_pokok', 15, 2);
    $table->decimal('tunjangan_tetap', 15, 2);
    $table->integer('jumlah_hadir')->default(0);
    $table->integer('jumlah_izin')->default(0);
    $table->integer('jumlah_sakit')->default(0);
    $table->integer('jumlah_alpha')->default(0);
    $table->decimal('potongan_absensi', 15, 2)->default(0);
    $table->decimal('total_gaji', 15, 2);
    $table->string('status')->default('Selesai');
    $table->timestamps();

    $table->unique(['nip', 'tahun', 'bulan']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_slips');
    }
};
