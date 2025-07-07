<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAddress extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'AddressID', 'AddressID');
    }
}
