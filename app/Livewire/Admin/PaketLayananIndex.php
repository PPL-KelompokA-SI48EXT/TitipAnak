<?php

namespace App\Livewire\Admin;

use App\Models\PaketLayanan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paket Layanan')]
class PaketLayananIndex extends Component
{
    public ?int $editingId = null;

    public string $editDeskripsi = '';

    public string $editHarga = '';

    public bool $editAktif = true;

    public ?int $deletingId = null;

    public ?string $deletingName = null;

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        return [
            'editDeskripsi' => ['nullable', 'string'],
            'editHarga' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:9999999999.99'],
            'editAktif' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'editDeskripsi' => 'deskripsi',
            'editHarga' => 'tarif',
            'editAktif' => 'status aktif',
        ];
    }

    public function mulaiEdit(int $paketId): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $paket = PaketLayanan::findOrFail($paketId);

        $this->editingId = $paket->id;
        $this->editDeskripsi = $paket->deskripsi ?? '';
        $this->editHarga = (string) $paket->harga;
        $this->editAktif = $paket->aktif;
        $this->resetValidation();
    }

    public function simpanEdit(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);
        abort_if($this->editingId === null, 404);

        $data = $this->validate($this->editRules());
        $paket = PaketLayanan::findOrFail($this->editingId);

        $paket->update([
            'deskripsi' => $data['editDeskripsi'] !== '' ? $data['editDeskripsi'] : null,
            'harga' => $data['editHarga'],
            'aktif' => $data['editAktif'],
        ]);

        $this->batalEdit();
        session()->flash('sukses', 'Paket layanan berhasil diperbarui.');
    }

    public function batalEdit(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $this->reset('editingId', 'editDeskripsi', 'editHarga');
        $this->editAktif = true;
        $this->resetValidation();
    }

    public function toggleAktif(int $paketId): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $paket = PaketLayanan::findOrFail($paketId);
        $paket->update(['aktif' => ! $paket->aktif]);

        session()->flash('sukses', $paket->aktif
            ? 'Paket layanan diaktifkan.'
            : 'Paket layanan dinonaktifkan.');
    }

    public function bukaKonfirmasiHapus(int $paketId): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $paket = PaketLayanan::findOrFail($paketId);
        $this->deletingId = $paket->id;
        $this->deletingName = $paket->nama;
        $this->resetValidation('hapus');
    }

    public function batalHapus(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $this->reset('deletingId', 'deletingName');
        $this->resetValidation('hapus');
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);
        abort_if($this->deletingId === null, 404);

        $paket = PaketLayanan::find($this->deletingId);

        if ($paket === null) {
            $this->batalHapus();

            return;
        }

        $id = $paket->id;

        if (DB::table('pendaftaran')->where('paket_layanan_id', $id)->exists()) {
            $this->addError('hapus', 'Paket layanan tidak dapat dihapus karena sudah digunakan oleh pendaftaran.');

            return;
        }

        try {
            $paket->delete();
        } catch (QueryException $exception) {
            if (! $this->isRestrictiveForeignKeyFailure($exception)) {
                throw $exception;
            }

            $this->addError('hapus', 'Paket layanan tidak dapat dihapus karena baru saja digunakan oleh pendaftaran.');

            return;
        }

        $this->reset('deletingId', 'deletingName');

        if ($this->editingId === $id) {
            $this->batalEdit();
        }

        $this->resetValidation('hapus');
        session()->flash('sukses', 'Paket layanan berhasil dihapus permanen.');
    }

    private function isRestrictiveForeignKeyFailure(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23503'], true)
            && str_contains(strtolower($exception->getMessage()), 'foreign key');
    }

    public function render(): View
    {
        return view('livewire.admin.paket-layanan-index', [
            'paketLayanan' => PaketLayanan::query()
                ->orderByDesc('aktif')
                ->orderBy('nama')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
