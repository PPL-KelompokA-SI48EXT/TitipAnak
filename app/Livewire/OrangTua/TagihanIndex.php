<?php

namespace App\Livewire\OrangTua;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tagihan Saya')]
class TagihanIndex extends Component
{
    public function render(): View
    {
        return view('livewire.orang-tua.tagihan-index', [
            'tagihan' => DB::table('tagihan as t')
                ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
                ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
                ->where('p.orang_tua_id', auth()->id())
                ->whereNull('p.deleted_at')
                ->whereNull('t.deleted_at')
                ->select([
                    't.id',
                    't.nomor_tagihan',
                    't.jumlah',
                    't.status',
                    't.diterbitkan_pada',
                    't.jatuh_tempo',
                    'pl.nama as nama_paket',
                ])
                ->orderByDesc('t.diterbitkan_pada')
                ->orderByDesc('t.id')
                ->get(),
        ]);
    }
}
