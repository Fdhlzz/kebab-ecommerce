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

        // Used 'with' to ensure product images and customer details load
        $query = Order::where('courier_id', $user->id)
            ->with(['items.product.images', 'customer'])
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
        // 1. Security Check
        if ($order->courier_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'on_delivery') {
            return response()->json(['message' => 'Pesanan ini tidak sedang dalam pengantaran.'], 422);
        }

        // 2. Transaction to update everything safely
        DB::transaction(function () use ($order) {

            // ✅ CRITICAL FIX: Mark Payment as PAID if it is COD
            // We use direct assignment and save() to ensure it works
            if ($order->payment_method == 'COD') {
                $order->payment_status = 'paid';
            }

            // Update Status to Completed
            $order->status = 'completed';

            // Save changes (updates status AND payment_status if changed)
            $order->save();

            // Free up the Courier
            User::where('id', auth()->id())->update(['courier_status' => 'available']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengantaran selesai. Status Anda kini Tersedia.',
            'data' => $order
        ]);
    }
}
