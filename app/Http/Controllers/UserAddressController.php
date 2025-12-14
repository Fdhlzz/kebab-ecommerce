<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAddress;

class UserAddressController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->addresses()->orderBy('is_primary', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string',
            'recipient_name' => 'required|string',
            'phone_number' => 'required|string',
            'district_id' => 'required|exists:indonesia_regions,code',
            'full_address' => 'required|string',
        ]);

        $isFirst = $request->user()->addresses()->count() === 0;

        $address = $request->user()->addresses()->create([
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'phone_number' => $request->phone_number,
            'district_id' => $request->district_id,
            'full_address' => $request->full_address,
            'is_primary' => $isFirst ? true : false,
        ]);

        return response()->json(['message' => 'Alamat berhasil disimpan', 'data' => $address]);
    }

    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $address->update($request->only([
            'label',
            'recipient_name',
            'phone_number',
            'district_id',
            'full_address'
        ]));

        return response()->json(['message' => 'Alamat diperbarui', 'data' => $address]);
    }


    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();
        return response()->json(['message' => 'Alamat dihapus']);
    }

    public function setPrimary(Request $request, $id)
    {
        $request->user()->addresses()->update(['is_primary' => false]);
        $address = $request->user()->addresses()->findOrFail($id);
        $address->update(['is_primary' => true]);

        return response()->json(['message' => 'Alamat utama diubah']);
    }
}
