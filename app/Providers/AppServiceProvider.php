<?php

namespace App\Providers;

use App\Models\Anak;
use App\Models\CatatanKesehatan;
use App\Models\PenjemputTerdaftar;
use App\Observers\KelengkapanPendaftaranObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // PBI-11d: pendaftaran Draft otomatis diajukan begitu data anak, kesehatan, dan penjemput lengkap.
        Anak::observe(KelengkapanPendaftaranObserver::class);
        CatatanKesehatan::observe(KelengkapanPendaftaranObserver::class);
        PenjemputTerdaftar::observe(KelengkapanPendaftaranObserver::class);
    }
}
