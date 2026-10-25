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
        Schema::create('catatan_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->unique()->constrained('anak');
            $table->text('riwayat_medis')->nullable();
            $table->text('alergi')->nullable();
            $table->text('pantangan_makanan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan_kesehatan');
    }
};
