<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Penjemput Anak')]
class PenjemputIndex extends Component
{
    use WithFileUploads;

    public string $anak_id = '';

    public string $nama_lengkap = '';

    public string $hubungan = '';

    public string $no_hp = '';

    public $foto_identitas;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'anak_id' => [
                'required',
                'integer',
                Rule::exists('anak', 'id')->where(fn ($query) => $query->where('orang_tua_id', auth()->id())),
            ],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'hubungan' => ['required', 'string', 'max:50'],
            'no_hp' => ['required', 'string', 'max:20'],
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
            'nama_lengkap' => 'nama lengkap',
            'hubungan' => 'hubungan',
            'no_hp' => 'nomor HP',
            'foto_identitas' => 'foto identitas',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola penjemput'), 403);

        $data = $this->validate();
        $path = $this->foto_identitas->store('penjemput-identitas', 'local');

        PenjemputTerdaftar::create([
            'anak_id' => $data['anak_id'],
            'nama_lengkap' => $data['nama_lengkap'],
            'hubungan' => $data['hubungan'],
            'no_hp' => $data['no_hp'],
            'path_foto_identitas' => $path,
            'aktif' => true,
        ]);

        $this->reset('anak_id', 'nama_lengkap', 'hubungan', 'no_hp', 'foto_identitas');
        session()->flash('sukses', 'Penjemput berhasil ditambahkan.');
    }

    public function render(): View
    {
        $anak = Anak::query()
            ->where('orang_tua_id', auth()->id())
            ->orderBy('nama_lengkap')
            ->orderBy('id')
            ->get(['id', 'nama_lengkap']);

        return view('livewire.orang-tua.penjemput-index', [
            'anak' => $anak,
            'penjemput' => PenjemputTerdaftar::query()
                ->whereIn('anak_id', $anak->pluck('id'))
                ->where('aktif', true)
                ->with('anak:id,nama_lengkap')
                ->orderBy('nama_lengkap')
                ->get(),
        ]);
    }
}
