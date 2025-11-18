<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOffice extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SLS3.SalesOffice';
    protected $hidden = ['Version'];
    use HasFactory;
}
