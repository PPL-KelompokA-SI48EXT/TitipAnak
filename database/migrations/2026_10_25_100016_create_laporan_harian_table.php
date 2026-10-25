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
        Schema::create('laporan_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anak');
            $table->foreignId('pengasuh_id')->constrained('pengguna');
            $table->date('tanggal');
            $table->string('status_makan', 20)->nullable(); // baik / sebagian / tidak_makan
            $table->text('catatan_makan')->nullable();
            $table->unsignedInteger('durasi_tidur_menit')->nullable();
            $table->text('catatan_buang_air')->nullable(); // BAB / BAK
            $table->string('suasana_hati', 30)->nullable();
            $table->text('catatan_aktivitas')->nullable();
            $table->text('catatan_umum')->nullable();
            $table->timestamps();

            $table->unique(['anak_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_harian');
    }
};
