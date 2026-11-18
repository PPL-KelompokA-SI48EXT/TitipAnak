<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\IzinPenjemputan;
use App\Models\Kehadiran;
use App\Models\PenjemputTerdaftar;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Satu halaman presensi untuk pengasuh (PBI-16a, 16b, 16c, 16d, 16e):
 * daftar kehadiran anak hari ini dengan pencarian dan filter status (16b), check-in (16a, 16c)
 * dan check-out dengan pencocokan penjemput atau izin penjemputan (16d, 16e).
 *
 * Hanya anak di kelompok usia yang dipegang pengasuh hari ini yang muncul. Form check-in dan check-out
 * dibuka sebagai dialog dari kartu anak; hanya satu dialog yang aktif pada satu waktu.
 */
#[Title('Presensi')]
class Presensi extends Component
{
    /** Pilihan cepat kondisi anak saat datang. */
    public const KONDISI = ['Sehat dan ceria', 'Batuk atau pilek', 'Kurang tidur', 'Rewel', 'Demam ringan'];

    /** Pilihan "penjemput lain" pada daftar penjemput. */
    public const LAIN = 'lain';

    /** Awalan nilai pilihan untuk izin penjemputan: "izin-{id}". */
    public const IZIN = 'izin-';

    public const STATUS_BELUM = 'belum';

    public const STATUS_HADIR = 'hadir';

    public const STATUS_PULANG = 'pulang';

    public const STATUS = [self::STATUS_BELUM, self::STATUS_HADIR, self::STATUS_PULANG];

    #[Url(except: '')]
    public string $q = '';

    /** Kosong = semua status. */
    #[Url(except: '')]
    public string $status = '';

    /** Anak yang sedang diisi form check-in-nya. */
    #[Locked]
    public ?int $anakId = null;

    public string $jam_datang = '';

    public string $kondisi = '';

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

    // ───────────── Filter daftar ─────────────

    public function pilihStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUS, true) && $this->status !== $status ? $status : '';
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status');
    }

    // ───────────── Aturan validasi ─────────────

    /**
     * Aturan mengikuti dialog yang sedang terbuka: check-out bila ada kehadiranId, selain itu check-in.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        if ($this->kehadiranId !== null) {
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

        return [
            'jam_datang' => [
                'required',
                'date_format:H:i',
                // Jam datang tidak boleh lebih dari sekarang (presensi hanya untuk hari ini).
                fn (string $attribute, mixed $value, \Closure $fail) => $value <= now()->format('H:i')
                    ?: $fail('Jam datang tidak boleh melewati jam sekarang.'),
            ],
            'kondisi' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'jam_datang' => 'jam datang', 'kondisi' => 'kondisi anak',
            'jam_pulang' => 'jam pulang', 'penjemput' => 'penjemput', 'nama_penjemput' => 'nama penjemput',
        ];
    }

    // ───────────── Check-in ─────────────

    public function mulaiCheckIn(int $anakId): void
    {
        $anak = $this->anakDiasuh()->whereKey($anakId)->first();
        if (! $anak) {
            return;
        }

        if ($this->sudahCheckIn($anak)) {
            $this->tolakCheckInGanda($anak);

            return;
        }

        $this->batal();
        $this->anakId = $anak->id;
        $this->jam_datang = now()->format('H:i');
    }

    public function pakaiKondisi(string $kondisi): void
    {
        if (in_array($kondisi, self::KONDISI, true)) {
            $this->kondisi = $kondisi;
        }
    }

    private function simpanCheckIn(): void
    {
        $data = $this->validate();

        // Anak harus masih di kelompok yang dipegang pengasuh hari ini (cegah id dimanipulasi).
        $anak = $this->anakId ? $this->anakDiasuh()->whereKey($this->anakId)->first() : null;
        if (! $anak) {
            $this->batal();
            session()->flash('sukses', 'Anak tidak ditemukan di kelompok Anda hari ini.');

            return;
        }

        // Satu anak hanya boleh check-in sekali per hari.
        if ($this->sudahCheckIn($anak)) {
            $this->tolakCheckInGanda($anak);

            return;
        }

        try {
            Kehadiran::create([
                'anak_id' => $anak->id,
                'tanggal' => today(),
                'check_in_pada' => today()->setTimeFromTimeString($data['jam_datang']),
                'kondisi_check_in' => trim($data['kondisi']),
                'check_in_oleh_id' => auth()->id(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Pengasuh lain mencatat anak yang sama di saat bersamaan; unique(anak_id, tanggal) menjaga di level DB.
            $this->tolakCheckInGanda($anak);

            return;
        }

        $this->batal();
        session()->flash('sukses', "Check-in {$anak->nama_lengkap} berhasil dicatat.");
    }

    /** Tutup form dan beri tahu pengasuh bahwa anak itu sudah tercatat hari ini, lengkap dengan jam check-in-nya. */
    private function tolakCheckInGanda(Anak $anak): void
    {
        $jam = Kehadiran::query()->where('anak_id', $anak->id)->padaTanggal(today())->value('check_in_pada');

        $this->batal();
        session()->flash('galat', $jam
            ? "{$anak->nama_lengkap} sudah check-in hari ini pukul ".Carbon::parse($jam)->format('H:i').'. Check-in tidak bisa dicatat dua kali.'
            : "{$anak->nama_lengkap} sudah check-in hari ini.");
    }

    private function sudahCheckIn(Anak $anak): bool
    {
        return Kehadiran::query()->where('anak_id', $anak->id)->padaTanggal(today())->exists();
    }

    // ───────────── Check-out ─────────────

    /** Mengubah pilihan atau nama penjemput berarti pengasuh mencoba lagi: panduan lama disembunyikan. */
    public function updatedPenjemput(): void
    {
        $this->tidakCocok = false;
    }

    public function updatedNamaPenjemput(): void
    {
        $this->tidakCocok = false;
    }

    public function mulaiCheckOut(int $kehadiranId): void
    {
        $kehadiran = $this->kehadiranAktif()->whereKey($kehadiranId)->first();
        if (! $kehadiran) {
            return;
        }

        $this->batal();
        $this->kehadiranId = $kehadiran->id;
        $this->jam_pulang = now()->format('H:i');
    }

    private function simpanCheckOut(): void
    {
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

    // ───────────── Simpan dan batal (dialog yang sedang terbuka) ─────────────

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        if ($this->kehadiranId !== null) {
            $this->simpanCheckOut();
        } else {
            $this->simpanCheckIn();
        }
    }

    public function batal(): void
    {
        $this->reset('anakId', 'jam_datang', 'kondisi', 'kehadiranId', 'jam_pulang', 'penjemput', 'nama_penjemput', 'tidakCocok');
        $this->resetValidation();
    }

    private function anakDiasuh()
    {
        return Anak::query()->diasuhOleh(auth()->user(), today());
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $semua = $this->anakDiasuh()
            ->with(['kelompokUsia:id,nama', 'kehadiran' => fn ($q) => $q->padaTanggal(today())])
            ->orderBy('nama_lengkap')
            ->get()
            ->each(function (Anak $a) {
                $hadir = $a->kehadiran->first();
                $a->setAttribute('presensi_hari_ini', $hadir);
                $a->setAttribute('status_hari_ini', match (true) {
                    $hadir === null => self::STATUS_BELUM,
                    $hadir->sudahPulang() => self::STATUS_PULANG,
                    default => self::STATUS_HADIR,
                });
            });

        $status = in_array($this->status, self::STATUS, true) ? $this->status : '';
        $kata = mb_strtolower(trim($this->q));

        $anak = $semua
            ->when($status !== '', fn ($c) => $c->where('status_hari_ini', $status))
            ->when($kata !== '', fn ($c) => $c->filter(fn (Anak $a) => str_contains(mb_strtolower($a->nama_lengkap), $kata)))
            ->values();

        $terpilih = $this->kehadiranId
            ? $this->kehadiranAktif()->with(['anak.kelompokUsia:id,nama'])->whereKey($this->kehadiranId)->first()
            : null;

        return view('livewire.pengasuh.presensi', [
            'anak' => $anak,
            'semua' => $semua->count(),
            'jumlah' => collect(self::STATUS)->mapWithKeys(fn ($s) => [$s => $semua->where('status_hari_ini', $s)->count()]),
            'statusAktif' => $status,
            'hariIni' => today(),
            // Dialog check-in
            'terpilihCheckIn' => $this->anakId ? $semua->firstWhere('id', $this->anakId) : null,
            'kondisiList' => self::KONDISI,
            // Dialog check-out
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
        ]);
    }
}
