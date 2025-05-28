<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Token;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceResource2;
use App\Http\Resources\RemittanceResource;
use App\Models\InventoryVoucher;
use App\Models\Invoice;
use App\Models\InvoiceAddress;
use App\Models\InvoiceItem;
use App\Models\PartUnit;
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
        $this->middleware(Token::class)->except('info','repairInvoiceItems');
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
            $d3 = Invoice::where('DeliveryDate', '>=', today()->subDays(10))
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

    public function show(Invoice $invoice)
    {
        try {
            return response(new InvoiceResource($invoice), 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function store(Request $request)
    {
        $data = json_encode([
            'OrderID' => $request['OrderID'],
            'OrderItems' => $request['OrderItems'],
            'name' => $request['name'],
        ]);
        $id = $request['OrderID'];
        $info = Redis::get($request['OrderID']);
        if (isset($info)) {
            $id = $request['OrderID'] . '-' . substr(explode(',', $request['OrderItems'])[0], -4);
        }
        Redis::set($id, $data);
        $value = Redis::get($id);
        $json = json_decode($value);
        $orderId = $json->{'OrderID'};
        $items = explode(',', $json->{'OrderItems'});
        $name = $json->{'name'};
        $myfile = fopen('../storage/logs/failed_data_entries/' . $id . ".log", "w") or die("Unable to open file!");
        $txt = json_encode([
            'OrderID' => $orderId,
            'name' => $name,
            'OrderItems' => $items
        ]);
        fwrite($myfile, $txt);
        fclose($myfile);

        $str = str_replace(' ', '', str_replace('"', '', $request['OrderItems']));
        $orderItems = explode(',', $str);
        try {
            foreach ($orderItems as $item) {
                Invoice::create([
                    "orderID" => $request['OrderID'],
                    "addressName" => $request['name'],
                    "barcode" => $item,
                ]);
            }
            $invoices = Invoice::orderByDesc('id')->where('orderID', $request['OrderID'])->get();
            return response(InvoiceResource::collection($invoices), 201);
        } catch (\Exception $exception) {
            for ($i = 0; $i < 3; $i++) {
                try {
                    foreach ($orderItems as $item) {
                        Invoice::create([
                            "orderID" => $request['OrderID'],
                            "addressName" => $request['name'],
                            "barcode" => str_replace(' ', '', str_replace('"', '', $item)),
                        ]);
                    }
                    $invoices = Invoice::orderByDesc('id')->where('orderID', $request['OrderID'])->get();
                    if (count($invoices) == count($orderItems)) {
                        $i = 3;
                        return response(InvoiceResource::collection($invoices), 201);
                    }
                } catch (\Exception $exception) {
                    return response(['message' =>
                        'خطای پایگاه داده. لطفا کد '
                        . $id .
                        ' را یادداشت کرده و جهت ثبت بارکد ها به پشتیبانی اطلاع دهید'], 500);
                }
            }
        }


    }

    public function update(Request $request, Invoice $invoice)
    {
        $validator = Validator::make($request->all('title'),
            [
//              'title' => 'required|unique:Invoices,title,' . $invoice['id'],
//                'title' => 'required',
            ],
            [
//                'title.required' => 'لطفا عنوان را وارد کنید',
//                'title.unique' => 'این عنوان قبلا ثبت شده است',
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        try {
            $invoice->update($request->all());
            return response(new InvoiceResource($invoice), 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function destroy(Invoice $invoice)
    {

        try {
            $invoice->invoiceItems()->each->delete();
            $invoice->delete();
            return response('Invoice deleted', 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function repairInvoiceItems(Request $request)
    {
        $item = InventoryVoucher::where('InventoryVoucherID', $request['OrderID'])->where('Number', $request['OrderNumber'])
            ->with('OrderItems', function ($q) {
                return $q->with('Part');
            })
            ->with('Store', function ($q) {
                return $q->with('Plant',function ($z){
                    return $z->with('Address');
                });
            })->first();
        $invoice = Invoice::where('OrderID', $item['InventoryVoucherID'])->first();
        return ['invoice'=>new InvoiceResource($invoice), 'InventoryVoucher'=>$item,];
        $type = match ($item['InventoryVoucherSpecificationRef']) {
            '68' => 'InventoryVoucher',
            '69' => 'Deputation',
            default => null,
        };

        if ($type === null) {
            return response('InventoryVoucherSpecificationRef not supported.', 422);
        }

        $invoice = Invoice::where('OrderID', $item['InventoryVoucherID'])->first();


        if ($invoice) {
            $invoice->invoiceItems()->each->delete();
        } else {
            $invoice = Invoice::create([
                'Type' => $type,
                'OrderID' => $item->InventoryVoucherID,
                'OrderNumber' => $item->Number,
                'AddressID' => $item->Store->Plant->Address->AddressID,
                'Sum' => $item->OrderItems->sum('Quantity'),
                'DeliveryDate' => $item->DeliveryDate
            ]);
            $address = InvoiceAddress::where('AddressID', $item->Store->Plant->Address->AddressID)->first();
            if (!$address) {
                InvoiceAddress::create([
                    'AddressID' => $item->Store->Plant->Address->AddressID,
                    'AddressName' => $item->Store->Name,
                    'Address' => $item->Store->Plant->Address->Details,
                    'Phone' => $item->Store->Plant->Address->Phone,
                    'city' => $item->City,
                ]);
            }
        }

        if ($type == 'InventoryVoucher') {
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
            }
        }
        if ($type == 'Deputation') {
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
            }
        }
        $invoice->update(['Sum' => $invoice->invoiceItems->sum('Quantity')]);
        return response(new InvoiceResource($invoice), 200);


    }

}
