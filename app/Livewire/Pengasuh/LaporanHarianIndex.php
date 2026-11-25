<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\LampiranLaporan;
use App\Models\LaporanHarian;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Laporan Harian')]
class LaporanHarianIndex extends Component
{
    use WithFileUploads;

    #[Url(except: '')]
    public string $tanggal = '';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $status = ''; // '' | 'belum' | 'sudah'

    // ───────────── State Modal Form Buat / Ubah ─────────────
    #[Locked]
    public ?int $anakId = null;

    #[Locked]
    public ?int $laporanId = null;

    public string $status_makan = 'baik';

    public string $catatan_makan = '';

    public ?int $durasi_tidur_menit = null;

    public string $catatan_buang_air = '';

    public string $suasana_hati = 'ceria';

    public string $catatan_aktivitas = '';

    public string $catatan_umum = '';

    // ───────────── State Foto Kegiatan (User Story 4 & 5) ─────────────
    /** @var array<int, TemporaryUploadedFile> */
    public array $fotoBaru = [];

    /** @var array<int, string> */
    public array $keteranganFotoBaru = [];

    #[Locked]
    public ?int $gantiLampiranId = null;

    public ?TemporaryUploadedFile $filePengganti = null;

    public string $keteranganPengganti = '';

    // ───────────── State Modal Konfirmasi Hapus Laporan (User Story 3) ─────────────
    #[Locked]
    public ?int $hapusLaporanId = null;

    public function mount(): void
    {
        if ($this->tanggal === '') {
            $this->tanggal = today()->toDateString();
        }
    }

    public function pilihStatus(string $status): void
    {
        $this->status = in_array($status, ['belum', 'sudah'], true) && $this->status !== $status ? $status : '';
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status');
        $this->tanggal = today()->toDateString();
    }

    // ───────────── Buka & Tutup Form Laporan ─────────────

    public function bukaForm(int $anakId): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $tanggal = $this->tanggalTerpilih();
        $anak = $this->anakYangBisaDilaporkan($tanggal)->whereKey($anakId)->first();

        if (! $anak) {
            session()->flash('galat', 'Anak tidak ditemukan di kelompok Anda atau belum check-in pada tanggal tersebut.');

            return;
        }

        $this->batalForm();
        $this->batalHapus();
        $this->anakId = $anak->id;

        $existing = LaporanHarian::query()
            ->where('anak_id', $anak->id)
            ->padaTanggal($tanggal)
            ->first();

        if ($existing) {
            $this->laporanId = $existing->id;
            $this->status_makan = $existing->status_makan ?? 'baik';
            $this->catatan_makan = $existing->catatan_makan ?? '';
            $this->durasi_tidur_menit = $existing->durasi_tidur_menit;
            $this->catatan_buang_air = $existing->catatan_buang_air ?? '';
            $this->suasana_hati = $existing->suasana_hati ?? 'ceria';
            $this->catatan_aktivitas = $existing->catatan_aktivitas ?? '';
            $this->catatan_umum = $existing->catatan_umum ?? '';
        }
    }

    public function pakaiDurasiTidur(int $menit): void
    {
        $this->durasi_tidur_menit = $menit;
    }

    public function hapusAntreanFoto(int $index): void
    {
        unset($this->fotoBaru[$index], $this->keteranganFotoBaru[$index]);
        $this->fotoBaru = array_values($this->fotoBaru);
        $this->keteranganFotoBaru = array_values($this->keteranganFotoBaru);
    }

    public function batalForm(): void
    {
        $this->reset(
            'anakId',
            'laporanId',
            'status_makan',
            'catatan_makan',
            'durasi_tidur_menit',
            'catatan_buang_air',
            'suasana_hati',
            'catatan_aktivitas',
            'catatan_umum',
            'fotoBaru',
            'keteranganFotoBaru',
            'gantiLampiranId',
            'filePengganti',
            'keteranganPengganti'
        );
        $this->status_makan = 'baik';
        $this->suasana_hati = 'ceria';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'status_makan' => ['required', Rule::in(array_keys(LaporanHarian::STATUS_MAKAN))],
            'catatan_makan' => ['nullable', 'string', 'max:1000'],
            'durasi_tidur_menit' => ['nullable', 'integer', 'min:0', 'max:600'],
            'catatan_buang_air' => ['nullable', 'string', 'max:500'],
            'suasana_hati' => ['required', Rule::in(array_keys(LaporanHarian::SUASANA_HATI))],
            'catatan_aktivitas' => ['required', 'string', 'max:2000'],
            'catatan_umum' => ['nullable', 'string', 'max:1000'],
            'fotoBaru' => ['nullable', 'array', 'max:5'],
            'fotoBaru.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'keteranganFotoBaru.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'status_makan' => 'status makan',
            'catatan_makan' => 'catatan makan',
            'durasi_tidur_menit' => 'durasi tidur siang',
            'catatan_buang_air' => 'catatan BAB/BAK',
            'suasana_hati' => 'suasana hati',
            'catatan_aktivitas' => 'catatan aktivitas',
            'catatan_umum' => 'catatan umum',
            'fotoBaru' => 'foto kegiatan',
            'fotoBaru.*' => 'berkas foto kegiatan',
            'filePengganti' => 'foto pengganti',
        ];
    }

    public function simpanLaporan(): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $data = $this->validate();
        $tanggal = $this->tanggalTerpilih();

        $anak = $this->anakId
            ? $this->anakYangBisaDilaporkan($tanggal)->whereKey($this->anakId)->first()
            : null;

        if (! $anak) {
            $this->batalForm();
            session()->flash('galat', 'Data anak tidak valid untuk tanggal yang dipilih.');

            return;
        }

        $sudahAda = LaporanHarian::query()
            ->where('anak_id', $anak->id)
            ->padaTanggal($tanggal)
            ->exists();

        DB::transaction(function () use ($anak, $tanggal, $data) {
            $laporan = LaporanHarian::query()->updateOrCreate(
                [
                    'anak_id' => $anak->id,
                    'tanggal' => $tanggal->toDateString(),
                ],
                [
                    'pengasuh_id' => auth()->id(),
                    'status_makan' => $data['status_makan'],
                    'catatan_makan' => filled($data['catatan_makan']) ? trim($data['catatan_makan']) : null,
                    'durasi_tidur_menit' => $data['durasi_tidur_menit'],
                    'catatan_buang_air' => filled($data['catatan_buang_air']) ? trim($data['catatan_buang_air']) : null,
                    'suasana_hati' => $data['suasana_hati'],
                    'catatan_aktivitas' => trim($data['catatan_aktivitas']),
                    'catatan_umum' => filled($data['catatan_umum']) ? trim($data['catatan_umum']) : null,
                ]
            );

            foreach ($this->fotoBaru as $idx => $file) {
                $path = $file->store('laporan-harian/'.$tanggal->format('Y-m'), 'public');
                $ket = $this->keteranganFotoBaru[$idx] ?? null;

                LampiranLaporan::create([
                    'laporan_harian_id' => $laporan->id,
                    'path_file' => $path,
                    'tipe_mime' => $file->getMimeType() ?: 'image/jpeg',
                    'ukuran_file' => (int) $file->getSize(),
                    'keterangan' => filled($ket) ? trim($ket) : null,
                ]);
            }
        });

        $nama = $anak->nama_lengkap;
        $this->batalForm();

        session()->flash(
            'sukses',
            $sudahAda
                ? "Laporan harian {$nama} berhasil diperbarui."
                : "Laporan harian {$nama} berhasil disimpan."
        );
    }

    // ───────────── Ubah & Hapus Foto Satuan (User Story 5) ─────────────

    public function mulaiGantiFoto(int $lampiranId): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $lampiran = $this->cariLampiranMilikPengasuh($lampiranId);
        if (! $lampiran) {
            return;
        }

        $this->gantiLampiranId = $lampiran->id;
        $this->filePengganti = null;
        $this->keteranganPengganti = $lampiran->keterangan ?? '';
        $this->resetErrorBag(['filePengganti', 'keteranganPengganti']);
    }

    public function batalGantiFoto(): void
    {
        $this->reset('gantiLampiranId', 'filePengganti', 'keteranganPengganti');
        $this->resetErrorBag(['filePengganti', 'keteranganPengganti']);
    }

    public function simpanGantiFoto(): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $this->validate([
            'filePengganti' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'keteranganPengganti' => ['nullable', 'string', 'max:255'],
        ]);

        $lampiran = $this->gantiLampiranId ? $this->cariLampiranMilikPengasuh($this->gantiLampiranId) : null;
        if (! $lampiran) {
            $this->batalGantiFoto();

            return;
        }

        $update = [
            'keterangan' => filled($this->keteranganPengganti) ? trim($this->keteranganPengganti) : null,
        ];

        if ($this->filePengganti !== null) {
            $pathBaru = $this->filePengganti->store(
                'laporan-harian/'.$lampiran->laporanHarian->tanggal->format('Y-m'),
                'public'
            );

            if (! $lampiran->isUrlEksternal() && Storage::disk('public')->exists($lampiran->path_file)) {
                Storage::disk('public')->delete($lampiran->path_file);
            }

            $update['path_file'] = $pathBaru;
            $update['tipe_mime'] = $this->filePengganti->getMimeType() ?: 'image/jpeg';
            $update['ukuran_file'] = (int) $this->filePengganti->getSize();
        }

        $lampiran->update($update);
        $this->batalGantiFoto();
        session()->flash('sukses', 'Foto kegiatan berhasil diperbarui.');
    }

    public function hapusLampiran(int $lampiranId): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $lampiran = $this->cariLampiranMilikPengasuh($lampiranId);
        if (! $lampiran) {
            return;
        }

        if (! $lampiran->isUrlEksternal() && Storage::disk('public')->exists($lampiran->path_file)) {
            Storage::disk('public')->delete($lampiran->path_file);
        }

        if ($this->gantiLampiranId === $lampiran->id) {
            $this->batalGantiFoto();
        }

        $lampiran->delete();
        session()->flash('sukses', 'Foto kegiatan berhasil dihapus.');
    }

    // ───────────── Hapus Laporan Harian (User Story 3) ─────────────

    public function konfirmasiHapus(int $laporanId): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $laporan = $this->cariLaporanMilikPengasuh($laporanId);
        if (! $laporan) {
            session()->flash('galat', 'Laporan harian tidak ditemukan atau Anda tidak memiliki akses.');

            return;
        }

        $this->batalForm();
        $this->hapusLaporanId = $laporan->id;
    }

    public function batalHapus(): void
    {
        $this->hapusLaporanId = null;
    }

    public function hapusLaporan(): void
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $laporan = $this->hapusLaporanId ? $this->cariLaporanMilikPengasuh($this->hapusLaporanId) : null;
        if (! $laporan) {
            $this->batalHapus();
            session()->flash('galat', 'Laporan harian tidak ditemukan atau sudah dihapus.');

            return;
        }

        $namaAnak = $laporan->anak?->nama_lengkap ?? 'Anak';

        // Bersihkan semua file foto fisik di storage lokal sebelum baris laporan dihapus
        foreach ($laporan->lampiran as $lampiran) {
            if (! $lampiran->isUrlEksternal() && Storage::disk('public')->exists($lampiran->path_file)) {
                Storage::disk('public')->delete($lampiran->path_file);
            }
        }

        $laporan->delete();
        $this->batalHapus();

        session()->flash('sukses', "Laporan harian {$namaAnak} berhasil dihapus.");
    }

    private function cariLaporanMilikPengasuh(int $laporanId): ?LaporanHarian
    {
        $tanggal = $this->tanggalTerpilih();
        $anakIds = $this->anakYangBisaDilaporkan($tanggal)->select('id');

        return LaporanHarian::query()
            ->with(['anak', 'lampiran'])
            ->whereKey($laporanId)
            ->padaTanggal($tanggal)
            ->whereIn('anak_id', $anakIds)
            ->first();
    }

    private function cariLampiranMilikPengasuh(int $lampiranId): ?LampiranLaporan
    {
        $tanggal = $this->tanggalTerpilih();
        $anakIds = $this->anakYangBisaDilaporkan($tanggal)->select('id');

        return LampiranLaporan::query()
            ->with('laporanHarian')
            ->whereKey($lampiranId)
            ->whereHas('laporanHarian', fn ($q) => $q->padaTanggal($tanggal)->whereIn('anak_id', $anakIds))
            ->first();
    }

    private function tanggalTerpilih(): Carbon
    {
        try {
            return $this->tanggal !== '' ? Carbon::parse($this->tanggal)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    private function anakYangBisaDilaporkan(Carbon $tanggal)
    {
        return Anak::query()
            ->diasuhOleh(auth()->user(), $tanggal)
            ->whereHas('kehadiran', fn ($q) => $q->padaTanggal($tanggal));
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('buat laporan harian'), 403);

        $tanggal = $this->tanggalTerpilih();

        $semua = $this->anakYangBisaDilaporkan($tanggal)
            ->with([
                'kelompokUsia:id,nama',
                'kehadiran' => fn ($q) => $q->padaTanggal($tanggal),
                'laporanHarian' => fn ($q) => $q->padaTanggal($tanggal)->with('lampiran'),
            ])
            ->orderBy('nama_lengkap')
            ->get()
            ->each(function (Anak $a) {
                $laporan = $a->laporanHarian->first();
                $a->setAttribute('laporan_hari_ini', $laporan);
                $a->setAttribute('sudah_lapor', $laporan !== null);
            });

        $kata = mb_strtolower(trim($this->q));

        $daftarAnak = $semua
            ->when($this->status === 'sudah', fn ($c) => $c->where('sudah_lapor', true))
            ->when($this->status === 'belum', fn ($c) => $c->where('sudah_lapor', false))
            ->when($kata !== '', fn ($c) => $c->filter(fn (Anak $a) => str_contains(mb_strtolower($a->nama_lengkap), $kata)))
            ->values();

        $terpilihAnak = $this->anakId ? $semua->firstWhere('id', $this->anakId) : null;
        $laporanAkanDihapus = $this->hapusLaporanId
            ? $semua->pluck('laporan_hari_ini')->filter()->firstWhere('id', $this->hapusLaporanId)
            : null;

        return view('livewire.pengasuh.laporan-harian-index', [
            'tanggalObjek' => $tanggal,
            'daftarAnak' => $daftarAnak,
            'totalAnak' => $semua->count(),
            'jumlahSudah' => $semua->where('sudah_lapor', true)->count(),
            'jumlahBelum' => $semua->where('sudah_lapor', false)->count(),
            'terpilihAnak' => $terpilihAnak,
            'lampiranTerpilih' => $terpilihAnak?->laporan_hari_ini?->lampiran ?? collect(),
            'laporanAkanDihapus' => $laporanAkanDihapus,
            'opsiMakan' => LaporanHarian::STATUS_MAKAN,
            'opsiSuasanaHati' => LaporanHarian::SUASANA_HATI,
        ]);
    }
}