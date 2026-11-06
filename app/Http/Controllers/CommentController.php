<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        Comment::create([
            'message' => $request->message,
            'ticket_id' => $request->ticket_id,
            'user_id' => 1
        ]);

        return back();
    }
}