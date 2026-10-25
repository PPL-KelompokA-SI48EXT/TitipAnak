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
        Schema::create('laporan_insiden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anak');
            $table->foreignId('pengasuh_id')->constrained('pengguna');
            $table->timestamp('waktu_kejadian');
            $table->string('judul', 150);
            $table->text('deskripsi');
            $table->text('tindakan')->nullable();
            $table->timestamps();

            $table->index(['anak_id', 'waktu_kejadian']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_insiden');
    }
};
