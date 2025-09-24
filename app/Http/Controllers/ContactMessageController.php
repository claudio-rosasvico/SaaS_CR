<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserAutoReply;
use App\Mail\AdminNewMessage;
use App\Mail\AdminReplyToUser;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Validación
        $data = $request->validate([
            'nombre'   => ['required', 'string', 'min:3', 'max:120'],
            'email'    => ['required', 'email', 'max:160'],
            'empresa'  => ['nullable', 'string', 'max:160'],
            'servicio' => ['required', 'in:chatbots,backoffice'],
            'mensaje'  => ['nullable', 'string', 'max:4000'],
            'website'  => ['nullable', 'string', 'max:120'], // honeypot
        ]);

        // Honeypot: si viene con contenido => ignorar
        if (!empty($data['website'])) {
            return response()->json(['ok' => true]); // silencioso contra bots
        }

        $msg = ContactMessage::create([
            ...$data,
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'referer'    => substr((string) $request->headers->get('referer'), 0, 255),
            'status'     => 'nuevo',
        ]);

        // Autorespuesta al usuario
        try {
            Mail::to($msg->email)->send(new UserAutoReply($msg));
        } catch (\Throwable $e) {
            // Log opcional
        }

        // Aviso al admin
        try {
            $adminTo = config('mail.from.address'); // o .env ADMIN_EMAIL
            Mail::to($adminTo)->send(new AdminNewMessage($msg));
        } catch (\Throwable $e) {
        }

        // Respuesta
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $msg->id]);
        }

        return back()->with('ok', '¡Gracias! Te contactaremos en breve para coordinar la demo.');
    }

    // Mini panel
    public function index(Request $request)
    {
        $q = ContactMessage::query()
            ->when($request->status, fn($qq) => $qq->where('status', $request->status))
            ->latest();

        $messages = $q->paginate(12)->withQueryString();
        return view('admin.messages.index', compact('messages'));
    }

    public function show(ContactMessage $message)
    {
        return view('admin.messages.show', compact('message'));
    }

    public function reply(Request $request, ContactMessage $message)
    {
        $data = $request->validate([
            'response_subject' => ['required', 'string', 'max:180'],
            'response_body'    => ['required', 'string', 'max:12000'],
            'responded_by'     => ['nullable', 'string', 'max:160'],
        ]);

        // Enviar respuesta al usuario
        Mail::to($message->email)->send(new AdminReplyToUser($message, $data['response_subject'], $data['response_body']));

        // Persistir
        $message->update([
            'response_subject' => $data['response_subject'],
            'response_body'    => $data['response_body'],
            'responded_by'     => $data['responded_by'] ?? 'admin@shiftia',
            'status'           => 'respondido',
            'responded_at'     => now(),
        ]);

        return back()->with('ok', 'Respuesta enviada.');
    }
}
