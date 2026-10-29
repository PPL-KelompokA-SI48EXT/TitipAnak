<?php

namespace App\Livewire\Admin;

use App\Models\PaketLayanan;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paket Layanan')]
class PaketLayananIndex extends Component
{
    public string $nama = '';

    public string $deskripsi = '';

    public string $harga = '';

    public ?int $editingId = null;

    public string $editDeskripsi = '';

    public string $editHarga = '';

    public bool $editAktif = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('paket_layanan', 'nama')],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:9999999999.99'],
        ];
    }

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
            'nama' => 'nama paket',
            'deskripsi' => 'deskripsi',
            'harga' => 'tarif',
            'editDeskripsi' => 'deskripsi',
            'editHarga' => 'tarif',
            'editAktif' => 'status aktif',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $data = $this->validate();
        $data['deskripsi'] = $data['deskripsi'] !== '' ? $data['deskripsi'] : null;
        $data['aktif'] = true;

        PaketLayanan::create($data);

        $this->reset('nama', 'deskripsi', 'harga');
        session()->flash('sukses', 'Paket layanan berhasil ditambahkan.');
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
