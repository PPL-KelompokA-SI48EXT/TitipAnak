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
        Schema::create('jadwal_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_usia_id')->constrained('kelompok_usia');
            $table->string('nama_kegiatan', 100);
            $table->string('jenis_kegiatan', 30); // makan / tidur / bermain / belajar / lainnya
            $table->unsignedTinyInteger('hari')->nullable(); // null = berlaku setiap hari
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['kelompok_usia_id', 'hari']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_kegiatan');
    }
};
