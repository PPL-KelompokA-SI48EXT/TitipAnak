@php
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Buat Jadwal Shift</h1>
        <p class="text-muted">Hubungkan pengasuh dengan kelompok usia agar pembagian tugas jelas.</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    @include('partials.form-shift', ['tombol' => 'Simpan jadwal'])
</div>
