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
        Schema::create('menu_makan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('waktu_makan', 20); // sarapan / makan_siang / camilan
            $table->string('nama_menu', 150);
            $table->text('deskripsi')->nullable();
            $table->foreignId('dibuat_oleh_id')->constrained('pengguna');
            $table->timestamps();

            $table->unique(['tanggal', 'waktu_makan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_makan');
    }
};
