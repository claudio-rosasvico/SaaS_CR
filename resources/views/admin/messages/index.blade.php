@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1 class="mb-3" style="font-family:Montserrat;font-weight:800;">Mensajes</h1>

        <form class="mb-3" method="get">
            <select name="status" class="form-select" style="max-width:220px; display:inline-block;">
                <option value="">Todos</option>
                <option value="nuevo" {{ request('status') === 'nuevo' ? 'selected' : '' }}>Nuevos</option>
                <option value="respondido" {{ request('status') === 'respondido' ? 'selected' : '' }}>Respondidos</option>
            </select>
            <button class="btn btn-dark ms-1">Filtrar</button>
        </form>

        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Servicio</th>
                        <th>Status</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messages as $m)
                        <tr>
                            <td>{{ $m->id }}</td>
                            <td>{{ $m->nombre }}</td>
                            <td>{{ $m->email }}</td>
                            <td>{{ $m->servicio }}</td>
                            <td>
                                <span class="badge {{ $m->status === 'nuevo' ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ ucfirst($m->status) }}
                                </span>
                            </td>
                            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td><a class="btn btn-sm btn-outline-warning"
                                    href="{{ route('admin.messages.show', $m) }}">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $messages->links() }}
    </div>
@endsection
