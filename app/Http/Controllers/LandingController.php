<?php

namespace App\Http\Controllers;

use App\Models\KelompokUsia;
use App\Models\PaketLayanan;
use Illuminate\Contracts\View\View;

/**
 * Halaman depan (landing page) yang bisa dibuka siapa saja.
 */
class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'kelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama', 'usia_min_bulan', 'usia_maks_bulan', 'deskripsi']),
            'paket' => PaketLayanan::where('aktif', true)->orderBy('harga', 'desc')->orderBy('nama')->get(['id', 'nama', 'deskripsi', 'harga']),
        ]);
    }
}
