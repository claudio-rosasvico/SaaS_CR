<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        @if (!empty($logo))
            <div class="text-center mb-3">
                {{ $logo }}
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
    