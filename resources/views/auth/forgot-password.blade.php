<x-guest-layout>
    <div class="mb-4 text-light small">
        {{ __('¿Olvidaste tu contraseña? No hay problema. Simplemente haznos saber tu dirección de correo electrónico y te enviaremos un enlace para restablecer la contraseña que te permitirá elegir una nueva.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 alert alert-success" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label text-light">{{ __('Correo Electrónico') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required
                autofocus>
            @error('email')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Enviar enlace de restablecimiento') }}
            </button>
        </div>
    </form>
</x-guest-layout>