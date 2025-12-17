<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Upload or Replace the QRIS Image
     */
    public function updateQris(Request $request)
    {
        $request->validate([
            'qris_image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('qris_image')) {
            // We force the filename to 'qris_code.jpg' so it always overwrites the old one
            $request->file('qris_image')->storeAs('settings', 'qris_code.jpg', 'public');

            return response()->json([
                'message' => 'QRIS berhasil diperbarui',
                // Add timestamp to prevent browser caching of the old image
                'url' => url('storage/settings/qris_code.jpg') . '?t=' . time()
            ]);
        }

        return response()->json(['message' => 'Gagal upload file'], 400);
    }

    /**
     * Get the current QRIS Image URL
     */
    public function getQris()
    {
        // Check if file exists
        if (Storage::disk('public')->exists('settings/qris_code.jpg')) {
            return response()->json([
                'url' => url('storage/settings/qris_code.jpg') . '?t=' . time()
            ]);
        }

        return response()->json(['url' => null]);
    }
}
