<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak audit penghapusan data kehadiran (PBI-16g). Baris kehadiran dihapus permanen supaya anak bisa
     * dicatat ulang (unique anak_id + tanggal), sehingga salinan datanya disimpan di sini.
     */
    public function up(): void
    {
        Schema::create('log_penghapusan_kehadiran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kehadiran_id'); // bukan foreign key: barisnya sudah dihapus
            $table->foreignId('anak_id')->constrained('anak');
            $table->date('tanggal');
            $table->json('data'); // salinan lengkap baris kehadiran sebelum dihapus
            $table->string('alasan', 255);
            $table->foreignId('dihapus_oleh_id')->constrained('pengguna');
            $table->timestamp('dihapus_pada');
            $table->timestamps();

            $table->index(['anak_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_penghapusan_kehadiran');
    }
};
