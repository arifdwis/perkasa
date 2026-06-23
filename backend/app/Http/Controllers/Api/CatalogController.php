<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlumniProfile;
use App\Models\Product;
use App\Models\Store;
use App\Models\Wilayah;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Search and filter the catalog for products, stores, and alumni.
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:product,store,alumni'],
        ]);

        $type = $request->type;

        switch ($type) {
            case 'product':
                return $this->searchProducts($request);
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

        if ($request->has('kategori_id') && $request->kategori_id) {
            $query->where('product_category_id', $request->kategori_id);
        }

        if ($request->has('kecamatan') && $request->kecamatan) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('kecamatan', $request->kecamatan);
            });
        }
        if ($request->has('kelurahan') && $request->kelurahan) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('kelurahan', $request->kelurahan);
            });
        }

        if ($request->has('harga_min') && $request->harga_min !== null) {
            $query->where('price', '>=', floatval($request->harga_min));
        }
        if ($request->has('harga_max') && $request->harga_max !== null) {
            $query->where('price', '<=', floatval($request->harga_max));
        }

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
     * Query and filter stores.
     */
    private function searchStores(Request $request)
    {
        $query = Store::with(['alumniProfile.user'])
            ->where('status', 'active');

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

        if ($request->has('kecamatan') && $request->kecamatan) {
            $query->where('kecamatan', $request->kecamatan);
        }
        if ($request->has('kelurahan') && $request->kelurahan) {
            $query->where('kelurahan', $request->kelurahan);
        }

        if ($request->has('kategori') && $request->kategori) {
            $query->where('kategori_usaha', $request->kategori);
        }

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

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uQuery) use ($search) {
                        $uQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('kota') && $request->kota) {
            $query->where('domisili', 'like', "%{$request->kota}%");
        }

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

    /**
     * Get unique kecamatan and kelurahan options from wilayah reference table.
     */
    public function locations()
    {
        $kecamatanList = Wilayah::distinct()->pluck('kecamatan')->sort()->values()->toArray();

        $mapping = [];
        Wilayah::orderBy('kelurahan')->chunk(100, function ($rows) use (&$mapping) {
            foreach ($rows as $row) {
                if (! isset($mapping[$row->kecamatan])) {
                    $mapping[$row->kecamatan] = [];
                }
                $mapping[$row->kecamatan][] = $row->kelurahan;
            }
        });
        foreach ($mapping as $k => &$list) {
            sort($list);
            $list = array_values(array_unique($list));
        }

        return response()->json([
            'kecamatan' => $kecamatanList,
            'kelurahan' => $mapping,
        ]);
    }
}
