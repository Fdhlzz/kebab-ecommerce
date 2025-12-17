<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // ✅ Needed for file upload
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ✅ FIXED: Added '.images' to load product pictures in Order History
        $query = Order::with(['items.product.images', 'courier', 'customer'])->latest();

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

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:user_addresses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            'total_price' => 'required|numeric',
            // ✅ NEW: Validate Payment Method
            'payment_method' => 'required|in:COD,QRIS',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $address = UserAddress::findOrFail($request->address_id);
            $fixedShippingCost = $address->shipping_cost ?? 0;

            // ✅ NEW: Calculate Grand Total
            $grandTotal = $request->total_price + $fixedShippingCost;

            $order = Order::create([
                'user_id' => $user->id,
                'courier_id' => null,
                'customer_name' => $address->recipient_name,
                'shipping_address' => $address->full_address . ' (' . $address->label . ') - ' . $address->phone_number,
                'region_code' => $address->district_id,

                'total_price' => $request->total_price, // Subtotal
                'shipping_cost' => $fixedShippingCost,
                'grand_total' => $grandTotal, // ✅ Save Grand Total

                'payment_method' => $request->payment_method, // ✅ Save Method (COD/QRIS)
                'status' => 'pending',
                'payment_status' => 'unpaid', // Default to unpaid
            ]);

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

    // ✅ NEW FUNCTION: Handle QRIS Screenshot Upload
    public function uploadPaymentProof(Request $request, $id)
    {
        // 1. Validate Input
        $request->validate([
            'payment_proof' => 'required|image|max:5120',
        ]);

        // 2. ✅ DEBUGGING: Check if user exists
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: User not found'], 401);
        }

        // 3. Find Order safely
        // We use $user->id instead of $request->user()->id
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found or does not belong to you'], 404);
        }

        // 4. Upload Logic
        if ($request->hasFile('payment_proof')) {
            // Delete old image if exists
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $path = $request->file('payment_proof')->store('payment_proofs', 'public');

            $order->update([
                'payment_proof' => $path,
            ]);

            return response()->json([
                'message' => 'Bukti pembayaran berhasil diupload',
                'data' => $order
            ]);
        }

        return response()->json(['message' => 'Gagal upload gambar'], 400);
    }

    // Add this at the top of the file


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

            if ($newStatus === 'completed') {
                if ($order->courier_id) {
                    User::where('id', $order->courier_id)->update(['courier_status' => 'available']);
                }

                $isCOD = ($order->payment_method == 'COD');

                if ($isCOD) {
                    $order->payment_status = 'paid';
                } else {
                }
            }

            if ($newStatus === 'processing' && $order->payment_method == 'QRIS') {
                $order->payment_status = 'paid';
            }

            $order->status = $newStatus;
            $order->save();

            return response()->json([
                'message' => 'Status pesanan diperbarui',
                'data' => $order
            ]);
        });
    }
}
