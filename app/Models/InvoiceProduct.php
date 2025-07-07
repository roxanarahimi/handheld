<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceProduct extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class,  'ProductNumber','ProductNumber');
    }
}
