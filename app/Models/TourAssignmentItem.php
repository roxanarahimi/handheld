<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourAssignmentItem extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'DSD3.TourAssignmentItem';
    protected $hidden = ['Version'];

    public function Tour()
    {
        return $this->hasOne(Tour::class, 'TourID', 'TourRef')->with('Invoices');
    }
    public function Assignment()
    {
        return $this->belongsTo(Assignment::class, 'AssignmentID', 'AssignmentRef');
    }

}
