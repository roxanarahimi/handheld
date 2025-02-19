<?php

namespace App\Http\Resources;

use App\Http\Controllers\DateController;
use App\Models\Remittance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource2 extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $count = $this->invoiceItems->sum('Quantity');
        $scanned = count($this->barcodes) + count($this->barcodes2);
        return [
            "id" => $this->id,
            "OrderID" => $this->OrderID,
            "OrderNumber" => $this->OrderNumber,
            "AddressName" => $this->address?->AddressName,
            'count' => $count,
            'Sum' => $this->Sum,
            'Scanned' => $scanned,
            'Difference' =>  $scanned - $count,
            'created_at' => explode(' ', (new DateController)->toPersian($this->created_at))[0] . ' ' . explode(' ', (new DateController)->toPersian($this->created_at))[1],

            'Barcodes' => 'http://5.34.204.23/api/report?api_key=Rsxw_q25jhk92345/624087Mnhi.oxcv&OrderNumber='.$this->OrderNumber,
//            "DeliveryDate" => $this->DeliveryDate,


        ];
    }
}
