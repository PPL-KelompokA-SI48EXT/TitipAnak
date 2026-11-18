<?php

namespace App\Livewire\Pengasuh;

use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daftar Penjemput Terdaftar')]
class PenjemputIndex extends Component
{
    public ?int $viewingFotoId = null;

    public ?string $previewFotoUrl = null;

    public ?string $previewFotoNama = null;

    public ?string $previewFotoAnak = null;

    public ?string $previewFotoHubungan = null;

    public function lihatFoto(int $id): void
    {
        abort_unless(auth()->user()?->can('validasi izin penjemputan'), 403);

        $penjemput = PenjemputTerdaftar::query()
            ->where('aktif', true)
            ->whereKey($id)
            ->with('anak:id,nama_lengkap')
            ->firstOrFail();

        $this->viewingFotoId = $penjemput->id;
        $this->previewFotoNama = $penjemput->nama_lengkap;
        $this->previewFotoAnak = $penjemput->anak?->nama_lengkap ?? '—';
        $this->previewFotoHubungan = $penjemput->hubungan;
        $this->previewFotoUrl = null;

        if ($penjemput->path_foto_identitas && Storage::disk('local')->exists($penjemput->path_foto_identitas)) {
            $mime = Storage::disk('local')->mimeType($penjemput->path_foto_identitas);
            $data = base64_encode(Storage::disk('local')->get($penjemput->path_foto_identitas));
            $this->previewFotoUrl = 'data:'.$mime.';base64,'.$data;
        }
    }

    public function tutupLihatFoto(): void
    {
        $this->reset('viewingFotoId', 'previewFotoUrl', 'previewFotoNama', 'previewFotoAnak', 'previewFotoHubungan');
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('validasi izin penjemputan'), 403);

        return view('livewire.pengasuh.penjemput-index', [
            'penjemput' => PenjemputTerdaftar::query()
                ->where('aktif', true)
                ->whereHas('anak', fn ($query) => $query->where('status', 'aktif'))
                ->with('anak:id,nama_lengkap')
                ->orderBy('anak_id')
                ->orderBy('nama_lengkap')
                ->get(),
        ]);
    }
}
