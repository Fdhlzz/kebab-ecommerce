<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'courier', 'customer'])->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:processing,on_delivery,completed,cancelled',
            'courier_id' => 'nullable|exists:users,id'
        ]);

        return DB::transaction(function () use ($request, $order) {
            $newStatus = $request->status;

            if ($newStatus === 'on_delivery') {
                if (!$request->courier_id) {
                    return response()->json(['message' => 'Pilih kurir terlebih dahulu!'], 422);
                }

                $courier = User::find($request->courier_id);

                if ($courier->courier_status !== 'available') {
                    return response()->json(['message' => 'Kurir ini sedang sibuk!'], 422);
                }
                $order->courier_id = $courier->id;
                $courier->update(['courier_status' => 'busy']);
            }

            if ($newStatus === 'completed' && $order->courier_id) {
                User::where('id', $order->courier_id)->update(['courier_status' => 'available']);
            }

            $order->update(['status' => $newStatus]);

            return response()->json(['message' => 'Status pesanan diperbarui', 'data' => $order]);
        });
    }
}
