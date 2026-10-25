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
        Schema::create('izin_penjemputan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anak');
            $table->foreignId('diajukan_oleh_id')->constrained('pengguna');
            $table->string('nama_penjemput', 100);
            $table->string('path_foto_identitas');
            $table->text('alasan');
            $table->date('tanggal_jemput');
            $table->string('status', 20)->default('menunggu'); // menunggu / disetujui / ditolak / dibatalkan
            $table->foreignId('divalidasi_oleh_id')->nullable()->constrained('pengguna');
            $table->timestamp('divalidasi_pada')->nullable();
            $table->timestamps();

            $table->index(['anak_id', 'tanggal_jemput', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izin_penjemputan');
    }
};
