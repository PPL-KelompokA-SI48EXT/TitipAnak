<?php

namespace App\Livewire\Pengasuh;

use App\Models\IzinPenjemputan;
use App\Support\FotoIdentitas;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Izin Penjemputan')]
class IzinPenjemputanIndex extends Component
{
    public const FILTER = ['menunggu', 'disetujui', 'ditolak', 'dibatalkan', 'semua'];

    #[Url(as: 'status')]
    public string $filterStatus = 'menunggu';

    public ?int $viewingFotoId = null;

    public ?string $previewFotoUrl = null;

    public function updatedFilterStatus(): void
    {
        if (! in_array($this->filterStatus, self::FILTER, true)) {
            $this->filterStatus = 'menunggu';
        }
    }

    public function setujui(int $id): void
    {
        $this->validasi($id, IzinPenjemputan::STATUS_DISETUJUI);
    }

    public function tolak(int $id): void
    {
        $this->validasi($id, IzinPenjemputan::STATUS_DITOLAK);
    }

    public function lihatFoto(int $id): void
    {
        $this->izinkan();

        $izin = IzinPenjemputan::query()->findOrFail($id);

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

        $izin = IzinPenjemputan::query()
            ->with(['anak:id,nama_lengkap', 'diajukanOleh:id,nama', 'divalidasiOleh:id,nama'])
            ->when(
                in_array($this->filterStatus, ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'], true),
                fn ($query) => $query->where('status', $this->filterStatus)
            )
            ->orderBy('tanggal_jemput')
            ->orderBy('id')
            ->get();

        return view('livewire.pengasuh.izin-penjemputan-index', [
            'izin' => $izin,
            'jumlah' => IzinPenjemputan::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'viewingIzin' => $this->viewingFotoId ? $izin->firstWhere('id', $this->viewingFotoId) : null,
        ]);
    }

    private function validasi(int $id, string $status): void
    {
        $this->izinkan();

        $izin = IzinPenjemputan::query()->with('anak:id,nama_lengkap')->findOrFail($id);

        // Status bisa berubah sejak daftar dibuka (mis. orang tua membatalkan), jadi periksa lagi.
        if ($izin->status !== IzinPenjemputan::STATUS_MENUNGGU) {
            session()->flash('galat', "Izin untuk {$izin->anak->nama_lengkap} sudah berstatus {$izin->status}.");

            return;
        }

        if ($izin->tanggal_jemput->lt(today())) {
            session()->flash('galat', 'Tanggal jemput izin ini sudah lewat, jadi tidak bisa divalidasi.');

            return;
        }

        $izin->update([
            'status' => $status,
            'divalidasi_oleh_id' => auth()->id(),
            'divalidasi_pada' => now(),
        ]);

        $kata = $status === IzinPenjemputan::STATUS_DISETUJUI ? 'disetujui' : 'ditolak';
        session()->flash('sukses', "Izin penjemputan {$izin->anak->nama_lengkap} {$kata}.");
    }

    private function izinkan(): void
    {
        abort_unless(auth()->user()?->can('validasi izin penjemputan'), 403);
    }
}
