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
        Schema::create('kehadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anak');
            $table->date('tanggal');
            $table->timestamp('check_in_pada');
            $table->text('kondisi_check_in')->nullable();
            $table->foreignId('check_in_oleh_id')->constrained('pengguna');
            $table->timestamp('check_out_pada')->nullable();
            $table->foreignId('check_out_oleh_id')->nullable()->constrained('pengguna');
            $table->foreignId('penjemput_terdaftar_id')->nullable()->constrained('penjemput_terdaftar');
            $table->foreignId('izin_penjemputan_id')->nullable()->constrained('izin_penjemputan');
            $table->string('nama_penjemput', 100)->nullable(); // salinan agar riwayat utuh
            $table->timestamps();

            $table->unique(['anak_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kehadiran');
    }
};
