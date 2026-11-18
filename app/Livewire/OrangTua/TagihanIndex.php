<?php

namespace App\Livewire\OrangTua;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public ?int $deletingPembayaranId = null;

    public string $deletingPembayaranNomor = '';

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

    public function bukaKonfirmasiHapusPembayaran(int $id): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);

        $pembayaran = $this->pembayaranDapatDihapus($id);

        $this->deletingPembayaranId = $pembayaran->id;
        $this->deletingPembayaranNomor = $pembayaran->nomor_tagihan;
    }

    public function batalHapusPembayaran(): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);

        $this->reset('deletingPembayaranId', 'deletingPembayaranNomor');
    }

    public function hapusPembayaran(): void
    {
        abort_unless(auth()->user()?->can('bayar tagihan'), 403);
        abort_if($this->deletingPembayaranId === null, 404);

        $pembayaran = $this->pembayaranDapatDihapus($this->deletingPembayaranId);
        Storage::disk('local')->delete($pembayaran->path_bukti);
        DB::table('pembayaran')->where('id', $pembayaran->id)->delete();

        $this->reset('deletingPembayaranId', 'deletingPembayaranNomor');
        session()->flash('sukses', 'Bukti pembayaran berhasil dihapus.');
    }

    protected function pembayaranDapatDihapus(int $id): object
    {
        $pembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as t', 't.id', '=', 'pb.tagihan_id')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->where('pb.id', $id)
            ->where('pb.diajukan_oleh_id', auth()->id())
            ->where('pb.status', 'menunggu')
            ->where('t.status', 'belum_lunas')
            ->whereNull('t.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.orang_tua_id', auth()->id())
            ->select(['pb.id', 'pb.path_bukti', 't.nomor_tagihan'])
            ->first();

        abort_if($pembayaran === null, 404);

        return $pembayaran;
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
                        ->select('id')
                        ->whereColumn('pembayaran.tagihan_id', 't.id')
                        ->where('status', 'menunggu')
                        ->orderByDesc('id')
                        ->limit(1),
                    'pembayaran_menunggu_id'
                )
                ->selectSub(
                    DB::table('pembayaran')
                        ->select('status')
                        ->whereColumn('pembayaran.tagihan_id', 't.id')
                        ->orderByDesc('id')
                        ->limit(1),
                    'status_pembayaran_terakhir'
                )
                ->selectSub(
                    DB::table('pembayaran')
                        ->select('catatan')
                        ->whereColumn('pembayaran.tagihan_id', 't.id')
                        ->where('status', 'ditolak')
                        ->orderByDesc('id')
                        ->limit(1),
                    'catatan_penolakan_terakhir'
                )
                ->orderByDesc('t.diterbitkan_pada')
                ->orderByDesc('t.id')
                ->get(),
        ]);
    }
}
