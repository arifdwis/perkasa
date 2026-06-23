<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\ReplyReviewRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Notifications\NewReviewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews filtered by product or store.
     */
    public function index(Request $request)
    {
        $query = Review::with(['user.profile']);

        if ($request->has('product_id') && $request->product_id) {
            $query->where('reviewable_type', Product::class)
                ->where('reviewable_id', $request->product_id);
        } elseif ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * Create a new review (Product only).
     */
    public function store(CreateReviewRequest $request)
    {
        Gate::authorize('create', Review::class);

        $type = $request->reviewable_type;
        $orderItemId = null;
        $storeId = null;
        $reviewableType = null;
        $reviewableId = null;

        if ($type !== 'product') {
            return response()->json([
                'message' => 'Tipe ulasan tidak valid.',
            ], 400);
        }

        if (! $request->order_item_id) {
            return response()->json([
                'message' => 'ID item pesanan wajib disertakan untuk mengulas produk.',
            ], 400);
        }

        $orderItem = OrderItem::with('order.store')->findOrFail($request->order_item_id);
        $order = $orderItem->order;

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk mengulas pesanan ini.',
            ], 403);
        }

        if ($order->status !== 'selesai') {
            return response()->json([
                'message' => 'Ulasan hanya dapat dibuat setelah status pesanan selesai.',
            ], 400);
        }

        $exists = Review::where('order_item_id', $request->order_item_id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Anda sudah mengulas item pesanan ini.',
            ], 400);
        }

        $orderItemId = $orderItem->id;
        $storeId = $order->store_id;
        $reviewableType = Product::class;
        $reviewableId = $orderItem->product_id;

        $review = Review::create([
            'user_id' => $request->user()->id,
            'order_item_id' => $orderItemId,
            'store_id' => $storeId,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        activity()
            ->performedOn($review)
            ->log('Memberikan ulasan bintang '.$review->rating.' untuk produk.');

        $store = Store::find($storeId);
        if ($store) {
            $seller = $store->alumniProfile?->user ?? $store->alumni_profile?->user;
            if ($seller) {
                $itemName = $orderItem->name;
                $slugOrOrderId = $order->id;
                $seller->notify(new NewReviewNotification($review, $itemName, $slugOrOrderId));
            }
        }

        return response()->json([
            'message' => 'Ulasan berhasil disimpan.',
            'review' => $review->load(['user.profile']),
        ], 201);
    }

    /**
     * Reply to a review (Seller).
     */
    public function reply(ReplyReviewRequest $request, $id)
    {
        $review = Review::findOrFail($id);

        Gate::authorize('reply', $review);

        $review->update([
            'reply' => $request->reply,
            'replied_at' => now(),
        ]);

        activity()
            ->performedOn($review)
            ->log('Membalas ulasan pembeli.');

        return response()->json([
            'message' => 'Ulasan berhasil dibalas.',
            'review' => $review->load(['user.profile']),
        ]);
    }
}
