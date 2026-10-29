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
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama paket',
            'deskripsi' => 'deskripsi',
            'harga' => 'tarif',
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
