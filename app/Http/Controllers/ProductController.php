<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // <--- 1. IMPORTANT IMPORT

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Load images relationship so Vue can see them
        $query = Product::with(['category', 'images']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $query->where('is_active', true)->latest()->get()
            ]);
        }

        return response()->json($query->latest()->paginate(10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'is_active' => 'boolean',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // 2. CREATE PRODUCT (Auto-generate Slug here)
        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4), // Handle unique constraint safely
            'category_id' => $validated['category_id'],
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // 3. UPLOAD IMAGES
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => false // Default
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Produk berhasil dibuat', 'data' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        // Update Slug if name changes
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);

        $product->update($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Produk diperbarui']);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Produk dihapus']);
    }
}
