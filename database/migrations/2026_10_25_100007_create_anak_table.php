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
        Schema::create('anak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftaran');
            $table->foreignId('orang_tua_id')->constrained('pengguna');
            $table->foreignId('kelompok_usia_id')->nullable()->constrained('kelompok_usia');
            $table->string('nama_lengkap', 100);
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 1); // L / P
            $table->string('status', 20)->default('menunggu'); // menunggu / aktif / nonaktif
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kelompok_usia_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anak');
    }
};
