<?php

namespace App\Http\Resources;

use App\Http\Controllers\DateController;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $barcodes_count = count($this->barcodes);
        $state = 0; // not done
        if ($barcodes_count < $this->Sum) {
            $state = 0; // not done
        } elseif ($barcodes_count == $this->Sum) {
            $state = 1; // done
        } elseif ($barcodes_count > $this->Sum) {
            $state = 2; // over done
        }
        if ($this->id == 3978) {
            $state = 0; // over done
        }
        return [
            "id" => $this->id,
            "OrderID" => $this->OrderID,
            "OrderNumber" => $this->OrderNumber,
            "AddressName" => $this->address?->AddressName,
            "Address" => $this->address?->Address,
            "City" => $this->address?->city,
            "Phone" => $this->address?->Phone,
            "Type" => $this->Type,
            'Sum' => $this->Sum,
            'count' => $this->invoiceItems?->sum('Quantity'),
            'Barcodes Count' => $barcodes_count,
            'Progress' => $barcodes_count . '/' . $this->Sum,
            'State' => $state,

//            "DeliveryDate" => $this->DeliveryDate,
            'DeliveryDate' => (new DateController)->toPersian2($this->DeliveryDate),

            "OrderItems" => InvoiceItemResource::collection($this->invoiceItems),
            'created_at' => explode(' ', (new DateController)->toPersian($this->created_at))[0] . ' ' . explode(' ', (new DateController)->toPersian($this->created_at))[1],
            'updated_at' => explode(' ', (new DateController)->toPersian($this->updated_at))[0] . ' ' . explode(' ', (new DateController)->toPersian($this->updated_at))[1],


        ];
    }
}
