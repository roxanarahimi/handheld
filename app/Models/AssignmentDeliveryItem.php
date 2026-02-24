<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentDeliveryItem extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'DSD3.AssignmentDeliveryItem';
    protected $hidden = ['Version'];
    use HasFactory;
    public function Assignment()
    {
        return $this->hasOne(Assignment::class,  'AssignmentRef','AssignmentID');
    }

    public function Order()
    {
        return $this->hasOne(Order::class, 'OrderID', 'OrderRef');
    }
    public function Invoice()
    {
        return $this->hasOne(InvoiceERP::class, 'InvoiceID', 'InvoiceRef');
    }
    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerRef', 'CustomerID');
    }

}
