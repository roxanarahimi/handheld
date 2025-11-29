<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceERP extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'SLS3.Invoice';
    protected $hidden = ['Version'];

}
