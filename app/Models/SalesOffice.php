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
    public function Tour()
    {
        return $this->hasOne(Tour::class, 'TourID', 'TourRef');
    }
    public function Address()
    {
        return $this->hasOne(Address::class, 'AddressID', 'AddressRef');
    }
}
