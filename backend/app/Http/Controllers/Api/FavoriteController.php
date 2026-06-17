<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Toggle a model favorite status (Add/Remove).
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'favoritable_id' => ['required', 'uuid'],
            'favoritable_type' => ['required', 'string', 'in:product,service,store']
        ]);

        $id = $request->favoritable_id;
        $typeString = $request->favoritable_type;

        // Map type string to full model class namespace
        $modelClass = null;
        switch ($typeString) {
            case 'product':
                $modelClass = Product::class;
                break;
            case 'service':
                $modelClass = Service::class;
                break;
            case 'store':
                $modelClass = Store::class;
                break;
        }

        // Verify if the target model exists
        $target = $modelClass::find($id);
        if (!$target) {
            return response()->json(['message' => 'Item tidak ditemukan.'], 404);
        }

        $userId = $request->user()->id;

        $existing = Favorite::where('user_id', $userId)
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $modelClass)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'favorited' => false,
                'message' => 'Berhasil dihapus dari favorit.'
            ]);
        } else {
            Favorite::create([
                'user_id' => $userId,
                'favoritable_id' => $id,
                'favoritable_type' => $modelClass
            ]);
            return response()->json([
                'favorited' => true,
                'message' => 'Berhasil ditambahkan ke favorit.'
            ]);
        }
    }

    /**
     * List all favorites of the authenticated user.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Fetch all favorites
        $favorites = Favorite::where('user_id', $userId)->get();

        $productIds = [];
        $serviceIds = [];
        $storeIds = [];

        foreach ($favorites as $fav) {
            if ($fav->favoritable_type === Product::class) {
                $productIds[] = $fav->favoritable_id;
            } elseif ($fav->favoritable_type === Service::class) {
                $serviceIds[] = $fav->favoritable_id;
            } elseif ($fav->favoritable_type === Store::class) {
                $storeIds[] = $fav->favoritable_id;
            }
        }

        // Retrieve items with matching relations
        $products = Product::with(['category', 'primaryImage', 'store'])
            ->whereIn('id', $productIds)
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->get();

        $services = Service::with(['category', 'primaryImage', 'store'])
            ->whereIn('id', $serviceIds)
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->get();

        $stores = Store::with(['alumniProfile.user'])
            ->whereIn('id', $storeIds)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'products' => $products,
            'services' => $services,
            'stores' => $stores
        ]);
    }
}
