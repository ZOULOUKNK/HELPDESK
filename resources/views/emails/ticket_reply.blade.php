<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Réponse à votre ticket {{ $ticket->reference }}</title>
</head>
<body style="font-family:'Inter',Arial,sans-serif;background:#f8fafc;margin:0;padding:32px 16px;">
<div style="max-width:560px;margin:0 auto;">
    <div style="background:#2563eb;border-radius:12px 12px 0 0;padding:24px 32px;">
        <h1 style="color:#fff;font-size:18px;font-weight:700;margin:0;">Support Helpdesk</h1>
        <p style="color:#bfdbfe;font-size:13px;margin:4px 0 0;">Votre demande a reçu une réponse</p>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-top:0;padding:32px;border-radius:0 0 12px 12px;">
        <p style="color:#475569;font-size:15px;margin:0 0 16px;">
            Bonjour <strong style="color:#1e293b;">{{ $ticket->user->name }}</strong>,
        </p>
        <p style="color:#475569;font-size:15px;margin:0 0 24px;">
            Une réponse a été apportée à votre ticket
            <strong style="color:#2563eb;">{{ $ticket->reference }}</strong>.
        </p>

        <div style="background:#f1f5f9;border-radius:8px;padding:12px 16px;margin-bottom:20px;">
            <p style="color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Sujet</p>
            <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0;">{{ $ticket->title }}</p>
        </div>

        <div style="background:#f8fafc;border-left:4px solid #2563eb;padding:14px 18px;border-radius:0 8px 8px 0;margin-bottom:28px;">
            <p style="color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;margin:0 0 8px;">
                RÉPONSE DE {{ strtoupper($reply->user->name) }}
            </p>
            <p style="color:#334155;font-size:14px;line-height:1.6;margin:0;white-space:pre-wrap;">{{ $reply->body }}</p>
        </div>

        <a href="{{ route('tickets.show',$ticket) }}"
           style="display:inline-block;background:#2563eb;color:#fff;font-weight:600;font-size:14px;padding:12px 24px;border-radius:8px;text-decoration:none;">
            Voir le ticket complet →
        </a>

        <hr style="border:0;border-top:1px solid #e2e8f0;margin:28px 0 20px;">
        <p style="color:#94a3b8;font-size:12px;margin:0;line-height:1.6;">
            Ce message est envoyé automatiquement. Merci de ne pas y répondre directement.<br>
            Référence : {{ $ticket->reference }}
        </p>
    </div>
</div>
</body>
</html>