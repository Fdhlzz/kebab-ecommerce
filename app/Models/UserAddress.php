<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingRate; // Import your existing model

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone_number',
        'district_id',
        'full_address',
        'is_primary'
    ];

    // ✅ This ensures 'shipping_cost' is always in the JSON response
    protected $appends = ['shipping_cost'];

    // ✅ Magic Function to calculate cost
    public function getShippingCostAttribute()
    {
        // 1. Search your ShippingRate table
        $rate = ShippingRate::where('region_code', $this->district_id)->first();

        // 2. Return the price if found, otherwise return default (e.g. 15000)
        // Casting to float ensures it sends a number, not a string "15000.00"
        return $rate ? (float) $rate->price : 15000;
    }
}
