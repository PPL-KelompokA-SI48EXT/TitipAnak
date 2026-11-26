<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\LaporanInsiden;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Laporan Insiden')]
class LaporanInsidenIndex extends Component
{
    public const JUDUL_CEPAT = [
        'Terjatuh saat bermain',
        'Terbentur ringan',
        'Demam mendadak',
        'Luka gores kecil',
        'Muntah / tidak enak badan',
    ];

    #[Url(except: '')]
    public string $tanggal = '';

    #[Url(except: '')]
    public string $q = '';

    // ───────────── State Modal Buat / Ubah Laporan Insiden ─────────────
    #[Locked]
    public ?int $insidenId = null;

    public string $anak_id = '';

    public string $jam_kejadian = '';

    public string $judul = '';

    public string $deskripsi = '';

    public string $tindakan = '';

    public function mount(): void
    {
        if ($this->tanggal === '') {
            $this->tanggal = today()->toDateString();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('q');
        $this->tanggal = today()->toDateString();
    }

    // ───────────── Buka Form Tambah / Ubah ─────────────

    public function mulaiBuat(?int $anakId = null): void
    {
        abort_unless(auth()->user()?->can('buat laporan insiden'), 403);

        $this->batalForm();
        $this->jam_kejadian = now()->format('H:i');

        if ($anakId !== null) {
            $anak = $this->anakDiasuh($this->tanggalTerpilih())->whereKey($anakId)->first();
            if ($anak) {
                $this->anak_id = (string) $anak->id;
            }
        }
    }

    public function mulaiUbah(int $insidenId): void
    {
        abort_unless(auth()->user()?->can('buat laporan insiden'), 403);

        $insiden = $this->cariInsidenDapatDiubah($insidenId);
        if (! $insiden) {
            session()->flash('galat', 'Laporan insiden tidak ditemukan atau tidak dapat diubah.');

            return;
        }

        $this->batalForm();
        $this->insidenId = $insiden->id;
        $this->anak_id = (string) $insiden->anak_id;
        $this->jam_kejadian = $insiden->waktu_kejadian->format('H:i');
        $this->judul = $insiden->judul;
        $this->deskripsi = $insiden->deskripsi;
        $this->tindakan = $insiden->tindakan ?? '';
    }

    public function pakaiJudul(string $judul): void
    {
        if (in_array($judul, self::JUDUL_CEPAT, true)) {
            $this->judul = $judul;
        }
    }

    public function batalForm(): void
    {
        $this->reset('insidenId', 'anak_id', 'jam_kejadian', 'judul', 'deskripsi', 'tindakan');
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $tanggal = $this->tanggalTerpilih();

        return [
            'anak_id' => ['required', 'integer'],
            'jam_kejadian' => [
                'required',
                'date_format:H:i',
                fn (string $attribute, mixed $value, \Closure $fail) => $tanggal->isToday() && $value > now()->format('H:i')
                    ? $fail('Jam kejadian tidak boleh melewati jam sekarang.')
                    : null,
            ],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:3000'],
            'tindakan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'anak_id' => 'anak',
            'jam_kejadian' => 'jam kejadian',
            'judul' => 'judul insiden',
            'deskripsi' => 'kronologi / deskripsi kejadian',
            'tindakan' => 'tindakan penanganan',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('buat laporan insiden'), 403);

        $data = $this->validate();
        $tanggal = $this->tanggalTerpilih();

        $anak = $this->anakDiasuh($tanggal)->whereKey((int) $data['anak_id'])->first();
        if (! $anak) {
            $this->addError('anak_id', 'Anak tidak terdaftar di kelompok tanggung jawab Anda pada tanggal tersebut.');

            return;
        }

        $waktuKejadian = $tanggal->copy()->setTimeFromTimeString($data['jam_kejadian']);

        if ($this->insidenId !== null) {
            $insiden = $this->cariInsidenDapatDiubah($this->insidenId);
            if (! $insiden) {
                $this->batalForm();
                session()->flash('galat', 'Laporan insiden tidak ditemukan.');

                return;
            }

            $insiden->update([
                'anak_id' => $anak->id,
                'waktu_kejadian' => $waktuKejadian,
                'judul' => trim($data['judul']),
                'deskripsi' => trim($data['deskripsi']),
                'tindakan' => filled($data['tindakan']) ? trim($data['tindakan']) : null,
            ]);

            $this->batalForm();
            session()->flash('sukses', "Laporan insiden {$anak->nama_lengkap} berhasil diperbarui.");

            return;
        }

        LaporanInsiden::create([
            'anak_id' => $anak->id,
            'pengasuh_id' => auth()->id(),
            'waktu_kejadian' => $waktuKejadian,
            'judul' => trim($data['judul']),
            'deskripsi' => trim($data['deskripsi']),
            'tindakan' => filled($data['tindakan']) ? trim($data['tindakan']) : null,
        ]);

        $this->batalForm();
        session()->flash('sukses', "Laporan insiden {$anak->nama_lengkap} berhasil dicatat.");
    }

    private function cariInsidenDapatDiubah(int $insidenId): ?LaporanInsiden
    {
        $tanggal = $this->tanggalTerpilih();
        $anakIds = $this->anakDiasuh($tanggal)->select('id');

        return LaporanInsiden::query()
            ->whereKey($insidenId)
            ->padaTanggal($tanggal)
            ->where(function ($q) use ($anakIds) {
                $q->where('pengasuh_id', auth()->id())
                    ->orWhereIn('anak_id', $anakIds);
            })
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

    private function anakDiasuh(Carbon $tanggal)
    {
        return Anak::query()->diasuhOleh(auth()->user(), $tanggal);
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('buat laporan insiden'), 403);

        $tanggal = $this->tanggalTerpilih();
        $anakDiasuh = $this->anakDiasuh($tanggal)
            ->with('kelompokUsia:id,nama')
            ->orderBy('nama_lengkap')
            ->get();

        $kata = mb_strtolower(trim($this->q));

        $daftarInsiden = LaporanInsiden::query()
            ->padaTanggal($tanggal)
            ->where(function ($q) use ($anakDiasuh) {
                $q->where('pengasuh_id', auth()->id())
                    ->orWhereIn('anak_id', $anakDiasuh->modelKeys());
            })
            ->with(['anak.kelompokUsia:id,nama', 'pengasuh:id,nama'])
            ->orderByDesc('waktu_kejadian')
            ->get()
            ->when($kata !== '', function ($c) use ($kata) {
                return $c->filter(function (LaporanInsiden $item) use ($kata) {
                    return str_contains(mb_strtolower($item->anak?->nama_lengkap ?? ''), $kata)
                        || str_contains(mb_strtolower($item->judul), $kata);
                });
            })
            ->values();

        return view('livewire.pengasuh.laporan-insiden-index', [
            'tanggalObjek' => $tanggal,
            'anakDiasuh' => $anakDiasuh,
            'daftarInsiden' => $daftarInsiden,
            'judulCepatList' => self::JUDUL_CEPAT,
            'modeUbah' => $this->insidenId !== null,
        ]);
    }
}