<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuePermitItem extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'LGS3.IssuePermititem';
    protected $hidden = ['Version'];
    use HasFactory;
    public function Part()
    {
        return $this->hasOne(Part::class, 'PartID', 'PartRef');
    }

    public function InventoryVoucherItem()
    {
        return $this->belongsTo(InventoryVoucherItem::class, 'ReferenceRef', 'IssuePermitItemID');
    }
    public function OrderItem()
    {
        return $this->belongsTo(OrderItem::class, 'ReferenceRef', 'IssuePermitItemID');
    }
}
