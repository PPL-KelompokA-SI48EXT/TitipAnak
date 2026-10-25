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
        Schema::create('laporan_perkembangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anak');
            $table->foreignId('pengasuh_id')->constrained('pengguna');
            $table->date('periode_bulan'); // selalu tanggal 1 pada bulan yang dinilai
            $table->text('catatan_motorik')->nullable();
            $table->text('catatan_bahasa')->nullable();
            $table->text('catatan_sosial')->nullable();
            $table->text('catatan_kognitif')->nullable();
            $table->text('catatan_umum')->nullable();
            $table->timestamps();

            $table->unique(['anak_id', 'periode_bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_perkembangan');
    }
};
