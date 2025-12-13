<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierDeliveryController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::where('courier_id', $user->id)
            ->with(['items.product', 'customer'])
            ->latest();

        if ($request->status === 'active') {
            $query->where('status', 'on_delivery');
        } elseif ($request->status === 'history') {
            $query->where('status', 'completed');
        }

        return response()->json($query->paginate(10));
    }

    public function completeOrder(Order $order)
    {
        if ($order->courier_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'on_delivery') {
            return response()->json(['message' => 'Pesanan ini tidak sedang dalam pengantaran.'], 422);
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'completed']);
            User::where('id', auth()->id())->update(['courier_status' => 'available']);
        });

        return response()->json(['message' => 'Pengantaran selesai. Status Anda kini Tersedia.']);
    }
}
