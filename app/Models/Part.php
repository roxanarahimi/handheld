<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'LGS3.Part';
    protected $hidden = ['Version'];

    public function Item()
    {
        return $this->belongsTo(InventoryVoucherItem::class, 'PartID', 'PartRef');
    }
    public function IssuePermitItem()
    {
        return $this->belongsTo(IssuePermitItem::class, 'PartID', 'PartRef');
    }
}
