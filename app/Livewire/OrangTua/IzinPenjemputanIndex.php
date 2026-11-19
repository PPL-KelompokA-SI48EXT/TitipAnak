<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\IzinPenjemputan;
use App\Models\Kehadiran;
use App\Support\FotoIdentitas;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Izin Penjemputan')]
class IzinPenjemputanIndex extends Component
{
    use WithFileUploads;

    public bool $bukaModalForm = false;

    public string $anak_id = '';

    public string $nama_penjemput = '';

    public string $tanggal_jemput = '';

    public string $alasan = '';

    public $foto_identitas;

    public ?int $konfirmasiId = null;

    /** "batalkan" atau "hapus". */
    public string $konfirmasiAksi = '';

    public ?int $viewingFotoId = null;

    public ?string $previewFotoUrl = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'anak_id' => [
                'required',
                'integer',
                Rule::exists('anak', 'id')
                    ->where('orang_tua_id', auth()->id())
                    ->where('status', 'aktif')
                    ->whereNull('deleted_at'),
            ],
            'nama_penjemput' => ['required', 'string', 'max:100'],
            'tanggal_jemput' => ['required', 'date', 'after_or_equal:today'],
            'alasan' => ['required', 'string', 'max:1000'],
            'foto_identitas' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'anak_id' => 'anak',
            'nama_penjemput' => 'nama penjemput',
            'tanggal_jemput' => 'tanggal jemput',
            'alasan' => 'alasan',
            'foto_identitas' => 'foto KTP',
        ];
    }

    public function bukaForm(): void
    {
        $this->izinkan();

        $this->reset('anak_id', 'nama_penjemput', 'alasan', 'foto_identitas');
        $this->tanggal_jemput = today()->toDateString();
        $this->resetErrorBag();
        $this->bukaModalForm = true;
    }

    public function tutupForm(): void
    {
        $this->reset('bukaModalForm', 'anak_id', 'nama_penjemput', 'tanggal_jemput', 'alasan', 'foto_identitas');
        $this->resetErrorBag();
    }

    public function simpan(): void
    {
        $this->izinkan();

        $data = $this->validate();
        $path = $this->foto_identitas->store('izin-penjemputan', 'local');

        IzinPenjemputan::create([
            'anak_id' => (int) $data['anak_id'],
            'diajukan_oleh_id' => auth()->id(),
            'nama_penjemput' => $data['nama_penjemput'],
            'path_foto_identitas' => $path,
            'alasan' => $data['alasan'],
            'tanggal_jemput' => $data['tanggal_jemput'],
            'status' => IzinPenjemputan::STATUS_MENUNGGU,
        ]);

        $this->tutupForm();
        session()->flash('sukses', 'Izin penjemputan berhasil diajukan dan menunggu validasi pengasuh.');
    }

    public function bukaKonfirmasi(int $id, string $aksi): void
    {
        $this->izinkan();
        abort_unless(in_array($aksi, ['batalkan', 'hapus'], true), 422);

        $this->izinMilikUser($id);

        $this->konfirmasiId = $id;
        $this->konfirmasiAksi = $aksi;
    }

    public function tutupKonfirmasi(): void
    {
        $this->reset('konfirmasiId', 'konfirmasiAksi');
    }

    public function jalankanKonfirmasi(): void
    {
        $this->izinkan();
        abort_if($this->konfirmasiId === null, 404);

        $izin = $this->izinMilikUser($this->konfirmasiId);
        $aksi = $this->konfirmasiAksi;
        $this->tutupKonfirmasi();

        if ($this->sudahDipakai($izin)) {
            session()->flash('galat', 'Izin ini sudah dipakai saat anak dijemput, jadi tidak bisa diubah lagi.');

            return;
        }

        if ($aksi === 'batalkan') {
            if (! in_array($izin->status, [IzinPenjemputan::STATUS_MENUNGGU, IzinPenjemputan::STATUS_DISETUJUI], true)) {
                session()->flash('galat', 'Hanya izin yang menunggu atau disetujui yang bisa dibatalkan.');

                return;
            }

            $izin->update(['status' => IzinPenjemputan::STATUS_DIBATALKAN]);
            session()->flash('sukses', 'Izin penjemputan dibatalkan.');

            return;
        }

        if (! in_array($izin->status, [IzinPenjemputan::STATUS_DIBATALKAN, IzinPenjemputan::STATUS_DITOLAK], true)) {
            session()->flash('galat', 'Batalkan izin terlebih dahulu sebelum menghapusnya.');

            return;
        }

        FotoIdentitas::hapus($izin->path_foto_identitas);
        $izin->delete();
        session()->flash('sukses', 'Izin penjemputan dihapus.');
    }

    public function lihatFoto(int $id): void
    {
        $this->izinkan();

        $izin = $this->izinMilikUser($id);

        $this->viewingFotoId = $izin->id;
        $this->previewFotoUrl = FotoIdentitas::sumber($izin->path_foto_identitas);
    }

    public function tutupFoto(): void
    {
        $this->reset('viewingFotoId', 'previewFotoUrl');
    }

    public function render(): View
    {
        $this->izinkan();

        $anak = Anak::query()
            ->where('orang_tua_id', auth()->id())
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap']);

        $izin = IzinPenjemputan::query()
            ->whereHas('anak', fn ($query) => $query->where('orang_tua_id', auth()->id()))
            ->with('anak:id,nama_lengkap')
            ->withExists(['kehadiran as sudah_dipakai'])
            ->orderByDesc('tanggal_jemput')
            ->orderByDesc('id')
            ->get();

        return view('livewire.orang-tua.izin-penjemputan-index', [
            'anak' => $anak,
            'izin' => $izin,
            'viewingNama' => $this->viewingFotoId ? $izin->firstWhere('id', $this->viewingFotoId)?->nama_penjemput : null,
        ]);
    }

    private function izinkan(): void
    {
        abort_unless(auth()->user()?->can('ajukan izin penjemputan'), 403);
    }

    private function izinMilikUser(int $id): IzinPenjemputan
    {
        return IzinPenjemputan::query()
            ->whereKey($id)
            ->whereHas('anak', fn ($query) => $query->where('orang_tua_id', auth()->id()))
            ->firstOrFail();
    }

    private function sudahDipakai(IzinPenjemputan $izin): bool
    {
        return Kehadiran::query()->where('izin_penjemputan_id', $izin->id)->exists();
    }
}
