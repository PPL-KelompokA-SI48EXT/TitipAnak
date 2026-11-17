<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Penjemput Anak')]
class PenjemputIndex extends Component
{
    use WithFileUploads;

    public string $anak_id = '';

    public string $edit_id = '';

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
            'foto_identitas' => [$this->edit_id === '' ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
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

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola penjemput'), 403);

        $penjemput = $this->penjemputMilikUser($id);

        $this->edit_id = (string) $penjemput->id;
        $this->anak_id = (string) $penjemput->anak_id;
        $this->nama_lengkap = $penjemput->nama_lengkap;
        $this->hubungan = $penjemput->hubungan;
        $this->no_hp = $penjemput->no_hp;
        $this->foto_identitas = null;
    }

    public function perbarui(): void
    {
        abort_unless(auth()->user()?->can('kelola penjemput'), 403);

        $penjemput = $this->penjemputMilikUser((int) $this->edit_id);
        $data = $this->validate();

        $penjemput->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'hubungan' => $data['hubungan'],
            'no_hp' => $data['no_hp'],
        ]);

        if ($this->foto_identitas) {
            $path = $this->foto_identitas->store('penjemput-identitas', 'local');
            if ($penjemput->path_foto_identitas) {
                Storage::disk('local')->delete($penjemput->path_foto_identitas);
            }
            $penjemput->update(['path_foto_identitas' => $path]);
        }

        $this->reset('edit_id', 'anak_id', 'nama_lengkap', 'hubungan', 'no_hp', 'foto_identitas');
        session()->flash('sukses', 'Penjemput berhasil diperbarui.');
    }

    public function batalEdit(): void
    {
        $this->reset('edit_id', 'anak_id', 'nama_lengkap', 'hubungan', 'no_hp', 'foto_identitas');
    }

    protected function penjemputMilikUser(int $id): PenjemputTerdaftar
    {
        return PenjemputTerdaftar::query()
            ->whereKey($id)
            ->where('aktif', true)
            ->whereHas('anak', fn ($query) => $query->where('orang_tua_id', auth()->id()))
            ->firstOrFail();
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

        $this->reset('edit_id', 'anak_id', 'nama_lengkap', 'hubungan', 'no_hp', 'foto_identitas');
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
