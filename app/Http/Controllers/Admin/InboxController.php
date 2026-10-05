<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Message;
use App\Models\Subscriber;
use App\Support\Portfolio;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function updateMessage(Request $request, Message $message)
    {
        $request->validate(['status' => 'required|in:new,read,replied,archived']);
        $message->update(['status' => $request->input('status')]);
        Activity::log($request->input('activity'));

        return response()->json($message->toFront());
    }

    /** Message placé dans la corbeille. */
    public function destroyMessage(Message $message)
    {
        $message->delete();
        Activity::log('Message placé dans la corbeille');

        return response()->json(['trashCount' => Portfolio::trashCount()]);
    }

    /** Inscrit placé dans la corbeille (une réinscription le restaure). */
    public function destroySubscriber(Subscriber $subscriber)
    {
        $subscriber->delete();
        Activity::log('Inscrit placé dans la corbeille');

        return response()->json(['trashCount' => Portfolio::trashCount()]);
    }
}
