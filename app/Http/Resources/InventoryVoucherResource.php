<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryVoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = match ($this->InventoryVoucherSpecificationRef) {
            '68' => 'InventoryVoucher',
            '69' => 'Deputation',
            default => null,
        };

        return [
            "OrderID" => $this->InventoryVoucherID,
            "OrderNumber" => $this->Number,
            "InventoryVoucherSpecificationRef" => $this->InventoryVoucherSpecificationRef,
            "CounterpartEntityRef" => $this->CounterpartEntityRef,
            "AddressID" => $this->AddressID,
            "AddressName" => $this->AddressName,
            "Address" => $this->Details,
            "Phone" => $this->Phone,
            "City" => $this->City,

            "Type" => $type,
            'Sum' => $this->OrderItems->sum('Quantity'),


            "CreationDate" => $this->CreationDate,
            "DeliveryDate" => $this->CreationDate,
            "OrderItems" => InventoryVoucherItemResource::collection($this->OrderItems),
//            "ok" => 1,//

        ];
    }
}
