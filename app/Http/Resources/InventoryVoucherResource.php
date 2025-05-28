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
            "AddressID" => $this->Store?->Plant->Address->AddressID. $this->Party?->PartyAddress->Address->AddressID,
            "AddressName" => $this->Store?->Name . $this->CounterpartEntityText . ' ' .$this->Number,
            "Address" => $this->Store?->Plant->Address->Details. $this->Party?->PartyAddress->Address->Details,
            "Phone" => $this->Store?->Plant->Address->Phone. $this->Party?->PartyAddress->Address->Phone,

            "Type" => $type,
            'Sum' => $this->OrderItems->sum('Quantity'),


            "CreationDate" => $this->CreationDate,
            "DeliveryDate" => $this->CreationDate,
            "OrderItems" => InventoryVoucherItemResource::collection($this->OrderItems),
//            "ok" => 1,//

        ];
    }
}
