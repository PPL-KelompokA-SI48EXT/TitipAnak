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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan');
            $table->foreignId('diajukan_oleh_id')->constrained('pengguna');
            $table->string('path_bukti'); // jpg / png / pdf, disk private
            $table->decimal('jumlah', 12, 2);
            $table->string('status', 20)->default('menunggu'); // menunggu / terverifikasi / ditolak
            $table->timestamp('diajukan_pada');
            $table->foreignId('diverifikasi_oleh_id')->nullable()->constrained('pengguna');
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['tagihan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
