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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained('pendaftaran');
            $table->string('nomor_tagihan', 30)->unique();
            $table->decimal('jumlah', 12, 2);
            $table->string('status', 20)->default('belum_lunas'); // belum_lunas / lunas
            $table->timestamp('diterbitkan_pada');
            $table->date('jatuh_tempo')->nullable();
            $table->timestamp('lunas_pada')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
