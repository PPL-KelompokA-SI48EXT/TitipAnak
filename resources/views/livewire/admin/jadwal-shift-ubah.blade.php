@php
    $lamaNonaktif = $pengasuhLama && ! $pengasuh->contains('id', $pengasuhLama->id);
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Ubah Jadwal Shift</h1>
        <p class="text-muted">Ganti pengasuh atau sesuaikan kelompok usia, hari, dan jam shift ini.</p>
    </div>

    @if ($lamaNonaktif)
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-cream px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="⚠" /></span>
            <span><b>{{ $pengasuhLama->nama }}</b> sudah tidak aktif. Pilih pengasuh pengganti sebelum menyimpan.</span>
        </div>
    @endif

    @include('partials.form-shift', ['tombol' => 'Simpan perubahan', 'batalUrl' => route('admin.jadwal-shift.index'), 'lamaNonaktif' => $lamaNonaktif, 'pengasuhLama' => $pengasuhLama])
</div>
