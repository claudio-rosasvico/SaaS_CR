<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label text-light">{{ __('Correo Electrónico') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}"
                required autofocus autocomplete="username">
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

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Restablecer Contraseña') }}
            </button>
        </div>
    </form>
</x-guest-layout>