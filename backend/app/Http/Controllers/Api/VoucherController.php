<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $vouchers = Voucher::where('store_id', $profile->store->id)
            ->latest()
            ->get();

        return response()->json($vouchers);
    }

    public function store(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $request->validate([
            'code' => 'required|string|max:20|unique:vouchers,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:1',
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        $voucher = Voucher::create([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'min_order' => $request->min_order ?? 0,
            'max_discount' => $request->max_discount,
            'usage_limit' => $request->usage_limit,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'store_id' => $profile->store->id,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Voucher berhasil dibuat.', 'voucher' => $voucher], 201);
    }

    public function update(Request $request, $id)
    {
        $profile = $request->user()->profile;
        $voucher = Voucher::where('store_id', $profile->store->id)->findOrFail($id);

        $voucher->update(['is_active' => ! $voucher->is_active]);

        return response()->json(['message' => 'Status voucher diperbarui.', 'voucher' => $voucher]);
    }

    public function destroy($id)
    {
        $profile = request()->user()->profile;
        Voucher::where('store_id', $profile->store->id)->where('id', $id)->delete();

        return response()->json(['message' => 'Voucher dihapus.']);
    }
}
