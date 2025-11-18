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

}
