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
        Schema::create('jadwal_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengasuh_id')->constrained('pengguna');
            $table->foreignId('kelompok_usia_id')->constrained('kelompok_usia');
            $table->unsignedTinyInteger('hari'); // 1 sampai 7, pola mingguan
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();

            $table->index(['pengasuh_id', 'hari']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_shift');
    }
};
