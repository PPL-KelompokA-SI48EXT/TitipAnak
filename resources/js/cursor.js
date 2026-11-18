// Kursor khusus TitipAnak: kepala beruang kecil (SVG buatan sendiri) yang mengikuti mouse,
// memiringkan badan saat digerakkan, berganti ekspresi di atas tautan/saat klik, dan
// meninggalkan jejak emoji. Kursor asli hanya disembunyikan di dalam halaman web ini.
// Hanya aktif di perangkat dengan mouse. Jejak dan goyangan dimatikan kalau pengguna meminta gerak dikurangi.
const BISA_DIKLIK = "a, button, summary, label, select, [role='button']";
const KOLOM_TEKS = "input:not([type='checkbox']):not([type='radio']):not([type='button']):not([type='submit']):not([type='range']):not([type='file']), textarea, [contenteditable='true']";
const EMOJI = ['🐾', '⭐', '💛', '🧸', '✨', '🎈', '🌈', '🍼', '🦋', '🌼'];
const JEJAK_MAKS = 40;
const JARAK_JEJAK = 30; // px yang ditempuh sebelum satu emoji baru muncul

const BERUANG = `
<svg viewBox="0 0 48 48" width="42" height="42" xmlns="http://www.w3.org/2000/svg" focusable="false">
  <g stroke="#1B1B1B" stroke-width="2.2" stroke-linejoin="round">
    <circle cx="10.5" cy="11" r="7.2" fill="#FFD43B"/>
    <circle cx="37.5" cy="11" r="7.2" fill="#FFD43B"/>
    <circle cx="24" cy="26.5" r="17.5" fill="#FFD43B"/>
  </g>
  <circle cx="10.5" cy="11.2" r="3.4" fill="#FF9E80"/>
  <circle cx="37.5" cy="11.2" r="3.4" fill="#FF9E80"/>
  <ellipse cx="24" cy="32.5" rx="8" ry="5.8" fill="#FFF6D6" stroke="#1B1B1B" stroke-width="1.4"/>
  <ellipse cx="11.8" cy="29.5" rx="3.6" ry="2.4" fill="#FF9E80" opacity=".75"/>
  <ellipse cx="36.2" cy="29.5" rx="3.6" ry="2.4" fill="#FF9E80" opacity=".75"/>
  <g class="mata-biasa">
    <circle cx="16.8" cy="23" r="2.6" fill="#1B1B1B"/><circle cx="31.2" cy="23" r="2.6" fill="#1B1B1B"/>
    <circle cx="17.7" cy="22" r="0.9" fill="#FFFFFF"/><circle cx="32.1" cy="22" r="0.9" fill="#FFFFFF"/>
  </g>
  <g class="mata-senang" fill="none" stroke="#1B1B1B" stroke-width="2.2" stroke-linecap="round">
    <path d="M14 24.2 Q16.8 19.8 19.6 24.2"/><path d="M28.4 24.2 Q31.2 19.8 34 24.2"/>
  </g>
  <ellipse cx="24" cy="29.6" rx="2.8" ry="2.1" fill="#1B1B1B"/>
  <path d="M24 31.6 V33.6 M20.6 33.9 Q24 37.6 27.4 33.9" fill="none" stroke="#1B1B1B" stroke-width="1.7" stroke-linecap="round"/>
</svg>`;

function pasangKursor() {
    if (!matchMedia('(pointer: fine)').matches) return;

    const kurangiGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;

    const lapisan = document.createElement('div');
    lapisan.className = 'kursor-lapisan';
    lapisan.setAttribute('aria-hidden', 'true');
    const beruang = document.createElement('div');
    beruang.className = 'kursor-beruang';
    beruang.innerHTML = BERUANG;
    lapisan.append(beruang);
    document.body.append(lapisan);

    root.classList.add('kursor-lucu');
    root.dataset.kursor = 'sembunyi';

    let x = 0;
    let y = 0;
    let terakhirX = null;
    let terakhirY = null;
    let sisaJarak = 0;
    let miring = 0;
    let targetMiring = 0;
    let jumlahJejak = 0;

    const emojiAcak = () => EMOJI[Math.floor(Math.random() * EMOJI.length)];

    const lepas = (px, py, { dx, dy, ukuran, durasi, putar }) => {
        const p = document.createElement('span');
        p.textContent = emojiAcak();
        p.setAttribute('aria-hidden', 'true');
        p.style.cssText = `position:fixed;left:${px}px;top:${py}px;z-index:99;pointer-events:none;line-height:1;font-size:${ukuran}px;will-change:transform,opacity`;
        document.body.append(p);
        jumlahJejak++;
        p.animate(
            [
                { transform: 'translate(-50%,-50%) scale(.4) rotate(0deg)', opacity: 1 },
                { transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) scale(1.05) rotate(${putar}deg)`, opacity: 0 },
            ],
            { duration: durasi, easing: 'ease-out' },
        ).onfinish = () => {
            p.remove();
            jumlahJejak--;
        };
    };

    const rand = (a, b) => a + Math.random() * (b - a);

    const jejak = (px, py) =>
        jumlahJejak < JEJAK_MAKS &&
        lepas(px + rand(-6, 6), py + rand(-6, 6), { dx: rand(-18, 18), dy: rand(-40, -14), ukuran: rand(12, 20), durasi: rand(750, 1100), putar: rand(-50, 50) });

    const percik = (px, py) => {
        for (let i = 0; i < 7; i++) {
            const sudut = (Math.PI * 2 * i) / 7 + rand(0, 0.5);
            const jarak = rand(30, 62);
            lepas(px, py, { dx: Math.cos(sudut) * jarak, dy: Math.sin(sudut) * jarak - 14, ukuran: rand(14, 24), durasi: rand(650, 900), putar: rand(-90, 90) });
        }
    };

    if (!kurangiGerak) {
        const putar = () => {
            miring += (targetMiring - miring) * 0.2;
            targetMiring *= 0.85;
            beruang.style.setProperty('--miring', `${miring.toFixed(2)}deg`);
            requestAnimationFrame(putar);
        };
        requestAnimationFrame(putar);
    }

    addEventListener('mousemove', (e) => {
        x = e.clientX;
        y = e.clientY;
        lapisan.style.transform = `translate3d(${x}px, ${y}px, 0)`;

        const sasaran = e.target instanceof Element ? e.target : null;
        const keadaan = sasaran?.closest(KOLOM_TEKS) ? 'teks' : sasaran?.closest(BISA_DIKLIK) ? 'tautan' : 'diam';
        root.dataset.kursor = keadaan;

        if (terakhirX !== null && !kurangiGerak) {
            const dx = x - terakhirX;
            const dy = y - terakhirY;
            targetMiring = Math.max(-16, Math.min(16, dx * 0.7));

            if (keadaan !== 'teks') {
                sisaJarak += Math.hypot(dx, dy);
                if (sisaJarak >= JARAK_JEJAK) {
                    sisaJarak = 0;
                    jejak(x, y);
                }
            }
        }
        terakhirX = x;
        terakhirY = y;
    });

    addEventListener('mousedown', (e) => {
        root.setAttribute('data-ditekan', '');
        if (e.button === 0 && !kurangiGerak && !(e.target instanceof Element && e.target.closest(KOLOM_TEKS))) percik(e.clientX, e.clientY);
    });
    addEventListener('mouseup', () => root.removeAttribute('data-ditekan'));
    document.addEventListener('mouseleave', () => {
        root.dataset.kursor = 'sembunyi';
        terakhirX = null;
        terakhirY = null;
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', pasangKursor);
} else {
    pasangKursor();
}
