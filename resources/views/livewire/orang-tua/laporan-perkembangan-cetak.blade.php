<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Perkembangan Anak - {{ $anak->nama_lengkap }}</title>
    <style>
        @page {
            margin: 18mm 16mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #1E1E1E;
            font-size: 11px;
            line-height: 1.5;
        }
        .sheet {
            border: 2px solid #1E1E1E;
            border-radius: 12px;
            padding: 20px 22px;
            background: #FFFFFF;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border-bottom: 2px solid #1E1E1E;
            margin-bottom: 16px;
        }
        .header-table td {
            padding-bottom: 12px;
            vertical-align: top;
        }
        .brand {
            font-size: 18px;
            font-weight: bold;
        }
        .subtitle {
            font-size: 11px;
            color: #5C5850;
            margin-top: 2px;
        }
        .badge {
            display: inline-block;
            background: #FFD84D;
            border: 1.5px solid #1E1E1E;
            border-radius: 14px;
            padding: 5px 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .meta-box {
            background: #FBF6EA;
            border: 1.5px solid #1E1E1E;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 16px;
        }
        .meta-table td {
            padding: 3px 6px;
            width: 50%;
            vertical-align: top;
        }
        .meta-label {
            color: #5C5850;
            font-weight: bold;
        }
        .aspek-table {
            margin-bottom: 10px;
        }
        .aspek-table td {
            width: 50%;
            vertical-align: top;
            padding-bottom: 10px;
        }
        .aspek-left {
            padding-right: 6px;
        }
        .aspek-right {
            padding-left: 6px;
        }
        .card {
            border: 1.5px solid #1E1E1E;
            border-radius: 8px;
            padding: 10px 12px;
            background: #FFFFFF;
            min-height: 92px;
        }
        .card h3 {
            margin: 0 0 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #4A463F;
            border-bottom: 1px solid #E5DFD2;
            padding-bottom: 4px;
        }
        .card p {
            margin: 0;
            font-size: 11px;
        }
        .card-umum {
            background: #F4F9F4;
            min-height: 64px;
            margin-bottom: 18px;
        }
        .footer-table {
            border-top: 1.5px dashed #1E1E1E;
        }
        .footer-table td {
            padding-top: 12px;
            vertical-align: bottom;
            font-size: 10px;
            color: #5C5850;
        }
        .ttd {
            text-align: right;
            color: #1E1E1E;
        }
        .ttd-nama {
            margin-top: 38px;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">TitipAnak Daycare</div>
                    <div class="subtitle">Laporan Evaluasi Perkembangan Anak Bulanan</div>
                </td>
                <td style="text-align: right;">
                    <span class="badge">Periode: {{ $laporan->periode_bulan->translatedFormat('F Y') }}</span>
                </td>
            </tr>
        </table>

        <div class="meta-box">
            <table class="meta-table">
                <tr>
                    <td><span class="meta-label">Nama Anak:</span> {{ $anak->nama_lengkap }}</td>
                    <td><span class="meta-label">Kelompok Usia:</span> {{ $anak->kelompokUsia?->nama ?? '—' }}</td>
                </tr>
                <tr>
                    <td><span class="meta-label">Orang Tua / Wali:</span> {{ $orangTua->nama }}</td>
                    <td><span class="meta-label">Pengasuh Penilai:</span> {{ $laporan->pengasuh?->nama ?? 'Pengasuh Daycare' }}</td>
                </tr>
            </table>
        </div>

        <table class="aspek-table">
            <tr>
                <td class="aspek-left">
                    <div class="card">
                        <h3>1. Perkembangan Motorik</h3>
                        <p>{!! nl2br(e($laporan->catatan_motorik ?: '—')) !!}</p>
                    </div>
                </td>
                <td class="aspek-right">
                    <div class="card">
                        <h3>2. Perkembangan Bahasa</h3>
                        <p>{!! nl2br(e($laporan->catatan_bahasa ?: '—')) !!}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="aspek-left">
                    <div class="card">
                        <h3>3. Sosial &amp; Emosional</h3>
                        <p>{!! nl2br(e($laporan->catatan_sosial ?: '—')) !!}</p>
                    </div>
                </td>
                <td class="aspek-right">
                    <div class="card">
                        <h3>4. Perkembangan Kognitif</h3>
                        <p>{!! nl2br(e($laporan->catatan_kognitif ?: '—')) !!}</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="card card-umum">
            <h3>5. Catatan Umum &amp; Saran Stimulasi di Rumah</h3>
            <p>{!! nl2br(e($laporan->catatan_umum ?: 'Tidak ada catatan tambahan pada periode ini.')) !!}</p>
        </div>

        <table class="footer-table">
            <tr>
                <td>
                    Dicetak dari Sistem Manajemen Daycare TitipAnak<br>
                    Tanggal unduh: {{ now()->translatedFormat('d F Y, H:i') }}
                </td>
                <td class="ttd">
                    <div>Pengasuh Pendamping,</div>
                    <div class="ttd-nama">{{ $laporan->pengasuh?->nama ?? 'Pengasuh Daycare' }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>