<?php

namespace App\Livewire\Admin;

use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tambah Kelompok Usia')]
class KelompokUsiaBuat extends Component
{
    public string $nama = '';

    public string $usia_min_bulan = '12';

    public string $usia_maks_bulan = '36';

    public string $kapasitas = '10';

    public string $deskripsi = '';

    protected function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelompok_usia', 'nama'),
            ],
            'usia_min_bulan' => ['required', 'integer', 'min:0'],
            'usia_maks_bulan' => [
                'required',
                'integer',
                'gte:usia_min_bulan',
            ],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama kelompok usia',
            'usia_min_bulan' => 'usia minimum',
            'usia_maks_bulan' => 'usia maksimum',
            'kapasitas' => 'kapasitas',
            'deskripsi' => 'deskripsi',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola master data'), 403);

        $data = $this->validate();

        KelompokUsia::create($data);

        $this->reset();
        $this->usia_min_bulan = '12';
        $this->usia_maks_bulan = '36';
        $this->kapasitas = '10';

        session()->flash('sukses', 'Kelompok usia berhasil ditambahkan.');
    }

    public function render(): View
    {
        return view('livewire.admin.kelompok-usia-buat');
    }
}
