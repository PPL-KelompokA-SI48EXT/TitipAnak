@php
    $totalTables = collect($groups)->sum(fn ($g) => count($g['tables']));
    $totalRows = collect($groups)->sum(fn ($g) => collect($g['tables'])->sum(fn ($t) => $t['rows']->count()));

    // status => warna badge
    $statusTone = [
        'disetujui' => 'mint', 'lunas' => 'mint', 'terverifikasi' => 'mint', 'aktif' => 'mint', 'terbit' => 'mint', 'baik' => 'mint',
        'menunggu' => 'yellow', 'menunggu_verifikasi' => 'yellow', 'draf' => 'yellow', 'sebagian' => 'yellow',
        'ditolak' => 'coral', 'dibatalkan' => 'coral', 'nonaktif' => 'coral', 'belum_lunas' => 'coral', 'tidak_makan' => 'coral',
        'draft' => 'ink',
    ];
    $statusColumns = ['status', 'status_makan'];
    $booleanColumns = ['aktif'];
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Seeder TitipAnak</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --ta-ink: #1B1B1B; --ta-muted: #6B6B6B; --ta-paper: #FFFFFF; --ta-cream: #FFF6D6;
  --ta-yellow: #FFD43B; --ta-blue: #A8D4FF; --ta-mint: #A8E6CF; --ta-lavender: #CDBDFF; --ta-coral: #FF9E80;
  --ta-link: #1B6FD1;
  --ta-border: 1.5px solid var(--ta-ink); --ta-border-thin: 1px solid var(--ta-ink);
  --ta-radius: 10px;
  --ta-font: "Poppins", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 24px; }
body { margin: 0; background: var(--ta-paper); color: var(--ta-ink); font-family: var(--ta-font); font-size: 16px; line-height: 1.6; -webkit-font-smoothing: antialiased; }
[hidden] { display: none !important; }
:focus-visible { outline: 3px solid var(--ta-link); outline-offset: 2px; }
a { color: var(--ta-link); }
code { background: var(--ta-cream); border: 1px solid var(--ta-ink); border-radius: 6px; padding: 1px 6px; font-size: .85em; }

.layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); min-height: 100vh; }
.nav { position: sticky; top: 0; height: 100vh; overflow-y: auto; padding: 24px; background: var(--ta-cream); border-right: var(--ta-border); }
.brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.15rem; margin-bottom: 24px; color: var(--ta-ink); text-decoration: none; }
.brand__mark { width: 44px; height: 44px; display: grid; place-items: center; background: var(--ta-yellow); border: var(--ta-border); border-radius: 10px; font-size: 1rem; }
.nav h2 { font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: var(--ta-muted); margin: 20px 0 6px; }
.nav a.link { display: flex; justify-content: space-between; gap: 8px; padding: 7px 12px; border: 1.5px solid transparent; border-radius: 8px; color: var(--ta-ink); text-decoration: none; font-size: .875rem; font-weight: 500; }
.nav a.link:hover { background: var(--ta-paper); border-color: var(--ta-ink); }
.nav a.link.is-active { background: var(--ta-yellow); border-color: var(--ta-ink); font-weight: 700; }
.nav a.link span { color: var(--ta-muted); font-weight: 600; }

.main { padding: 40px 32px 64px; min-width: 0; }
.head { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; justify-content: space-between; }
.head h1 { margin: 0; font-size: clamp(1.75rem, 4vw, 2.25rem); line-height: 1.2; }
.head p { margin: 4px 0 0; color: var(--ta-muted); }

.badge { display: inline-flex; align-items: center; font: 600 .75rem/1 var(--ta-font); padding: 6px 12px; border: var(--ta-border-thin); border-radius: 999px; background: var(--ta-paper); white-space: nowrap; }
.badge--yellow { background: var(--ta-yellow); } .badge--blue { background: var(--ta-blue); } .badge--mint { background: var(--ta-mint); }
.badge--lavender { background: var(--ta-lavender); } .badge--coral { background: var(--ta-coral); } .badge--ink { background: var(--ta-ink); color: var(--ta-paper); }

.stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 24px 0; }
.card { border: var(--ta-border); border-radius: var(--ta-radius); padding: 16px 20px; background: var(--ta-paper); }
.card--yellow { background: var(--ta-yellow); } .card--blue { background: var(--ta-blue); } .card--mint { background: var(--ta-mint); }
.stat__num { font-size: 2.5rem; font-weight: 700; line-height: 1; }
.stat__label { font-weight: 600; margin-top: 4px; }

.banner { display: flex; gap: 12px; align-items: center; background: var(--ta-ink); color: var(--ta-paper); border: var(--ta-border); border-radius: var(--ta-radius); padding: 14px 20px; font-weight: 600; font-size: .9rem; margin-bottom: 24px; }
.banner code { color: var(--ta-ink); }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; position: sticky; top: 0; z-index: 10; background: var(--ta-paper); padding: 12px 0; }
.search { position: relative; flex: 1; min-width: 220px; max-width: 520px; }
.search span { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.search input { width: 100%; font: 400 1rem var(--ta-font); color: var(--ta-ink); padding: 11px 14px 11px 42px; border: var(--ta-border); border-radius: var(--ta-radius); background: var(--ta-paper); }
.search input:focus-visible { outline: 3px solid var(--ta-yellow); outline-offset: 0; background: var(--ta-cream); }
.chip { font: 600 .8rem var(--ta-font); padding: 8px 14px; cursor: pointer; border: var(--ta-border-thin); border-radius: 999px; background: var(--ta-paper); color: var(--ta-ink); }
.chip:hover { background: var(--ta-cream); }
.chip[aria-pressed="true"] { background: var(--ta-yellow); border: var(--ta-border); }

.group { margin-top: 48px; }
.group__title { display: inline-flex; align-items: center; gap: 10px; margin: 0 0 16px; font-size: 1.625rem; font-weight: 700; }
.group__title i { width: 14px; height: 14px; border: var(--ta-border); border-radius: 4px; display: inline-block; }
.tbl { margin-bottom: 28px; }
.tbl__head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
.tbl__head h3 { margin: 0; font-size: 1.1rem; font-family: ui-monospace, "Cascadia Code", Consolas, monospace; }
.wrap { border: var(--ta-border); border-radius: var(--ta-radius); overflow: auto; max-height: 440px; background: var(--ta-paper); }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th { position: sticky; top: 0; background: var(--ta-yellow); text-align: left; font-weight: 700; padding: 10px 14px; border-bottom: var(--ta-border); white-space: nowrap; z-index: 1; }
td { padding: 9px 14px; border-bottom: var(--ta-border-thin); vertical-align: middle; max-width: 340px; }
tbody tr:nth-child(even) td { background: var(--ta-cream); }
tbody tr:last-child td { border-bottom: 0; }
td .text { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px; }
.nul { color: var(--ta-muted); }
.thumb { width: 44px; height: 44px; object-fit: cover; border: var(--ta-border); border-radius: 8px; background: var(--ta-cream); display: block; }
.empty { padding: 20px; text-align: center; color: var(--ta-muted); }
.noresult { text-align: center; padding: 48px 16px; border: var(--ta-border); border-radius: var(--ta-radius); background: var(--ta-cream); margin-top: 32px; }

@media (max-width: 960px) {
  .layout { grid-template-columns: 1fr; }
  .nav { position: static; height: auto; border-right: 0; border-bottom: var(--ta-border); }
  .main { padding: 24px 16px 48px; }
  .stats { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="layout">

<nav class="nav" aria-label="Daftar tabel">
  <a class="brand" href="#top"><span class="brand__mark">TA</span> TitipAnak</a>
  @foreach ($groups as $group)
    <h2>{{ $group['title'] }}</h2>
    @foreach ($group['tables'] as $t)
      <a class="link" href="#t-{{ $t['name'] }}" data-nav="{{ $t['name'] }}">{{ $t['name'] }} <span>{{ $t['rows']->count() }}</span></a>
    @endforeach
  @endforeach
</nav>

<main class="main" id="top">
  <header class="head">
    <div>
      <h1>Data Seeder</h1>
      <p>Isi seluruh tabel setelah <code>php artisan migrate:fresh --seed</code></p>
    </div>
    <span class="badge badge--ink">Lokal saja · env: {{ app()->environment() }}</span>
  </header>

  <section class="stats" aria-label="Ringkasan">
    <div class="card card--yellow"><div class="stat__num">{{ $totalTables }}</div><div class="stat__label">Tabel</div></div>
    <div class="card card--blue"><div class="stat__num">{{ number_format($totalRows, 0, ',', '.') }}</div><div class="stat__label">Total baris</div></div>
    <div class="card card--mint"><div class="stat__num">{{ count($groups) }}</div><div class="stat__label">Kelompok</div></div>
  </section>

  <div class="banner">
    <span>🔑</span>
    <span>Semua akun memakai kata sandi <code>password</code>. Contoh: <code>admin@titipanak.test</code>, <code>pengasuh1@titipanak.test</code>, <code>ortu1@titipanak.test</code></span>
  </div>

  <div class="toolbar">
    <label class="search">
      <span aria-hidden="true">🔍</span>
      <input id="q" type="search" placeholder="Cari di semua tabel (nama, email, status, tanggal…)" autocomplete="off" aria-label="Cari di semua tabel">
    </label>
    @foreach ($groups as $i => $group)
      <button type="button" class="chip" data-group="{{ $i }}" aria-pressed="false">{{ $group['title'] }}</button>
    @endforeach
  </div>

  @foreach ($groups as $i => $group)
    <section class="group" data-group="{{ $i }}" aria-labelledby="g-{{ $i }}">
      <h2 class="group__title" id="g-{{ $i }}"><i style="background: var(--ta-{{ $group['color'] }})"></i>{{ $group['title'] }}</h2>

      @foreach ($group['tables'] as $t)
        @php $columns = $t['rows']->isNotEmpty() ? array_keys($t['rows']->first()) : []; @endphp
        <article class="tbl" id="t-{{ $t['name'] }}" data-table="{{ $t['name'] }}">
          <div class="tbl__head">
            <h3>{{ $t['name'] }}</h3>
            <span class="badge badge--{{ $group['color'] }}"><span data-shown>{{ $t['rows']->count() }}</span>&nbsp;/ {{ $t['rows']->count() }} baris</span>
          </div>

          @if ($t['rows']->isEmpty())
            <div class="wrap"><div class="empty">Tabel kosong.</div></div>
          @else
            <div class="wrap">
              <table>
                <thead><tr>@foreach ($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($t['rows'] as $row)
                  <tr data-row="{{ mb_strtolower(collect($row)->filter(fn ($v) => ! is_null($v))->implode(' ')) }}">
                    @foreach ($row as $col => $val)
                      <td>
                        @if (is_null($val))
                          <span class="nul">—</span>
                        @elseif (is_string($val) && preg_match('~^https?://\S+\.(png|jpe?g|webp|gif)$~i', $val))
                          <a href="{{ $val }}" target="_blank" rel="noopener"><img class="thumb" src="{{ $val }}" alt="{{ $col }}" loading="lazy"></a>
                        @elseif (in_array($col, $booleanColumns, true))
                          <span class="badge badge--{{ $val ? 'mint' : 'coral' }}">{{ $val ? 'Ya' : 'Tidak' }}</span>
                        @elseif (in_array($col, $statusColumns, true))
                          <span class="badge badge--{{ $statusTone[$val] ?? 'blue' }}">{{ str_replace('_', ' ', $val) }}</span>
                        @else
                          <span class="text" title="{{ $val }}">{{ $val }}</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </article>
      @endforeach
    </section>
  @endforeach

  <div class="noresult" id="noresult" hidden>
    <div style="font-size:1.25rem;font-weight:700">Tidak ada hasil</div>
    <div class="nul">Coba kata kunci lain atau hapus filter kelompok.</div>
  </div>
</main>
</div>

<script>
(function () {
  var q = document.getElementById('q');
  var chips = [].slice.call(document.querySelectorAll('.chip'));
  var groups = [].slice.call(document.querySelectorAll('section.group'));
  var noResult = document.getElementById('noresult');
  var activeGroup = null;

  function apply() {
    var term = q.value.trim().toLowerCase(), anyVisible = false;
    groups.forEach(function (g) {
      var groupOk = activeGroup === null || g.dataset.group === activeGroup, groupHas = false;
      g.querySelectorAll('.tbl').forEach(function (t) {
        var rows = [].slice.call(t.querySelectorAll('tbody tr')), shown = 0;
        rows.forEach(function (r) {
          var ok = !term || r.dataset.row.indexOf(term) > -1;
          r.hidden = !ok; if (ok) shown++;
        });
        var counter = t.querySelector('[data-shown]'); if (counter) counter.textContent = shown;
        var show = groupOk && (!term || shown > 0 || (rows.length === 0 && false));
        t.hidden = !show; if (show) groupHas = true;
        var link = document.querySelector('[data-nav="' + t.dataset.table + '"]');
        if (link) link.hidden = !show;
      });
      g.hidden = !groupHas; if (groupHas) anyVisible = true;
    });
    noResult.hidden = anyVisible;
  }

  q.addEventListener('input', apply);
  chips.forEach(function (c) {
    c.addEventListener('click', function () {
      activeGroup = activeGroup === c.dataset.group ? null : c.dataset.group;
      chips.forEach(function (x) { x.setAttribute('aria-pressed', x.dataset.group === activeGroup ? 'true' : 'false'); });
      apply();
    });
  });

  /* tandai menu aktif sesuai tabel yang terlihat */
  if ('IntersectionObserver' in window) {
    var links = document.querySelectorAll('.nav a.link');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        links.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + en.target.id); });
      });
    }, { rootMargin: '-15% 0px -75% 0px' });
    document.querySelectorAll('.tbl').forEach(function (t) { io.observe(t); });
  }
})();
</script>
</body>
</html>
