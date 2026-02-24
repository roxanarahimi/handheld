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
            "OrderID" => $this->AssignmentDeliveryItem[0]->Order->OrderID,
            "OrderNumber" => $this->Number,
            "OrderNumber1" => $this->AssignmentDeliveryItem[0]->Order->Number,
            "BroadcastDelivery"=>1,
            "AddressID" => $this->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->AddressID,
            "AddressName" => $this->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Name,
            "Address" => $this->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Details,
            "Phone" => $this->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Phone,
                        "City" => $this->AssignmentDeliveryItem[0]->Order->City,

            "Type" => "InventoryVoucher",
            'Sum' => $this->AssignmentDeliveryItem[0]->Order->OrderItems->sum('Quantity'),

            "CreationDate" => $this->AssignmentDeliveryItem[0]->Order->CreationDate,
            "DeliveryDate" => $this->AssignmentDeliveryItem[0]->Order->CreationDate,
            "OrderItems" => OrderItemResource::collection($this->AssignmentDeliveryItem[0]->Order->OrderItems),


        ];
    }
}
