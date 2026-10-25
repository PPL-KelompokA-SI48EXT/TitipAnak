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
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orang_tua_id')->constrained('pengguna');
            $table->foreignId('paket_layanan_id')->constrained('paket_layanan');
            $table->string('status', 25)->default('draft'); // draft / menunggu_verifikasi / disetujui / ditolak
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('diverifikasi_oleh_id')->nullable()->constrained('pengguna');
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->text('catatan_penolakan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['orang_tua_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftaran');
    }
};
