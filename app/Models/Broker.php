<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broker extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SLS3.Broker';
    protected $hidden = ['Version'];
    use HasFactory;

    public function Assignments()
    {
        return $this->hasMany(Assignment::class, 'BrokerRef','BrokerID');
    }
}
