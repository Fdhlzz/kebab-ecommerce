<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    // 1. Point to your single table
    protected $table = 'indonesia_regions';

    // 2. The primary key is 'code', not 'id'
    protected $primaryKey = 'code';

    // 3. IMPORTANT: The key is a String (e.g., "73.71.01"), not an Integer
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['code', 'name'];
    public $timestamps = false;
}
