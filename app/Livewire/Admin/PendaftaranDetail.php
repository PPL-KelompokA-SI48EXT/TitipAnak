<?php

namespace App\Livewire\Admin;

use App\Models\Anak;
use App\Models\CatatanKesehatan;
use App\Models\KelompokUsia;
use App\Models\Pendaftaran;
use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11e: Admin memeriksa satu pendaftaran yang masuk: data orang tua, paket, anak,
 * catatan kesehatan, dan penjemput terdaftar.
 *
 * PBI-11f: dari halaman yang sama, Admin menyetujui pendaftaran yang Menunggu Verifikasi
 * sekaligus menetapkan kelompok usia anak, sehingga anak berstatus aktif.
 *
 * Draft belum diteruskan ke Admin, jadi diperlakukan seolah tidak ada (404).
 */
#[Title('Periksa Pendaftaran')]
class PendaftaranDetail extends Component
{
    #[Locked]
    public int $pendaftaranId;

    /** PBI-11f: kelompok usia yang dipilih Admin saat menyetujui. */
    public string $kelompok_usia_id = '';

    public function mount(int $pendaftaran): void
    {
        $pendaftaran = $this->pendaftaranMasuk($pendaftaran);

        $this->pendaftaranId = $pendaftaran->id;

        if ($pendaftaran->bisaDisetujui() && $pendaftaran->anak) {
            $kelompokUsia = $this->daftarKelompokUsia();
            $saran = $kelompokUsia->find($this->saranKelompokUsia($pendaftaran->anak, $kelompokUsia));

            // Kelompok yang sudah penuh tidak dipilihkan; Admin memilih sendiri.
            if ($saran && $saran->anak_count < $saran->kapasitas) {
                $this->kelompok_usia_id = (string) $saran->id;
            }
        }
    }

    protected function pendaftaranMasuk(int $id): Pendaftaran
    {
        return Pendaftaran::query()
            ->whereIn('status', PendaftaranIndex::STATUS_MASUK)
            ->with([
                'orangTua:id,nama,email',
                'paketLayanan:id,nama,harga',
                'anak.kelompokUsia:id,nama',
            ])
            ->findOrFail($id);
    }

    /**
     * Semua kelompok usia beserta jumlah anak aktif di dalamnya (untuk cek kapasitas).
     *
     * @return Collection<int, KelompokUsia>
     */
    protected function daftarKelompokUsia(): Collection
    {
        return KelompokUsia::query()
            ->withCount('anak')
            ->orderBy('usia_min_bulan')
            ->get(['id', 'nama', 'usia_min_bulan', 'usia_maks_bulan', 'kapasitas']);
    }

    /** Usia anak dalam bulan penuh. */
    protected function usiaBulan(Anak $anak): ?int
    {
        return $anak->tanggal_lahir ? (int) floor($anak->tanggal_lahir->diffInMonths(now())) : null;
    }

    /**
     * Saran awal pilihan kelompok usia: yang sudah tercatat di data anak, atau yang rentang
     * usianya cocok dengan usia anak. Admin tetap bebas memilih kelompok lain.
     *
     * @param  Collection<int, KelompokUsia>  $kelompokUsia
     */
    protected function saranKelompokUsia(Anak $anak, Collection $kelompokUsia): ?int
    {
        if ($anak->kelompok_usia_id) {
            return $anak->kelompok_usia_id;
        }

        $usia = $this->usiaBulan($anak);

        if ($usia === null) {
            return null;
        }

        return $kelompokUsia->first(
            fn (KelompokUsia $kelompok) => $usia >= $kelompok->usia_min_bulan && $usia <= $kelompok->usia_maks_bulan
        )?->id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'kelompok_usia_id' => ['required', 'integer', Rule::exists('kelompok_usia', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'kelompok_usia_id.required' => 'Pilih kelompok usia anak terlebih dahulu.',
            'kelompok_usia_id.integer' => 'Pilih kelompok usia dari daftar.',
            'kelompok_usia_id.exists' => 'Kelompok usia yang dipilih tidak ditemukan.',
        ];
    }

    /** PBI-11f: setujui pendaftaran dan tetapkan kelompok usia anak. */
    public function setujui(): void
    {
        abort_unless(auth()->user()?->can('verifikasi pendaftaran'), 403);

        $pendaftaran = $this->pendaftaranMasuk($this->pendaftaranId);

        if (! $pendaftaran->bisaDisetujui() || ! $pendaftaran->anak) {
            $this->addError('kelompok_usia_id', 'Pendaftaran ini sudah diproses dan tidak bisa disetujui lagi.');

            return;
        }

        $data = $this->validate();

        $kelompok = KelompokUsia::query()->withCount('anak')->findOrFail($data['kelompok_usia_id']);

        if ($kelompok->anak_count >= $kelompok->kapasitas) {
            $this->addError('kelompok_usia_id', "Kelompok {$kelompok->nama} sudah penuh ({$kelompok->anak_count} dari {$kelompok->kapasitas} anak). Pilih kelompok lain.");

            return;
        }

        if (! $pendaftaran->setujui($kelompok, auth()->user())) {
            $this->addError('kelompok_usia_id', 'Pendaftaran ini sudah diproses dan tidak bisa disetujui lagi.');

            return;
        }

        session()->flash('sukses', "Pendaftaran {$pendaftaran->anak->nama_lengkap} disetujui. Anak sekarang aktif di kelompok {$kelompok->nama}.");

        $this->redirectRoute('admin.pendaftaran.index', navigate: true);
    }

    public function render(): View
    {
        $pendaftaran = $this->pendaftaranMasuk($this->pendaftaranId);
        $anak = $pendaftaran->anak;
        $bisaDisetujui = $pendaftaran->bisaDisetujui() && $anak !== null;
        $kelompokUsia = $bisaDisetujui ? $this->daftarKelompokUsia() : collect();

        return view('livewire.admin.pendaftaran-detail', [
            'pendaftaran' => $pendaftaran,
            'anak' => $anak,
            'profilOrangTua' => DB::table('profil_orang_tua')
                ->where('pengguna_id', $pendaftaran->orang_tua_id)
                ->first(['no_hp', 'alamat', 'pekerjaan', 'kontak_darurat']),
            'catatanKesehatan' => $anak ? CatatanKesehatan::where('anak_id', $anak->id)->first() : null,
            'penjemput' => $anak
                ? PenjemputTerdaftar::where('anak_id', $anak->id)->where('aktif', true)->orderBy('nama_lengkap')->get()
                : collect(),
            'diverifikasiOleh' => $pendaftaran->diverifikasi_oleh_id
                ? DB::table('pengguna')->where('id', $pendaftaran->diverifikasi_oleh_id)->value('nama')
                : null,
            'bisaDisetujui' => $bisaDisetujui,
            'kelompokUsia' => $kelompokUsia,
            'saranKelompokUsiaId' => $bisaDisetujui ? $this->saranKelompokUsia($anak, $kelompokUsia) : null,
        ]);
    }
}
