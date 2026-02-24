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
    public function AssignmentDeliveryItem()
    {
        return $this->hasMany(AssignmentDeliveryItem::class,  'AssignmentRef','AssignmentID',);
    }
    public function Broker()
    {
        return $this->belongsTo(Broker::class, 'BrokerID', 'BrokerRef');
    }
    public function SalesOffice()
    {
        return $this->hasMany(SalesOffice::class, 'SalesOfficeID', 'SalesOfficeRef');
    }
    public function Plant()
    {
        return $this->hasOne(Plant::class, 'PlantID', 'PlantRef');
    }
}
