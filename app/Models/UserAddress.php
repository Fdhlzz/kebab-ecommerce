<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

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

    // Helper to get district name (Optional, depends on your setup)
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'code');
    }
}
