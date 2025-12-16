<x-guest-layout>
    <div class="mb-4 text-light small">
        {{ __('Esta es un área segura de la aplicación. Por favor, confirma tu contraseña antes de continuar.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label text-light">{{ __('Contraseña') }}</label>
            <input id="password" class="form-control" type="password" name="password" required
                autocomplete="current-password">
            @error('password')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Confirmar') }}
            </button>
        </div>
    </form>
</x-guest-layout>