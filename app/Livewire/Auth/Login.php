<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Masuk ke Akun')]
#[Layout('layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'email' => 'alamat email',
            'password' => 'kata sandi',
        ];
    }

    public function login(): mixed
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Cari akun pengguna
        $user = User::where('email', $this->email)->first();

        if (! $user || ! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Cek status aktif akun
        if (! $user->aktif) {
            Auth::logout();
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin daycare.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $user->update(['terakhir_login' => now()]);

        return redirect()->intended(self::redirectPathForUser($user));
    }

    public static function redirectPathForUser(User $user): string
    {
        if ($user->hasRole('admin')) {
            return route('dashboard');
        }

        if ($user->hasRole('pengasuh')) {
            return route('dashboard');
        }

        if ($user->hasRole('orang_tua')) {
            return route('dashboard');
        }

        return route('dashboard');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
