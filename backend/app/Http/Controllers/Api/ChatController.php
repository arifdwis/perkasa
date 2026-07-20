<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\ImageService;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->with([
                'store:id,name,logo',
                'product:id,name,slug',
                'lastMessage.user:id,name',
                'participants.user:id,name',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId)
                    ->where(function ($q2) use ($userId) {
                        $q2->whereDoesntHave('conversation.participants', function ($q3) use ($userId) {
                            $q3->where('user_id', $userId)
                                ->whereColumn('messages.created_at', '<=', 'conversation_participants.last_read_at');
                        });
                    });
            }])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->latest()
                    ->take(1)
            )
            ->paginate(20);

        return response()->json($conversations);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $conversation = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->with([
                'store:id,name,logo',
                'product:id,name,slug,price,stock,product_category_id,product_type,pre_order_deadline,pre_order_estimated_ship',
                'product.images',
                'product.category',
                'product.variants',
                'product.variants.images',
                'participants.user:id,name',
            ])
            ->findOrFail($id);

        $messages = $conversation->messages()
            ->with(['user:id,name', 'product:id,name,slug,price,stock,product_category_id', 'product.images', 'product.category'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        $participant = $conversation->participants()->where('user_id', $userId)->first();
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|string|exists:products,id',
            'store_id' => 'required|string|exists:stores,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $userId = $request->user()->id;
        $store = Store::findOrFail($request->store_id);

        $sellerUserId = $store->alumniProfile->user_id;

        if ($userId === $sellerUserId) {
            return response()->json(['message' => 'Tidak bisa chat dengan toko sendiri.'], 422);
        }

        $conversation = DB::transaction(function () use ($request, $userId, $store, $sellerUserId) {
            $conversation = Conversation::where('store_id', $store->id)
                ->where(function ($q) use ($request) {
                    if ($request->product_id) {
                        $q->where('product_id', $request->product_id);
                    } else {
                        $q->whereNull('product_id');
                    }
                })
                ->whereHas('participants', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereHas('participants', function ($q) use ($sellerUserId) {
                    $q->where('user_id', $sellerUserId);
                })
                ->first();

            $isNew = false;
            $lastMessage = null;

            if (! $conversation) {
                $isNew = true;
                $conversation = Conversation::create([
                    'product_id' => $request->product_id,
                    'store_id' => $store->id,
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'last_read_at' => now(),
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $sellerUserId,
                ]);
            }

            // Send product card only once (on new conversation with product)
            if ($isNew && $request->product_id) {
                $existingCard = $conversation->messages()
                    ->where('type', 'product_card')
                    ->where('product_id', $request->product_id)
                    ->exists();

                if (! $existingCard) {
                    $lastMessage = Message::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'type' => 'product_card',
                        'text' => '',
                        'product_id' => $request->product_id,
                    ]);
                }
            }

            // Send text message if provided
            if ($request->filled('message')) {
                $lastMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'type' => 'text',
                    'text' => $request->message,
                ]);
            }

            if ($lastMessage) {
                $conversation->update(['last_message_id' => $lastMessage->id]);

                $participant = $conversation->participants()
                    ->where('user_id', $userId)
                    ->first();
                if ($participant) {
                    $participant->update(['last_read_at' => now()]);
                }

                $seller = User::find($sellerUserId);
                if ($seller) {
                    $seller->notify(new NewMessageNotification($conversation, $lastMessage));
                    app(WebPushService::class)->sendToUser(
                        $sellerUserId,
                        'Chat Baru: ' . ($store->name ?? 'Toko'),
                        ($lastMessage->user->name ?? 'Pembeli') . ': ' . mb_strimwidth($lastMessage->text ?: 'Mengirim kartu produk', 0, 100, '...'),
                        '/logo_unmul.png',
                        '/seller/chat/' . $conversation->id
                    );
                }
            }

            return $conversation;
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => 'Pesan berhasil dikirim.',
        ], 201);
    }

    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'text' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if (! $request->filled('text') && ! $request->hasFile('image') && ! $request->filled('latitude')) {
            return response()->json(['message' => 'Pesan tidak boleh kosong.'], 422);
        }

        $userId = $request->user()->id;
        $conversation = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->findOrFail($id);

        $data = [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'type' => 'text',
        ];

        if ($request->hasFile('image')) {
            $path = (new ImageService)->storeAsWebP($request->file('image'), 'chat/images');
            $data['type'] = 'image';
            $data['image_path'] = $path;
            $data['text'] = $request->text ?: null;
        } elseif ($request->filled('latitude') && $request->filled('longitude')) {
            $data['type'] = 'location';
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
            $data['text'] = $request->text ?: null;
        } else {
            $data['text'] = $request->text;
        }

        $message = Message::create($data);

        $conversation->update(['last_message_id' => $message->id]);

        $participant = $conversation->participants()
            ->where('user_id', $userId)
            ->first();
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        $otherParticipant = $conversation->participants()
            ->where('user_id', '!=', $userId)
            ->first();
        if ($otherParticipant) {
            $otherUser = User::find($otherParticipant->user_id);
            if ($otherUser) {
                $otherUser->notify(new NewMessageNotification($conversation, $message));

                $preview = match ($message->type) {
                    'image' => '[Foto]',
                    'location' => '[Lokasi]',
                    default => mb_strimwidth($message->text, 0, 100, '...'),
                };

                $store = $conversation->store;
                $isStoreOwner = $otherUser->id === ($store->alumniProfile->user_id ?? null);
                app(WebPushService::class)->sendToUser(
                    $otherUser->id,
                    'Chat Baru: ' . ($message->user->name ?? 'Pengguna'),
                    $preview,
                    '/logo_unmul.png',
                    $isStoreOwner ? '/seller/chat/' . $conversation->id : '/buyer/chat/' . $conversation->id
                );
            }
        }

        return response()->json([
            'message' => $message->load('user:id,name'),
        ], 201);
    }

    public function markRead(Request $request, $id)
    {
        $userId = $request->user()->id;

        $conversation = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->findOrFail($id);

        $participant = $conversation->participants()
            ->where('user_id', $userId)
            ->first();
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $count = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->whereHas('messages', function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId);
            })
            ->get()
            ->sum(fn ($c) => $c->unreadCountFor($request->user()));

        return response()->json(['unread_count' => $count]);
    }
}
