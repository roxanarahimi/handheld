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
            "OrderID" => $this->AssignmentDeliveryItem->Order->OrderID,
            "OrderNumber" => $this->Number,
            "OrderNumber1" => $this->AssignmentDeliveryItem->Order->Number,
            "BroadcastDelivery"=>1,
            "AddressID" => $this->AssignmentDeliveryItem->Customer->CustomerAddress->Address->AddressID,
            "AddressName" => $this->AssignmentDeliveryItem->Customer->CustomerAddress->Address->Name,
            "Address" => $this->AssignmentDeliveryItem->Customer->CustomerAddress->Address->Details,
            "Phone" => $this->AssignmentDeliveryItem->Customer->CustomerAddress->Address->Phone,
                        "City" => $this->AssignmentDeliveryItem->Order->City,

            "Type" => "InventoryVoucher",
            'Sum' => $this->AssignmentDeliveryItem->Order->OrderItems->sum('Quantity'),

            "CreationDate" => $this->AssignmentDeliveryItem->Order->CreationDate,
            "DeliveryDate" => $this->AssignmentDeliveryItem->Order->CreationDate,
            "OrderItems" => OrderItemResource::collection($this->AssignmentDeliveryItem->Order->OrderItems),


        ];
    }
}
