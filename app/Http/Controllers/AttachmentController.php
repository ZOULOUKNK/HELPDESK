<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Téléchargement d'une pièce jointe
    |--------------------------------------------------------------------------
    | Les fichiers sont rangés sur le disque privé (storage/app/private) et
    | servis uniquement ici, avec les mêmes règles que l'affichage du ticket :
    | - un client ne voit que les pièces jointes de ses propres tickets ;
    | - les pièces jointes d'une note interne sont réservées au support.
    | Les fichiers envoyés avant ce changement restent sur le disque public
    | et sont servis depuis celui-ci.
    */
    public function download(Attachment $attachment)
    {
        $user = Auth::user();
        $attachment->loadMissing(['ticket', 'reply']);

        if ($user->isClient()) {
            if ($attachment->ticket?->user_id !== $user->id || $attachment->reply?->is_internal) {
                abort(403);
            }
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($attachment->path)) {
                return Storage::disk($disk)->download($attachment->path, $attachment->filename);
            }
        }

        abort(404);
    }
}
