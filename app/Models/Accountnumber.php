<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accountnumber extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function accountcategory()
    {
        return $this->belongsTo(Accountcategory::class, 'account_category_id');
    }
}
