<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh' => $request->keys['p256dh'],
                'auth' => $request->keys['auth'],
            ]
        );

        return response()->json(['message' => 'OK']);
    }

    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['message' => 'OK']);
    }
}
