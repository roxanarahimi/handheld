<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource2 extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "OrderID" => $this->OrderID,
            "OrderNumber" => $this->AssignmentDeliveryItem->Assignment->Number,
            "OrderNumber2" => $this->Number,
            "AddressID" => $this->Customer->CustomerAddress->Address->AddressID,
            "AddressName" => $this->Customer->CustomerAddress->Address->Name,
            "Address" => $this->Customer->CustomerAddress->Address->Details,
            "Phone" => $this->Customer->CustomerAddress->Address->Phone,
                        "City" => $this->City,

            "Type" => "Order",
            'Sum' => $this->OrderItems->sum('Quantity'),

            "CreationDate" => $this->CreationDate,
            "DeliveryDate" => $this->CreationDate,
            "OrderItems" => OrderItemResource::collection($this->OrderItems),
//            "ok" => 1,//


//            "InventoryVoucherSpecificationRef" => $this->InventoryVoucherSpecificationRef,
//            "CounterpartEntityRef" => $this->CounterpartEntityRef,
//            "AddressID" => $this->Store?->Plant->Address->AddressID. $this->AddressID,
//            "AddressName" => $this->Store?->Name . $this->CounterpartEntityText . ' ' .$this->AddressName,
//            "Address" => $this->Store?->Plant->Address->Details. $this->Details,

//
//            "Type" => $type,
//            'Sum' => $this->OrderItems->sum('Quantity'),

        ];
    }
}
