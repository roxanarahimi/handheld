<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuePermit extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'IssuePermit';
    protected $hidden = ['Version'];
    use HasFactory;
}
