<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuePermit extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'LGS3.IssuePermit';
    protected $hidden = ['Version'];
    use HasFactory;

    public function OrderItems()
    {
        return $this->hasMany(IssuePermitItem::class, 'IssuePermitRef', 'IssuePermitID')
//            ->with('Product')//
            ->whereHas('Part', function ($q) {
                $q->where('Name', 'like', '%نودالیت%');
                $q->whereNot('Name', 'like', '%لیوانی%');
                $q->whereNot('Name', 'like', '%کیلویی%');
            })->orderBy('PartRef');
    }
}
