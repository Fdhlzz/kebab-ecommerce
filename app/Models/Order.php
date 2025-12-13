<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'courier_id',
        'customer_name',
        'shipping_address',
        'region_code',
        'total_price',
        'shipping_cost',
        'status',
        'payment_status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
