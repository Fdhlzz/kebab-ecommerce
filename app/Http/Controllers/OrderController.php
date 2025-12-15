<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with(['items.product', 'courier', 'customer'])->latest();

        if ($user->role === 'customer') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('type')) {
            if ($request->type === 'active') {
                $query->whereIn('status', ['pending', 'processing', 'on_delivery']);
            } elseif ($request->type === 'history') {
                $query->whereIn('status', ['completed', 'cancelled']);
            }
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->get()
        ]);
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
    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:user_addresses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            // 'shipping_cost' => 'required|numeric', // <-- Remove this validation, we calculate it ourselves
            'total_price' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();

            // 1. Fetch Address
            $address = UserAddress::findOrFail($request->address_id);

            // 2. ✅ FORCE BACKEND TO CALCULATE ONGKIR
            // This uses the "getShippingCostAttribute" accessor we added to the UserAddress model earlier.
            // It guarantees the price comes from your database, not the app's potentially buggy state.
            $fixedShippingCost = $address->shipping_cost ?? 0;

            // 3. Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'courier_id' => null,
                'customer_name' => $address->recipient_name,
                'shipping_address' => $address->full_address . ' (' . $address->label . ') - ' . $address->phone_number,
                'region_code' => $address->district_id,

                'total_price' => $request->total_price,     // Subtotal (e.g. 10,000)
                'shipping_cost' => $fixedShippingCost,      // ✅ Securely calculated (e.g. 10,000)

                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            // 4. Create Items
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order
            ], 201);
        });
    }
}
