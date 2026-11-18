<?php

namespace App\Livewire\OrangTua;

use App\Models\PaketLayanan;
use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11a: Orang tua membuat pendaftaran dan memilih paket layanan. Status awal Draft.
 */
#[Title('Buat Pendaftaran')]
class PendaftaranBuat extends Component
{
    public string $paket_layanan_id = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'paket_layanan_id' => [
                'required',
                'integer',
                fn (string $attribute, mixed $value, \Closure $fail) => PaketLayanan::where('aktif', true)->whereKey($value)->exists()
                    ?: $fail('Pilih paket layanan yang tersedia.'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['paket_layanan_id' => 'paket layanan'];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola pendaftaran sendiri'), 403);

        $data = $this->validate();

        Pendaftaran::create([
            'orang_tua_id' => auth()->id(),
            'paket_layanan_id' => $data['paket_layanan_id'],
            'status' => 'draft',
        ]);

        session()->flash('sukses', 'Pendaftaran disimpan sebagai Draft. Lengkapi data anak untuk mengajukannya.');

        $this->redirectRoute('orang-tua.pendaftaran.index');
    }

    public function render(): View
    {
        return view('livewire.orang-tua.pendaftaran-buat', [
            'paket' => PaketLayanan::where('aktif', true)->orderBy('harga')->get(['id', 'nama', 'deskripsi', 'harga']),
        ]);
    }
}
