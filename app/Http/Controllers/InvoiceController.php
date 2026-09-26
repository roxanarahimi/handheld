<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Token;
use App\Http\Resources\InventoryVoucherResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceResource2;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderResource2;
use App\Http\Resources\RemittanceResource;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\InventoryVoucher;
use App\Models\Invoice;
use App\Models\InvoiceAddress;
use App\Models\InvoiceItem;
use App\Models\InvoiceProduct;
use App\Models\Order;
use App\Models\Part;
use App\Models\PartUnit;
use App\Models\Product;
use App\Models\Remittance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;


class InvoiceController extends Controller
{
    public function __construct(Request $request)
    {
        $this->middleware(Token::class)->except('info', 'updateInvoiceItems', 'showInventoryVoucher','showPakhsh', 'makePakhsh','makeInvoice','deleteInvoice');
    }

    public function index(Request $request)
    {
        try {
            $data = Invoice::orderByDesc('id')->get();
            return response(InvoiceResource::collection($data), 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function info(Request $request)
    {
        try {
            $d3 = Invoice::orderByDesc('id')
                ->where('DeliveryDate', '>=', today()->subDays(6))
//                ->whereNot('Type', 'Order')
                ->orderByDesc('Type')
//                ->orderByDesc('OrderNumber')
                ->take(200)->paginate(100);
            $data = InvoiceResource::collection($d3);
//            return response()->json($d3, 200);
            return response($d3, 200);

        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function showProduct($id)
    {
        try {
            $dat = Part::select('PartID as ProductID', 'Name', 'PropertiesComment as Description', 'Code as Number')->where('Code', $id)->first();
            if (!$dat) {
                $dat = Product::select('ProductID', 'Name', 'Description', 'Number')->where('Number', $id)->first();
            }
            return response()->json($dat, 200);

        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function showProductTest($id)
    {
        try {
            $dat = InvoiceProduct::select('id', 'ProductName as Name', 'ProductNumber', 'Description')->where('ProductNumber', $id)->first();
            return response()->json($dat, 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }
    public function updateInvoiceItems(Request $request)
    {

        try {
//            $item = InventoryVoucher::where('InventoryVoucherID', $request['OrderID'])->where('Number', $request['OrderNumber'])->first();
//            $invoice = Invoice::orderByDesc('id')->where('OrderID', $item['InventoryVoucherID'])->where('OrderNumber', $request['OrderNumber'])->first();
            $invoice = Invoice::find($request['id']);
            $item = InventoryVoucher::where('InventoryVoucherID', $invoice['OrderID'])->first();

            if ($invoice->BroadcastDelivery ===0){
                $invoice->invoiceItems->each->delete();
                if ($invoice->Type == 'InventoryVoucher') {
                    foreach ($item->OrderItems as $item2) {
                        $exist = InvoiceItem::where('invoice_id', $invoice->id)->where('ProductNumber', $item2->Part->Code)->first();
                        if ($exist) {
                            $exist->update(['Quantity' => $exist->Quantity + $item2->Quantity]);
                        } else {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Quantity' => $item2->Quantity,
                                ]);
                            }
                        }
                    }
                }
                if ($invoice->Type == 'Deputation') {
                    foreach ($item->OrderItems as $item2) {
                        $q = $item2->Quantity;
                        $int = (int)$item2->Quantity;
                        if (str_contains($item2->PartUnit->Name, 'پک')) {
                            $t = (int)PartUnit::where('PartID', $item2->PartRef)->where('Name', 'like', '%کارتن%')->pluck('DSRatio')[0];
                            $q = (string)floor($int / $t);
                        }
                        $exist = InvoiceItem::where('invoice_id', $invoice->id)->where('ProductNumber', $item2->Part->Code)->first();
                        if ($exist) {
                            $exist->update(['Quantity' => $exist->Quantity + $q]);
                        } else {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Quantity' => $q,
                                ]);
                            }
                        }
                    }
                }
            }



            $i = Invoice::where('id', $invoice->id)->first();
            $i->update(['Sum' => $i->invoiceItems->sum('Quantity')]);
            return response(new InvoiceResource($i), 200);

        } catch (\Exception $exception) {
            return response($exception);
        }
    }
    public function showInventoryVoucher(Request $request)
    {
        $x = InventoryVoucher::orderByDesc('InventoryVoucherID')
            ->where('Number', $request['OrderNumber'])
//            ->where('InventoryVoucherID', $request['OrderID'])
            ->with('OrderItems', function ($q) {
                return $q->with('Part');
            })
            ->get();
        if (!$x){
            return response('Not Found', 404);
        }
        return response(InventoryVoucherResource::collection($x), 200);

    }
    public function makeInventoryVoucher(Request $request)
    {
        try {
            $item = InventoryVoucher::orderByDesc('InventoryVoucherID')->where('Number', $request['Number'])->first();
            if (!$item){
                return response('Not Found', 404);
            }
            if ($item->InventoryVoucherSpecificationRef === 68) {
                $exx = Invoice::where('OrderID', $item->InventoryVoucherID)->where('OrderNumber', $item->Number)->where('Type', 'InventoryVoucher')->first();
                if (!$exx) {
                    $invoice = Invoice::create([
                        'Type' => 'InventoryVoucher',
                        'OrderID' => $item->InventoryVoucherID,
                        'OrderNumber' => $item->Number,
                        'AddressID' => $item->Store->Plant->Address->AddressID,
                        'Sum' => $item->OrderItems->sum('Quantity'),
                        'DeliveryDate' => $item->Date
                    ]);
                    $address = InvoiceAddress::where('AddressID', $item->Store->Plant->Address->AddressID)->first();
                    if (!$address) {
                        InvoiceAddress::create([
                            'AddressID' => $item->Store->Plant->Address->AddressID,
                            'AddressName' => $item->Store->Name,
                            'Address' => $item->Store->Plant->Address->Details,
                            'Phone' => $item->Store->Plant->Address->Phone,
                            'city' => $item->Store->Plant->Address->Region->Name,
                        ]);
                    }
                    foreach ($item->OrderItems as $item2) {
                        $exist = InvoiceItem::where('invoice_id', $invoice->id)->where('ProductNumber', $item2->Part->Code)->first();
                        if ($exist) {
                            $exist->update(['Quantity' => $exist->Quantity + $item2->Quantity]);
                        } else {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                $invoiceItem = InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Quantity' => $item2->Quantity,
                                ]);
                            }

                        }

                        $product = InvoiceProduct::where('ProductNumber', $item2->Part->Code)->first();
                        if (!$product) {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                InvoiceProduct::create([
                                    'ProductName' => $item2->Part->Name,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Description' => $item2->Part->Description,
                                ]);
                            }

                        }
                    }
                }
            }
            if ($item->InventoryVoucherSpecificationRef === 69) {
                $exx2 = Invoice::where('OrderID', $item->InventoryVoucherID)->where('OrderNumber', $item->Number)->where('Type', 'Deputation')->first();
                if (!$exx2) {
                    $invoice = Invoice::create([
                        'Type' => 'Deputation',
                        'OrderID' => $item->InventoryVoucherID,
                        'OrderNumber' => $item->Number,
                        'AddressID' => $item->Party->PartyAddress->Address->AddressID,
                        'Sum' => $item->OrderItems->sum('Quantity'),
                        'DeliveryDate' => $item->Date
                    ]);
                    $address = InvoiceAddress::where('AddressID', $item->Party->PartyAddress->Address->AddressID)->first();
                    if (!$address) {
                        InvoiceAddress::create([
                            'AddressID' => $item->Party->PartyAddress->Address->AddressID,
                            'AddressName' => $item->Party->PartyAddress->Address->Name,
                            'Address' => $item->Party->PartyAddress->Address->Details,
                            'Phone' => $item->Party->PartyAddress->Address->Phone,
                            'city' => $item->Party->PartyAddress->Address->Region->Name
                        ]);
                    }
                    foreach ($item->OrderItems as $item2) {
                        $q = $item2->Quantity;
                        $int = (int)$item2->Quantity;
                        if (str_contains($item2->PartUnit->Name, 'پک')) {
                            $t = (int)PartUnit::where('PartID', $item2->PartRef)->where('Name', 'like', '%کارتن%')->pluck('DSRatio')[0];
                            $q = (string)floor($int / $t);
                        }
                        $exist = InvoiceItem::where('invoice_id', $invoice->id)->where('ProductNumber', $item2->Part->Code)->first();
                        if ($exist) {
                            $exist->update(['Quantity' => $exist->Quantity + $q]);
                        } else {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                $invoiceItem = InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Quantity' => $q,
                                ]);
                            }

                        }

                        $product = InvoiceProduct::where('ProductNumber', $item2->Part->Code)->first();
                        if (!$product) {
                            if (!str_contains($item2->Part->Name, 'لیوانی') && !str_contains($item2->Part->Name, 'کیلویی')) {
                                InvoiceProduct::create([
                                    'ProductName' => $item2->Part->Name,
                                    'ProductNumber' => $item2->Part->Code,
                                    'Description' => $item2->Part->Description,
                                ]);
                            }

                        }
                    }
                }
            }

            $i = Invoice::orderByDesc('id')
                ->where('OrderID', $item['InventoryVoucherID'])
                ->where('OrderNumber', $item['Number'])
                ->where('BroadcastDelivery', 0)->first();
            return response(new InvoiceResource($i), 201);
        } catch (\Exception $exception) {
            return response($exception);
        }

    }
    public function makePaksh(Request $request)
    {
        $item = Assignment::query()
            ->where('Number', $request['Number'])
            ->orderByDesc('AssignmentID')
            ->first();
        if (!$item){
            return response('Not Found', 404);
        }
        $invoice = Invoice::where('OrderID', $item->AssignmentDeliveryItem[0]->Order->OrderID)->where('OrderNumber', $item->Number)->where('Type', 'InventoryVoucher')->where('BroadcastDelivery', 1)->first();
//                if ($exx3) {
//                    return response(['invoice exists!',new InvoiceResource($exx3)], 200);
//                }
        if (!$invoice) {
            $invoice = Invoice::create([
                'Type' => 'InventoryVoucher',
                'BroadcastDelivery' => 1,
                'OrderID' => $item->AssignmentDeliveryItem[0]->Order->OrderID,
                'OrderNumber' => $item->Number,//
                "AddressID" => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->AddressID,
                'Sum' => $item->AssignmentDeliveryItem[0]->Order->OrderItems->sum('Quantity'),
                'DeliveryDate' => $item->AssignmentDeliveryItem[0]->Order->DeliveryDate
            ]);
            $address = InvoiceAddress::where('AddressID', $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->AddressID)->first();
            if (!$address) {
                InvoiceAddress::create([
                    'AddressID' => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->AddressID,
                    'AddressName' => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Name,
                    'Address' => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Details,
                    'Phone' => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Phone,
                    'city' => $item->AssignmentDeliveryItem[0]->Customer->CustomerAddress->Address->Region->Name
                ]);
            }
            foreach ($item->AssignmentDeliveryItem[0]->Order->OrderItems as $item2) {
                $exist = InvoiceItem::where('invoice_id', $invoice->id)->where('ProductNumber', $item2->Product->Number)->first();
                if ($exist) {
                    $exist->update(['Quantity' => $exist->Quantity + $item2->Quantity]);
                } else {
                    if (!str_contains($item2->Product->Name, 'لیوانی') && !str_contains($item2->Product->Name, 'کیلویی')) {
                        $invoiceItem = InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'ProductNumber' => $item2->Product->Number,
                            'Quantity' => $item2->Quantity,
                        ]);
                    }

                }
                $product = InvoiceProduct::where('ProductNumber', $item2->Product->Number)->first();
                if (!$product) {
                    if (!str_contains($item2->Product->Name, 'لیوانی') && !str_contains($item2->Product->Name, 'کیلویی')) {
                        InvoiceProduct::create([
                            'ProductName' => $item2->Product->Name,
                            'ProductNumber' => $item2->Product->Number,
                            'Description' => $item2->Product->Description
                        ]);
                    }

                }
            }

        }
        return response(new InvoiceResource($invoice), 200);
    }
    public function showPakhsh(Request $request)
    {
        try{
            $item = Assignment::query()
                ->where('Number', $request['Number'])
                ->orderByDesc('AssignmentID')
                ->first();
            if ($item){
//                return response($item, 200);
                return response(new OrderResource2($item), 200);
            }else{
                return response('Not Found', 404);
            }
        }catch(\Exception $exception){
            return response($exception);
        }

    }

    public function makeInvoice(Request $request)
    {
        try{
            $customer = Customer::where('Number',$request['Customer'])->first();
            $addressID = $customer->CustomerAddress->AddressRef;
            $address = InvoiceAddress::where('AddressID',$addressID)->first();
            if(!$address){
                InvoiceAddress::create([
                    'AddressID' => $customer->CustomerAddress->Address->AddressID,
                    'AddressName' => $customer->CustomerAddress->Address->Name,
                    'Address' => $customer->CustomerAddress->Address->Details,
                    'Phone' => $customer->CustomerAddress->Address->Phone,
                    'city' => $customer->CustomerAddress->Address->Region->Name,
                ]);
            }
            $invoice = Invoice::create([
                "AddressID"=> $addressID,
                "OrderNumber"=> $request['OrderNumber'],
                "OrderID"=> $request['OrderNumber'].time(),
                "Type"=> $request['Type'],
                "Sum"=> $request['Sum'],
                'DeliveryDate' => new \DateTime(now())

            ]);
            if ($request['Type'] === 'Broadcast'){
                $invoice->update([
                    "Type"=> 'InventoryVoucher',
                    "BroadcastDelivery"=> 1,
                ]);
            }
            $x= explode('-',$request['items']);
            $t=[];
            foreach ($x as $itemm){
                $t[] = explode(',',$itemm);
            }
            foreach ($t as $item){
                InvoiceItem::create([
                    "invoice_id"=>$invoice->id,
                    "ProductNumber"=>$item[0],
                    "Quantity"=> $item[1]
                ]);
            }
            $invoice = Invoice::findOrFail($invoice->id);
            return response(new InvoiceResource($invoice), 201);
        }catch(\Exception $exception){
            return response($exception);
        }
    }

    public function deleteInvoice(Request $request)
    {
        $invoice = Invoice::findOrFail($request['id']);
        $invoice->invoiceItems->each->delete();
        $invoice->delete();
        return response('invoice deleted.', 200);

    }
}
