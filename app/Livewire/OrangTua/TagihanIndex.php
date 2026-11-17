<?php

namespace App\Livewire\OrangTua;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Tagihan Saya')]
class TagihanIndex extends Component
{
    use WithFileUploads;

    public ?int $uploadingTagihanId = null;

    public string $uploadingTagihanNomor = '';

    public $buktiPembayaran;

    /**
     * @return array<string, mixed>
     */
    protected function uploadRules(): array
    {
        return [
            'buktiPembayaran' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function bukaFormUpload(int $id): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);

        $tagihan = $this->tagihanDapatDiunggah($id);

        $this->uploadingTagihanId = $tagihan->id;
        $this->uploadingTagihanNomor = $tagihan->nomor_tagihan;
        $this->reset('buktiPembayaran');
        $this->resetValidation();
    }

    public function batalUpload(): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);

        $this->reset('uploadingTagihanId', 'uploadingTagihanNomor', 'buktiPembayaran');
        $this->resetValidation();
    }

    public function unggahBukti(): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);
        abort_if($this->uploadingTagihanId === null, 404);

        $data = $this->validate($this->uploadRules());
        $tagihan = $this->tagihanDapatDiunggah($this->uploadingTagihanId);
        $path = $data['buktiPembayaran']->store('pembayaran-bukti', 'local');

        DB::table('pembayaran')->insert([
            'tagihan_id' => $tagihan->id,
            'diajukan_oleh_id' => auth()->id(),
            'path_bukti' => $path,
            'jumlah' => $tagihan->jumlah,
            'status' => 'menunggu',
            'diajukan_pada' => now(),
            'diverifikasi_oleh_id' => null,
            'diverifikasi_pada' => null,
            'catatan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->batalUpload();
        session()->flash('sukses', 'Bukti pembayaran berhasil diunggah.');
    }

    protected function tagihanDapatDiunggah(int $id): object
    {
        $tagihan = DB::table('tagihan as t')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->where('t.id', $id)
            ->where('p.orang_tua_id', auth()->id())
            ->where('p.deleted_at', null)
            ->where('t.deleted_at', null)
            ->where('t.status', 'belum_lunas')
            ->select(['t.id', 't.nomor_tagihan', 't.jumlah'])
            ->first();

        abort_if($tagihan === null, 404);

        abort_if(
            DB::table('pembayaran')
                ->where('tagihan_id', $tagihan->id)
                ->whereIn('status', ['menunggu', 'terverifikasi'])
                ->exists(),
            404
        );

        return $tagihan;
    }

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
                ->selectSub(
                    DB::table('pembayaran')
                        ->selectRaw('count(*)')
                        ->whereColumn('pembayaran.tagihan_id', 't.id')
                        ->whereIn('status', ['menunggu', 'terverifikasi']),
                    'jumlah_pembayaran_aktif'
                )
                ->selectSub(
                    DB::table('pembayaran')
                        ->select('status')
                        ->whereColumn('pembayaran.tagihan_id', 't.id')
                        ->orderByDesc('id')
                        ->limit(1),
                    'status_pembayaran_terakhir'
                )
                ->orderByDesc('t.diterbitkan_pada')
                ->orderByDesc('t.id')
                ->get(),
        ]);
    }
}
