<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "InvoiceID" => $this->InvoiceID,
            "OrderID" => $this->Order?->OrderID,
            "OrderNumber" => $this->Order?->Number,
            "Amount" => (integer)$this->Order?->Price,

//            "Party ID"=> $this->Tour->TourAssignmentItem?->Assignment?->Transporter?->Party->PartyID,
//            "Party Ref"=> $this->Tour->TourAssignmentItem?->Assignment?->Transporter?->PartyRef,

//            "Party Mobile"=> $this->Tour->TourAssignmentItem?->Assignment?->Transporter?->Party->Mobile,
//            "Party FullName"=> $this->Tour->TourAssignmentItem?->Assignment?->Transporter?->Party->FullName,
//"Transporter"=> $this->Tour->TourAssignmentItem?->Assignment?->Transporter->FirstName.' '. $this->Tour->TourAssignmentItem?->Assignment?->Transporter->LastName,

//            "OrderRef"=> $this->OrderRef,
//            "Order"=> $this->Order,
//            "Customer"=> [
            "CustomerID" => $this->Order?->Customer->CustomerID,
            "CustomerNumber" => $this->Order?->Customer->Number,
            "FullName" => $this->Order?->Customer->Party->FullName,
            "Mobile" => $this->Order?->Customer->Party->Mobile,
            "NationalID" => $this->Order?->Customer->Party->NationalID,
            "BirthDate" => $this->Order?->Customer->Party->BirthDate,
            "Tel" => $this->Order?->Customer->Party->Tel,
            "Phone" => $this->Order?->Customer->CustomerAddress->Address->Phone,
            "AddressName" => $this->Order?->Customer->CustomerAddress->Address->Name,
            "Region" => $this->Order?->Customer->CustomerAddress->Address->Region->Name,
            "Address" => $this->Order?->Customer->CustomerAddress->Address->Details,
            "Latitude" => $this->Order?->Customer->CustomerAddress->Address->Latitude,
            "Longitude" => $this->Order?->Customer->CustomerAddress->Address->Longitude,
//            ],

        ];
    }
}
