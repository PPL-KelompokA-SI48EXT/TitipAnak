<div class="mx-auto w-full max-w-md">
    {{-- Kartu Form Login --}}
    <div class="rounded-ta border-[1.5px] border-ink bg-paper p-6 sm:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight">Masuk ke TitipAnak</h1>
            <p class="mt-1 text-sm text-muted">Gunakan email dan kata sandi Anda untuk mengakses akun.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-center gap-2.5 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm font-medium">
                <span aria-hidden="true">✅</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form wire:submit="login" class="grid gap-5">
            {{-- Input Email --}}
            <div class="grid gap-1.5 @error('email') field--error @enderror">
                <label for="email" class="text-sm font-semibold">Alamat Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    placeholder="nama@email.com"
                    autocomplete="email"
                    required
                    autofocus
                    class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-base text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow @error('email') border-[#C2371A] bg-[#FFF1EC] @enderror"
                />
                @error('email')
                    <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span>
                @enderror
            </div>

            {{-- Input Kata Sandi --}}
            <div class="grid gap-1.5 @error('password') field--error @enderror" x-data="{ showPass: false }">
                <div class="flex items-center justify-between">
                    <label for="password" class="text-sm font-semibold">Kata Sandi</label>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        :type="showPass ? 'text' : 'password'"
                        wire:model="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 pr-11 text-base text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow @error('password') border-[#C2371A] bg-[#FFF1EC] @enderror"
                    />
                    <button
                        type="button"
                        @click="showPass = !showPass"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-sm text-muted hover:text-ink focus-visible:outline-none"
                        :aria-label="showPass ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    >
                        <span x-text="showPass ? '🙈' : '👁️'">👁️</span>
                    </button>
                </div>
                @error('password')
                    <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span>
                @enderror
            </div>

            {{-- Ingat Saya --}}
            <div class="flex items-center justify-between">
                <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        id="remember"
                        type="checkbox"
                        wire:model="remember"
                        class="size-4 rounded border-[1.5px] border-ink accent-yellow focus:ring-0"
                    />
                    <span class="text-sm">Ingat saya di perangkat ini</span>
                </label>
            </div>

            {{-- Tombol Masuk --}}
            <div class="mt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-base font-semibold text-ink transition hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="login">Masuk ke Akun</span>
                    <span wire:loading wire:target="login">Memeriksa…</span>
                </button>
            </div>
        </form>

        {{-- Hubungan ke dev switcher saat lokal --}}
        {{-- @if (config('titipanak.dev_switcher')) --}}
            <!-- <div class="mt-6 border-t-[1.5px] border-ink/20 pt-4 text-center">
                <p class="text-xs text-muted mb-2">Sedang dalam lingkungan pengembangan?</p>
                <a href="{{ route('dev.akun') }}" class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-cream px-3 py-1 text-xs font-semibold hover:bg-yellow transition">
                    <span>⚡ Buka Pemilih Akun Dev</span>
                </a>
            </div> -->
        {{-- @endif --}}
    </div>
</div>

