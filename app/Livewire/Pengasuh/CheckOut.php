<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\IzinPenjemputan;
use App\Models\Kehadiran;
use App\Models\PenjemputTerdaftar;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-16d: Pengasuh mencatat check-out anak dengan mencocokkan penjemput ke Penjemput Terdaftar.
 */
#[Title('Check-out')]
class CheckOut extends Component
{
    /** Pilihan "penjemput lain" pada daftar penjemput. */
    public const LAIN = 'lain';

    /** Awalan nilai pilihan untuk izin penjemputan: "izin-{id}". */
    public const IZIN = 'izin-';

    /** Kehadiran (anak) yang sedang diisi form check-out-nya. */
    #[Locked]
    public ?int $kehadiranId = null;

    public string $jam_pulang = '';

    /** Id penjemput terdaftar, atau "lain" bila yang datang tidak ada di daftar. */
    public string $penjemput = '';

    public string $nama_penjemput = '';

    /** True setelah percobaan check-out gagal karena penjemput tidak cocok; menampilkan panduan tindak lanjut. */
    #[Locked]
    public bool $tidakCocok = false;

    /** Mengubah pilihan atau nama penjemput berarti pengasuh mencoba lagi: panduan lama disembunyikan. */
    public function updatedPenjemput(): void
    {
        $this->tidakCocok = false;
    }

    public function updatedNamaPenjemput(): void
    {
        $this->tidakCocok = false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'jam_pulang' => [
                'required',
                'date_format:H:i',
                fn (string $attribute, mixed $value, \Closure $fail) => $value <= now()->format('H:i')
                    ?: $fail('Jam pulang tidak boleh melewati jam sekarang.'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->sebelumCheckIn($value)
                    ? $fail('Jam pulang tidak boleh lebih awal dari jam datang.')
                    : null,
            ],
            'penjemput' => ['required', 'string'],
            'nama_penjemput' => [$this->penjemput === self::LAIN ? 'required' : 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['jam_pulang' => 'jam pulang', 'penjemput' => 'penjemput', 'nama_penjemput' => 'nama penjemput'];
    }

    public function mulaiCheckOut(int $kehadiranId): void
    {
        $kehadiran = $this->kehadiranAktif()->whereKey($kehadiranId)->first();
        if (! $kehadiran) {
            return;
        }

        $this->resetValidation();
        $this->kehadiranId = $kehadiran->id;
        $this->jam_pulang = now()->format('H:i');
        $this->penjemput = '';
        $this->nama_penjemput = '';
        $this->tidakCocok = false;
    }

    public function batal(): void
    {
        $this->reset('kehadiranId', 'jam_pulang', 'penjemput', 'nama_penjemput', 'tidakCocok');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $data = $this->validate();

        // Kehadiran harus masih milik anak di kelompok pengasuh dan belum check-out (cegah id dimanipulasi).
        $kehadiran = $this->kehadiranId ? $this->kehadiranAktif()->with('anak')->whereKey($this->kehadiranId)->first() : null;
        if (! $kehadiran) {
            $this->batal();
            session()->flash('galat', 'Data kehadiran tidak ditemukan atau anak sudah dicatat pulang.');

            return;
        }

        // Penjemput dengan izin penjemputan yang sudah disetujui.
        $izin = null;
        $penjemput = null;
        if (str_starts_with($data['penjemput'], self::IZIN)) {
            $izinId = substr($data['penjemput'], strlen(self::IZIN));
            $alasan = $this->alasanIzinTidakBerlaku($kehadiran->anak, $izinId);

            if ($alasan) {
                $this->tidakCocok = true;
                $this->addError('penjemput', $alasan);

                return;
            }

            $izin = IzinPenjemputan::find((int) $izinId);
        } else {
            $penjemput = $this->cocokkanPenjemput($kehadiran->anak, $data['penjemput'], $data['nama_penjemput'] ?? '');
        }

        if (! $penjemput && ! $izin) {
            // Anak tidak dilepas. Form tetap terbuka dan panduan tindak lanjut ditampilkan.
            $this->tidakCocok = true;
            $this->addError($this->penjemput === self::LAIN ? 'nama_penjemput' : 'penjemput', 'Penjemput tidak cocok dengan Penjemput Terdaftar atau izin penjemputan anak ini.');

            return;
        }

        $namaPenjemput = $izin?->nama_penjemput ?? $penjemput->nama_lengkap;

        // Update bersyarat (belum check-out): bila pengasuh lain menyimpan lebih dulu di saat bersamaan,
        // catatannya tidak ditimpa diam-diam.
        $tersimpan = Kehadiran::query()
            ->whereKey($kehadiran->id)
            ->whereNull('check_out_pada')
            ->update([
                'check_out_pada' => today()->setTimeFromTimeString($data['jam_pulang']),
                'check_out_oleh_id' => auth()->id(),
                'penjemput_terdaftar_id' => $penjemput?->id,
                'izin_penjemputan_id' => $izin?->id,
                'nama_penjemput' => $namaPenjemput,
                'updated_at' => now(),
            ]);

        if ($tersimpan === 0) {
            $this->batal();
            session()->flash('galat', 'Data kehadiran tidak ditemukan atau anak sudah dicatat pulang.');

            return;
        }

        $nama = $kehadiran->anak->nama_lengkap;
        $this->batal();
        session()->flash('sukses', "Check-out {$nama} berhasil dicatat. Dijemput oleh {$namaPenjemput}".($izin ? ' (izin penjemputan).' : '.'));
    }

    /** Izin penjemputan anak yang disetujui dan berlaku hari ini dan belum dipakai check-out. */
    private function izinBerlaku(Anak $anak)
    {
        return $anak->izinPenjemputan()
            ->disetujui()
            ->padaTanggal(today())
            ->whereNotIn('id', Kehadiran::query()->whereNotNull('izin_penjemputan_id')->select('izin_penjemputan_id'));
    }

    /**
     * Periksa izin saat disimpan (status bisa berubah sejak form dibuka). Mengembalikan alasan penolakan,
     * atau null bila izin sah dipakai: milik anak ini, berstatus disetujui, berlaku hari ini, dan belum terpakai.
     */
    private function alasanIzinTidakBerlaku(Anak $anak, string $izinId): ?string
    {
        $izin = ctype_digit($izinId) ? $anak->izinPenjemputan()->whereKey((int) $izinId)->first() : null;

        return match (true) {
            $izin === null => 'Izin penjemputan tidak ditemukan untuk anak ini.',
            $izin->status !== IzinPenjemputan::STATUS_DISETUJUI => "Izin penjemputan berstatus {$izin->status}. Hanya izin yang disetujui yang bisa dipakai.",
            ! $izin->tanggal_jemput->isToday() => 'Izin penjemputan ini berlaku pada '.$izin->tanggal_jemput->format('d/m/Y').', bukan hari ini.',
            Kehadiran::query()->where('izin_penjemputan_id', $izin->id)->exists() => 'Izin penjemputan ini sudah dipakai untuk check-out.',
            default => null,
        };
    }

    /**
     * Cocokkan penjemput dengan Penjemput Terdaftar aktif milik anak itu.
     * Dipilih dari daftar: id harus milik anak. Penjemput lain: nama diketik dan harus sama persis (tanpa
     * memperhatikan huruf besar-kecil dan spasi berlebih) dengan salah satu penjemput terdaftar.
     */
    private function cocokkanPenjemput(Anak $anak, string $pilihan, string $nama): ?PenjemputTerdaftar
    {
        $terdaftar = $anak->penjemputTerdaftar()->aktif();

        if ($pilihan !== self::LAIN) {
            return ctype_digit($pilihan) ? $terdaftar->whereKey((int) $pilihan)->first() : null;
        }

        $kunci = $this->normalisasi($nama);

        return $terdaftar->get()->first(fn (PenjemputTerdaftar $p) => $this->normalisasi($p->nama_lengkap) === $kunci);
    }

    private function normalisasi(string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($nama)));
    }

    /** Apakah jam pulang (H:i) lebih awal dari jam check-in kehadiran yang sedang diisi. */
    private function sebelumCheckIn(string $jam): bool
    {
        $masuk = $this->kehadiranId ? Kehadiran::query()->whereKey($this->kehadiranId)->value('check_in_pada') : null;

        return $masuk !== null && $jam < Carbon::parse($masuk)->format('H:i');
    }

    /** Kehadiran hari ini milik anak di kelompok pengasuh yang belum check-out. */
    private function kehadiranAktif()
    {
        return Kehadiran::query()
            ->padaTanggal(today())
            ->masihDiTempat()
            ->whereIn('anak_id', Anak::query()->diasuhOleh(auth()->user(), today())->select('id'));
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $daftar = $this->kehadiranAktif()
            ->with(['anak.kelompokUsia:id,nama'])
            ->orderBy('check_in_pada')
            ->get();

        $terpilih = $this->kehadiranId ? $daftar->firstWhere('id', $this->kehadiranId) : null;

        return view('livewire.pengasuh.check-out', [
            'daftar' => $daftar,
            'terpilih' => $terpilih,
            'kontakOrangTua' => $terpilih && $this->tidakCocok
                ? $terpilih->anak->orangTua()->with('profilOrangTua:pengguna_id,nama_lengkap,no_hp')->first()?->profilOrangTua
                : null,
            'izinList' => $terpilih
                ? $this->izinBerlaku($terpilih->anak)->orderBy('nama_penjemput')->get(['id', 'nama_penjemput', 'alasan'])
                : collect(),
            'penjemputList' => $terpilih
                ? $terpilih->anak->penjemputTerdaftar()->aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'hubungan'])
                : collect(),
            'hariIni' => today(),
        ]);
    }
}
