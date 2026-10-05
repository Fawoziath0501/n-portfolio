<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Message;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function updateMessage(Request $request, Message $message)
    {
        $request->validate(['status' => 'required|in:new,read,replied,archived']);
        $message->update(['status' => $request->input('status')]);
        if ($msg = $request->input('activity')) {
            Activity::log($msg);
        }

        return response()->json($message->toFront());
    }

    public function destroyMessage(Message $message)
    {
        $message->delete();
        Activity::log('Message supprimé');

        return response()->noContent();
    }

    public function destroySubscriber(Subscriber $subscriber)
    {
        $subscriber->delete();
        Activity::log('Inscrit retiré');

        return response()->noContent();
    }
}
