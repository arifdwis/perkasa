<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\AlumniProfile;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Search and filter the catalog for products, services, stores, and alumni.
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:product,service,store,alumni']
        ]);

        $type = $request->type;

        switch ($type) {
            case 'product':
                return $this->searchProducts($request);
            case 'service':
                return $this->searchServices($request);
            case 'store':
                return $this->searchStores($request);
            case 'alumni':
                return $this->searchAlumni($request);
        }
    }

    /**
     * Query and filter products.
     */
    private function searchProducts(Request $request)
    {
        $query = Product::with(['store', 'category', 'primaryImage'])
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->whereIn('status', ['active', 'out_of_stock']);

        // Search name/description or store name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('store', function ($storeQuery) use ($search) {
                      $storeQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if ($request->has('kategori_id') && $request->kategori_id) {
            $query->where('product_category_id', $request->kategori_id);
        }

        // Location / City filter
        if ($request->has('kota') && $request->kota) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('kota', 'like', "%{$request->kota}%");
            });
        }

        // Price filters
        if ($request->has('harga_min') && $request->harga_min !== null) {
            $query->where('price', '>=', floatval($request->harga_min));
        }
        if ($request->has('harga_max') && $request->harga_max !== null) {
            $query->where('price', '<=', floatval($request->harga_max));
        }

        // Owner Alumni Identity Filters
        if ($request->has('program_studi') && $request->program_studi) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('program_studi', $request->program_studi);
            });
        }
        if ($request->has('tahun_masuk') && $request->tahun_masuk) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('tahun_masuk', intval($request->tahun_masuk));
            });
        }
        if ($request->has('tahun_lulus') && $request->tahun_lulus) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('tahun_lulus', intval($request->tahun_lulus));
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Query and filter services.
     */
    private function searchServices(Request $request)
    {
        $query = Service::with(['store', 'category', 'primaryImage'])
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->where('status', 'active');

        // Search name/description or store name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('store', function ($storeQuery) use ($search) {
                      $storeQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if ($request->has('kategori_id') && $request->kategori_id) {
            $query->where('service_category_id', $request->kategori_id);
        }

        // Location filter (match lokasi_layanan or store city)
        if ($request->has('kota') && $request->kota) {
            $kota = $request->kota;
            $query->where(function ($q) use ($kota) {
                $q->where('lokasi_layanan', 'like', "%{$kota}%")
                  ->orWhereHas('store', function ($storeQuery) use ($kota) {
                      $storeQuery->where('kota', 'like', "%{$kota}%");
                  });
            });
        }

        // Price starting from filters
        if ($request->has('harga_min') && $request->harga_min !== null) {
            $query->where('price_from', '>=', floatval($request->harga_min));
        }
        if ($request->has('harga_max') && $request->harga_max !== null) {
            $query->where('price_from', '<=', floatval($request->harga_max));
        }

        // Owner Alumni Identity Filters
        if ($request->has('program_studi') && $request->program_studi) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('program_studi', $request->program_studi);
            });
        }
        if ($request->has('tahun_masuk') && $request->tahun_masuk) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('tahun_masuk', intval($request->tahun_masuk));
            });
        }
        if ($request->has('tahun_lulus') && $request->tahun_lulus) {
            $query->whereHas('store.alumniProfile', function ($q) use ($request) {
                $q->where('tahun_lulus', intval($request->tahun_lulus));
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_from', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_from', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Query and filter stores.
     */
    private function searchStores(Request $request)
    {
        $query = Store::with(['alumniProfile.user'])
            ->where('status', 'active');

        // Search store name/description or owner user name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('alumniProfile.user', function ($uQuery) use ($search) {
                      $uQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // City filter
        if ($request->has('kota') && $request->kota) {
            $query->where('kota', 'like', "%{$request->kota}%");
        }

        // Category filter (kategori_usaha)
        if ($request->has('kategori') && $request->kategori) {
            $query->where('kategori_usaha', $request->kategori);
        }

        // Owner Alumni Identity Filters
        if ($request->has('program_studi') && $request->program_studi) {
            $query->whereHas('alumniProfile', function ($q) use ($request) {
                $q->where('program_studi', $request->program_studi);
            });
        }
        if ($request->has('tahun_masuk') && $request->tahun_masuk) {
            $query->whereHas('alumniProfile', function ($q) use ($request) {
                $q->where('tahun_masuk', intval($request->tahun_masuk));
            });
        }
        if ($request->has('tahun_lulus') && $request->tahun_lulus) {
            $query->whereHas('alumniProfile', function ($q) use ($request) {
                $q->where('tahun_lulus', intval($request->tahun_lulus));
            });
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(15));
    }

    /**
     * Query and filter alumni profiles.
     */
    private function searchAlumni(Request $request)
    {
        $query = AlumniProfile::with(['user'])
            ->where('status_verifikasi', 'verified');

        // Search user name or nim
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uQuery) use ($search) {
                      $uQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // City domisili filter
        if ($request->has('kota') && $request->kota) {
            $query->where('domisili', 'like', "%{$request->kota}%");
        }

        // Identity Filters
        if ($request->has('program_studi') && $request->program_studi) {
            $query->where('program_studi', $request->program_studi);
        }
        if ($request->has('tahun_masuk') && $request->tahun_masuk) {
            $query->where('tahun_masuk', intval($request->tahun_masuk));
        }
        if ($request->has('tahun_lulus') && $request->tahun_lulus) {
            $query->where('tahun_lulus', intval($request->tahun_lulus));
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(15));
    }
}
