{{-- PBI-11c: isi dialog konfirmasi hapus pendaftaran Draft. --}}
@props(['pendaftaran'])

<div class="grid gap-2">
    <p>
        Pendaftaran paket <b>{{ $pendaftaran->paketLayanan->nama }}</b>
        @if ($pendaftaran->anak)
            beserta data anak <b>{{ $pendaftaran->anak->nama_lengkap }}</b>
        @endif
        akan dihapus dan tidak lagi tampil di daftar Anda.
    </p>
    <p class="text-muted">Anda bisa membuat pendaftaran baru kapan saja.</p>
</div>
