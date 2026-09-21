<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGroupMember extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'SLS3.ProductGroupMember';
    protected $hidden = ['Version'];

    public function Product()
    {
        return $this->hasOne(Product::class, 'ProductID', 'MemberID');
    }
    public function EntityGroup()
    {
        return $this->hasOne(EntityGroup::class, 'EntityGroupID','GroupRef');
    }
}
