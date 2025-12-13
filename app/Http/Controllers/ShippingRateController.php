<?php

namespace App\Http\Controllers;

use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingRateController extends Controller
{
    public function index(Request $request)
    {
        try {
            // "d" = District (Kecamatan - Target), "c" = City (Kota - Context)
            $query = DB::table('indonesia_regions as d')
                ->leftJoin('shipping_rates', 'd.code', '=', 'shipping_rates.region_code')
                ->leftJoin('indonesia_regions as c', DB::raw('LEFT(d.code, 5)'), '=', 'c.code')
                ->select(
                    'd.code',
                    'd.name',
                    'c.name as city_name', // Shows "KOTA MAKASSAR"
                    'shipping_rates.price',
                    'shipping_rates.id as rate_id'
                );

            // Filter 1: Strict Length for Kecamatan (Format xx.xx.xx = 8 chars)
            $query->whereRaw('LENGTH(d.code) = 8');

            // Filter 2: Search or Default
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('d.name', 'like', '%' . $search . '%')
                        ->orWhere('c.name', 'like', '%' . $search . '%')
                        ->orWhere('d.code', 'like', $search . '%');
                });
            } else {
                // Default: Show all districts in Kota Makassar (73.71)
                $query->where('d.code', 'like', '73.71%');
            }

            $query->orderBy('d.name'); // Alphabetical order (Biringkanaya first usually)

            return response()->json($query->paginate(20));

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Validation: Code must exist in regions table
        $validated = $request->validate([
            'region_code' => 'required|exists:indonesia_regions,code',
            'price' => 'required|numeric|min:0'
        ]);

        $rate = ShippingRate::updateOrCreate(
            ['region_code' => $validated['region_code']],
            ['price' => $validated['price']]
        );

        return response()->json(['message' => 'Ongkir kecamatan berhasil disimpan', 'data' => $rate]);
    }

    public function destroy($region_code)
    {
        ShippingRate::where('region_code', $region_code)->delete();
        return response()->json(['message' => 'Ongkir di-reset']);
    }
}
