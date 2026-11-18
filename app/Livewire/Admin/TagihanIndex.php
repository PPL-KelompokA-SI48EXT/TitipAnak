<?php

namespace App\Livewire\Admin;

use App\Models\Tagihan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Tagihan')]
class TagihanIndex extends Component
{
    public ?int $penerbitanId = null;

    public string $penerbitanNama = '';

    public ?int $editingTagihanId = null;

    public string $editJumlah = '';

    public ?int $deletingTagihanId = null;

    public string $deletingTagihanNomor = '';

    public ?int $viewingPembayaranId = null;

    public string $filterPembayaran = '';

    public string $cariTagihan = '';

    public ?int $verifyingPembayaranId = null;

    public string $verifyingNomorTagihan = '';

    public float $verifyingJumlah = 0;

    public string $verifyingCatatan = '';

    public ?int $rejectingPembayaranId = null;

    public string $rejectingNomorTagihan = '';

    public string $rejectingCatatan = '';

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        return [
            'editJumlah' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:9999999999.99'],
        ];
    }

    public function mulaiEditTagihan(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $tagihan = $this->tagihanBelumLunas($id);

        $this->editingTagihanId = $tagihan->id;
        $this->editJumlah = number_format((float) $tagihan->jumlah, 2, '.', '');
    }

    public function batalEditTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $this->reset('editingTagihanId', 'editJumlah');
        $this->resetValidation('editJumlah');
    }

    public function simpanEditTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);
        abort_if($this->editingTagihanId === null, 404);

        $data = $this->validate($this->editRules());
        $tagihan = $this->tagihanBelumLunas($this->editingTagihanId);
        $tagihan->update(['jumlah' => $data['editJumlah']]);

        $this->batalEditTagihan();
        session()->flash('sukses', 'Nominal tagihan berhasil diperbarui.');
    }

    protected function tagihanBelumLunas(int $id): Tagihan
    {
        return Tagihan::query()
            ->whereKey($id)
            ->where('status', 'belum_lunas')
            ->firstOrFail();
    }

    public function bukaKonfirmasiHapusTagihan(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $tagihan = $this->tagihanDapatDihapus($id);

        $this->deletingTagihanId = $tagihan->id;
        $this->deletingTagihanNomor = $tagihan->nomor_tagihan;
    }

    public function batalHapusTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $this->reset('deletingTagihanId', 'deletingTagihanNomor');
    }

    public function hapusTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);
        abort_if($this->deletingTagihanId === null, 404);

        $tagihan = $this->tagihanDapatDihapus($this->deletingTagihanId);
        $tagihan->delete();

        $this->reset('deletingTagihanId', 'deletingTagihanNomor');
        session()->flash('sukses', 'Tagihan berhasil dihapus.');
    }

    protected function tagihanDapatDihapus(int $id): Tagihan
    {
        $tagihan = Tagihan::query()->whereKey($id)->where('status', 'belum_lunas')->firstOrFail();

        abort_if(DB::table('pembayaran')->where('tagihan_id', $tagihan->id)->exists(), 404);

        return $tagihan;
    }

    public function bukaKonfirmasiPenerbitan(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $pendaftaran = $this->pendaftaranDisetujui($id);

        $this->penerbitanId = $pendaftaran->id;
        $this->penerbitanNama = $pendaftaran->nama_orang_tua;
    }

    public function batalPenerbitan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $this->reset('penerbitanId', 'penerbitanNama');
    }

    public function terbitkan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);
        abort_if($this->penerbitanId === null, 404);

        $pendaftaran = $this->pendaftaranDisetujui($this->penerbitanId);

        Tagihan::create([
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_tagihan' => $this->nomorTagihan(),
            'jumlah' => $pendaftaran->harga,
            'status' => 'belum_lunas',
            'diterbitkan_pada' => now(),
            'jatuh_tempo' => today()->addDays(10),
        ]);

        $this->reset('penerbitanId', 'penerbitanNama');
        session()->flash('sukses', 'Tagihan berhasil diterbitkan.');
    }

    protected function pendaftaranDisetujui(int $id): object
    {
        $pendaftaran = DB::table('pendaftaran as p')
            ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
            ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
            ->where('p.id', $id)
            ->where('p.status', 'disetujui')
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'u.nama as nama_orang_tua',
                'pl.nama as nama_paket',
                'pl.harga',
            ])
            ->first();

        abort_if($pendaftaran === null, 404);

        return $pendaftaran;
    }

    protected function nomorTagihan(): string
    {
        do {
            $nomor = 'TGH-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Tagihan::withTrashed()->where('nomor_tagihan', $nomor)->exists());

        return $nomor;
    }

    public function lihatBukti(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $this->pembayaranValid($id);

        $this->viewingPembayaranId = $id;
    }

    public function tutupLihatBukti(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $this->reset('viewingPembayaranId');
    }

    public function unduhBukti(int $id): StreamedResponse
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $pembayaran = $this->pembayaranValid($id);

        abort_unless(Storage::disk('local')->exists($pembayaran->path_bukti), 404);

        $ext = pathinfo($pembayaran->path_bukti, PATHINFO_EXTENSION) ?: 'jpg';
        $namaFile = 'bukti-pembayaran-'.$pembayaran->nomor_tagihan.'.'.$ext;

        return Storage::disk('local')->download($pembayaran->path_bukti, $namaFile);
    }

    public function bukaKonfirmasiVerifikasi(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $pembayaran = $this->pembayaranMenungguVerifikasi($id);

        $this->verifyingPembayaranId = $pembayaran->id;
        $this->verifyingNomorTagihan = $pembayaran->nomor_tagihan;
        $this->verifyingJumlah = (float) $pembayaran->jumlah_bayar;
        $this->verifyingCatatan = '';
        $this->reset('viewingPembayaranId');
    }

    public function batalVerifikasi(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $this->reset('verifyingPembayaranId', 'verifyingNomorTagihan', 'verifyingJumlah', 'verifyingCatatan');
    }

    public function verifikasiPembayaran(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);
        abort_if($this->verifyingPembayaranId === null, 404);

        $pembayaran = $this->pembayaranMenungguVerifikasi($this->verifyingPembayaranId);

        DB::transaction(function () use ($pembayaran) {
            DB::table('pembayaran')
                ->where('id', $pembayaran->id)
                ->update([
                    'status' => 'terverifikasi',
                    'diverifikasi_oleh_id' => auth()->id(),
                    'diverifikasi_pada' => now(),
                    'catatan' => $this->verifyingCatatan ?: null,
                    'updated_at' => now(),
                ]);

            DB::table('tagihan')
                ->where('id', $pembayaran->tagihan_id)
                ->update([
                    'status' => 'lunas',
                    'lunas_pada' => now(),
                    'updated_at' => now(),
                ]);
        });

        $this->batalVerifikasi();
        session()->flash('sukses', 'Pembayaran berhasil diverifikasi. Status tagihan otomatis menjadi Lunas.');
    }

    public function bukaKonfirmasiTolak(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $pembayaran = $this->pembayaranMenungguVerifikasi($id);

        $this->rejectingPembayaranId = $pembayaran->id;
        $this->rejectingNomorTagihan = $pembayaran->nomor_tagihan;
        $this->rejectingCatatan = '';
        $this->resetValidation('rejectingCatatan');
        $this->reset('viewingPembayaranId');
    }

    public function batalTolak(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);

        $this->reset('rejectingPembayaranId', 'rejectingNomorTagihan', 'rejectingCatatan');
        $this->resetValidation('rejectingCatatan');
    }

    public function tolakPembayaran(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan') || auth()->user()?->can('verifikasi pembayaran'), 403);
        abort_if($this->rejectingPembayaranId === null, 404);

        $this->validate([
            'rejectingCatatan' => ['required', 'string', 'max:500'],
        ], [
            'rejectingCatatan.required' => 'Alasan penolakan wajib diisi.',
            'rejectingCatatan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $pembayaran = $this->pembayaranMenungguVerifikasi($this->rejectingPembayaranId);

        DB::table('pembayaran')
            ->where('id', $pembayaran->id)
            ->update([
                'status' => 'ditolak',
                'diverifikasi_oleh_id' => auth()->id(),
                'diverifikasi_pada' => now(),
                'catatan' => $this->rejectingCatatan,
                'updated_at' => now(),
            ]);

        $this->batalTolak();
        session()->flash('sukses', 'Pembayaran ditolak. Orang tua dapat mengunggah bukti pembayaran baru.');
    }

    protected function pembayaranMenungguVerifikasi(int $id): object
    {
        $pembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as t', 't.id', '=', 'pb.tagihan_id')
            ->where('pb.id', $id)
            ->where('pb.status', 'menunggu')
            ->where('t.status', 'belum_lunas')
            ->whereNull('t.deleted_at')
            ->select(['pb.id', 'pb.tagihan_id', 'pb.jumlah as jumlah_bayar', 't.nomor_tagihan', 't.jumlah as jumlah_tagihan'])
            ->first();

        abort_if($pembayaran === null, 404);

        return $pembayaran;
    }

    protected function pembayaranValid(int $id): object
    {
        $pembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as t', 't.id', '=', 'pb.tagihan_id')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->where('pb.id', $id)
            ->whereNull('t.deleted_at')
            ->whereNull('p.deleted_at')
            ->select(['pb.id', 'pb.path_bukti', 't.nomor_tagihan'])
            ->first();

        abort_if($pembayaran === null, 404);

        return $pembayaran;
    }

    protected function getDetailPembayaran(int $id): ?object
    {
        $pembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as t', 't.id', '=', 'pb.tagihan_id')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
            ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
            ->where('pb.id', $id)
            ->whereNull('t.deleted_at')
            ->whereNull('p.deleted_at')
            ->select([
                'pb.id',
                'pb.tagihan_id',
                'pb.path_bukti',
                'pb.jumlah as jumlah_bayar',
                'pb.status as status_pembayaran',
                'pb.diajukan_pada',
                'pb.catatan',
                't.nomor_tagihan',
                't.jumlah as jumlah_tagihan',
                't.status as status_tagihan',
                'u.nama as nama_orang_tua',
                'u.email as email_orang_tua',
                'pl.nama as nama_paket',
            ])
            ->first();

        if ($pembayaran === null) {
            return null;
        }

        $exists = Storage::disk('local')->exists($pembayaran->path_bukti);
        $mime = $exists ? Storage::disk('local')->mimeType($pembayaran->path_bukti) : null;
        $isImage = $mime && str_starts_with($mime, 'image/');
        $isPdf = $mime === 'application/pdf' || str_ends_with(strtolower($pembayaran->path_bukti), '.pdf');
        $previewUrl = null;

        if ($exists && $isImage) {
            $content = Storage::disk('local')->get($pembayaran->path_bukti);
            $previewUrl = 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return (object) [
            ...((array) $pembayaran),
            'file_exists' => $exists,
            'is_image' => $isImage,
            'is_pdf' => $isPdf,
            'preview_url' => $previewUrl,
            'mime' => $mime,
        ];
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $queryPembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as t', 't.id', '=', 'pb.tagihan_id')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
            ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
            ->whereNull('t.deleted_at')
            ->whereNull('p.deleted_at')
            ->select([
                'pb.id',
                'pb.tagihan_id',
                'pb.path_bukti',
                'pb.jumlah as jumlah_bayar',
                'pb.status as status_pembayaran',
                'pb.diajukan_pada',
                'pb.catatan',
                't.nomor_tagihan',
                't.jumlah as jumlah_tagihan',
                't.status as status_tagihan',
                'u.nama as nama_orang_tua',
                'pl.nama as nama_paket',
            ])
            ->orderByDesc('pb.diajukan_pada')
            ->orderByDesc('pb.id');

        if ($this->filterPembayaran !== '') {
            $queryPembayaran->where('pb.status', $this->filterPembayaran);
        }

        $queryTagihan = DB::table('tagihan as t')
            ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
            ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
            ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
            ->whereNull('t.deleted_at')
            ->select([
                't.id',
                't.nomor_tagihan',
                't.jumlah',
                't.status',
                't.diterbitkan_pada',
                't.jatuh_tempo',
                'p.id as pendaftaran_id',
                'u.nama as nama_orang_tua',
                'pl.nama as nama_paket',
            ])
            ->selectSub(
                DB::table('pembayaran')
                    ->selectRaw('count(*)')
                    ->whereColumn('pembayaran.tagihan_id', 't.id'),
                'jumlah_pembayaran'
            )
            ->orderByDesc('t.diterbitkan_pada')
            ->orderByDesc('t.id');

        if ($this->cariTagihan !== '') {
            $cari = '%'.trim($this->cariTagihan).'%';
            $queryTagihan->where(function ($q) use ($cari) {
                $q->where('t.nomor_tagihan', 'like', $cari)
                    ->orWhere('u.nama', 'like', $cari);
            });
        }

        return view('livewire.admin.tagihan-index', [
            'pendaftaran' => DB::table('pendaftaran as p')
                ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
                ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
                ->where('p.status', 'disetujui')
                ->whereNull('p.deleted_at')
                ->select([
                    'p.id',
                    'u.nama as nama_orang_tua',
                    'pl.nama as nama_paket',
                    'pl.harga',
                ])
                ->orderBy('p.id')
                ->get(),
            'tagihan' => $queryTagihan->get(),
            'daftarPembayaran' => $queryPembayaran->get(),
            'detailPembayaran' => $this->viewingPembayaranId ? $this->getDetailPembayaran($this->viewingPembayaranId) : null,
        ]);
    }
}
