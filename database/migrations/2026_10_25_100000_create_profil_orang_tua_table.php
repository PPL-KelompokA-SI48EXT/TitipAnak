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
        Schema::create('profil_orang_tua', function (Blueprint $table) {
            $table->foreignId('pengguna_id')->primary()->constrained('pengguna')->cascadeOnDelete();
            $table->string('nama_lengkap', 100);
            $table->string('no_hp', 20);
            $table->text('alamat')->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('kontak_darurat', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_orang_tua');
    }
};
