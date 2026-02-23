<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Token;
use App\Http\Resources\InventoryVoucherResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceResource2;
use App\Http\Resources\OrderResource;
use App\Http\Resources\RemittanceResource;
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
        $this->middleware(Token::class)->except('info', 'updateInvoiceItems', 'showInventoryVoucher','makePaksh');
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
            $d3 = Invoice::where('DeliveryDate', '>=', today()->subDays(15))
                ->whereNot('Type', 'Order')
                ->orderByDesc('Type')
                ->orderByDesc('OrderID')
                ->paginate(100);
            $data = InvoiceResource::collection($d3);
            return response()->json($d3, 200);

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
            $invoice =Invoice::find($request['id']);
            $item = InventoryVoucher::where('InventoryVoucherID', $invoice['OrderID'])->first();


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
        return response(InventoryVoucherResource::collection($x), 200);

    }

    public function makePaksh(Request $request)
    {
        $item = Order::query()
            ->where('Date', '>=', today()->subDays(10))
            ->where('FiscalYearRef', 1405)
            ->where('InventoryRef', 1)

            ->where('Type', 0)
            ->where('State', 2)

            ->orderByDesc('OrderID')
            ->whereHas('OrderItems')
            ->whereHas('AssignmentDeliveryItem')
            ->whereHas('AssignmentDeliveryItem.Assignment', function ($p) use ($request) {
                    $p->where('Number', $request['Number'])// 👈 این خط اضافه شد
                ;
            })
            ->with([
                'AssignmentDeliveryItem.Assignment.Plant.Address',
                'AssignmentDeliveryItem.Customer.CustomerAddress.Address',
                'OrderItems'
            ])->first();
        return new OrderResource::($item);
        return $item;
                        $exx3 = Invoice::where('OrderID',$item->OrderID)->where('OrderNumber',$item->Number)->where('Type','Order')->first();
                if(!$exx3){
                    $invoice = Invoice::create([
                        'Type' => 'Order',
                        'OrderID' => $item->OrderID,
                        'OrderNumber' => $item->Number,
                        'AddressID' => $item->Customer->CustomerAddress->Address->AddressID,
                        'Sum' => $item->OrderItems->sum('Quantity'),
                        'DeliveryDate' => $item->DeliveryDate
                    ]);
                    $address = InvoiceAddress::where('AddressID', $item->Customer->CustomerAddress->Address->AddressID)->first();
                    if (!$address) {
                        InvoiceAddress::create([
                            'AddressID' => $item->Customer->CustomerAddress->Address->AddressID,
                            'AddressName' => $item->Customer->CustomerAddress->Address->Name,
                            'Address' => $item->Customer->CustomerAddress->Address->Details,
                            'Phone' => $item->Customer->CustomerAddress->Address->Phone,
                            'city' => $item->Customer->CustomerAddress->Address->Region->Name
                        ]);
                    }
                    foreach ($item->OrderItems as $item2) {
                        $exist = InvoiceItem::where('invoice_id',$invoice->id)->where('ProductNumber',$item2->Product->Number)->first();
                        if ($exist){
                            $exist->update(['Quantity'=>$exist->Quantity + $item2->Quantity]);
                        }else{
                            if (!str_contains($item2->Product->Name,'لیوانی') && !str_contains($item2->Product->Name,'کیلویی')){
                                $invoiceItem = InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'ProductNumber' => $item2->Product->Number,
                                    'Quantity' => $item2->Quantity,
                                ]);
                            }

                        }
                        $product = InvoiceProduct::where('ProductNumber', $item2->Product->Number)->first();
                        if (!$product) {
                            if (!str_contains($item2->Product->Name,'لیوانی') && !str_contains($item2->Product->Name,'کیلویی')){
                                InvoiceProduct::create([
                                    'ProductName' => $item2->Product->Name,
                                    'ProductNumber' => $item2->Product->Number,
                                    'Description' => $item2->Product->Description
                                ]);
                            }

                        }
                    }
                }
    }
}
