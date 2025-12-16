<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label text-light">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required
                autofocus autocomplete="username">
            @error('email')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label text-light">{{ __('Contraseña') }}</label>
            <input id="password" class="form-control" type="password" name="password" required
                autocomplete="current-password">
            @error('password')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-3 form-check">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label text-light small">{{ __('Recordarme') }}</label>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            @if (Route::has('password.request'))
                <a class="text-decoration-none text-brand-secondary small" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <button type="submit" class="btn btn-primary px-4">
                {{ __('Ingresar') }}
            </button>
        </div>
        <div>
            <p>¿No tienes una cuenta? Registrate
                <a href="{{ route('register') }}" class="text-decoration-none text-brand-secondary small">
                    {{ __(' Aquí ') }}
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>