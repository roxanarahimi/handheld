<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'DSD3.Assignment';
    protected $hidden = ['Version'];
    use HasFactory;
}
