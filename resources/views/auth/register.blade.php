<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label text-light">{{ __('Nombre') }}</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus
                autocomplete="name">
            @error('name')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Organization Name -->
        <div class="mb-3">
            <label for="organization_name" class="form-label text-light">{{ __('Nombre de la Organización') }}</label>
            <input id="organization_name" class="form-control" type="text" name="organization_name"
                value="{{ old('organization_name') }}" required autocomplete="organization">
            @error('organization_name')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label text-light">{{ __('Correo Electrónico') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required
                autocomplete="username">
            @error('email')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label text-light">{{ __('Contraseña') }}</label>
            <input id="password" class="form-control" type="password" name="password" required
                autocomplete="new-password">
            @error('password')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="password_confirmation" class="form-label text-light">{{ __('Confirmar Contraseña') }}</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required
                autocomplete="new-password">
            @error('password_confirmation')
                <div class="text-danger mt-1 small">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <a class="text-decoration-none text-brand-secondary small" href="{{ route('login') }}">
                {{ __('¿Ya estás registrado?') }}
            </a>

            <button type="submit" class="btn btn-primary px-4">
                {{ __('Registrarse') }}
            </button>
        </div>
    </form>
</x-guest-layout>