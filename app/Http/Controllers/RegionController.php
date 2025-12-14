<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\District;

class RegionController extends Controller
{
    public function index()
    {
        // 73.71 is the code for Kota Makassar
        // We look for codes like '73.71.%' that are exactly 8 characters long

        $districts = District::where('code', 'LIKE', '73.71.%')
            ->whereRaw('LENGTH(code) = 8')
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
            'data' => $districts
        ]);
    }
}
