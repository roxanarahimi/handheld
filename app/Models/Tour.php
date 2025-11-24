<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'DSD3.Tour';
    protected $hidden = ['Version'];
    use HasFactory;

    public function SalesOffice()
    {
        return $this->belongsTo(SalesOffice::class, 'SalesOfficeID','SalesOfficeRef');
    }
    public function Broker()
    {
        return $this->hasOne(Broker::class, 'BrokerID','BrokerRef' );
    }
}
