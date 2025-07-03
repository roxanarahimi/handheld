<?php

namespace App\Http\Controllers;


use App\Models\InventoryVoucher;
use App\Models\Invoice;
use App\Models\InvoiceAddress;
use App\Models\InvoiceItem;
use App\Models\InvoiceProduct;
use App\Models\Order;
use App\Models\Part;
use App\Models\PartUnit;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CacheController extends Controller
{
    public function cacheProducts()
    {
//        InvoiceProduct::query()->truncate();
        $productnumbers = InvoiceProduct::
        pluck('ProductNumber');
        $products = Product::where('CreationDate', '>=', today()->subDays(2))
            ->where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')
            ->whereNotIn('Number', $productnumbers)->get();
        foreach ($products as $item) {
            InvoiceProduct::create([
                'ProductName' => $item->Name,
                'ProductNumber' => $item->Number,
                'Description' => $item->Description
            ]);
        }
        $Codes = InvoiceProduct::

        pluck('ProductNumber');
        $parts = Part::where('CreationDate', '>=', today()->subDays(2))
            ->where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')
            ->whereNotIn('Code', $Codes)->get();
        foreach ($parts as $item) {
            InvoiceProduct::create([
                'ProductName' => $item->Name,
                'ProductNumber' => $item->Code,
                'Description' => $item->Description
            ]);
        }
    }

    public function getInventoryVouchers($inventoryVoucherIDs)
    {
        $partIDs = Part::where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')->pluck("PartID");
//        $storeIDs = DB::connection('sqlsrv')->table('LGS3.Store')
//            ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
//            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
//            ->whereNot(function ($query) {
//                $query->where('LGS3.Store.Name', 'LIKE', "%مارکتینگ%")
//                    ->orWhere('LGS3.Store.Name', 'LIKE', "%گرمدره%")
//                    ->orWhere('GNR3.Address.Details', 'LIKE', "%گرمدره%")
//                    ->orWhere('LGS3.Store.Name', 'LIKE', "%ضایعات%")
//                    ->orWhere('LGS3.Store.Name', 'LIKE', "%برگشتی%");
//            })
//            ->pluck('StoreID');
        $storeIDs = Store::orderBy('Code')
            ->whereNot(function ($query) {
                $query->where('Name', 'LIKE', '%گرمدره%')
                    ->orwhere('Name', 'LIKE', "%مارکتینگ%")
                    ->orWhere('Name', 'LIKE', "%ضایعات%")
                    ->orWhere('Name', 'LIKE', "%برگشتی%")
                    ->orWhere('Code', "1000");
            })
            ->whereNot(function ($q) {
                $q->whereHas('Plant', function ($x) {
                    $x->where('Name', 'LIKE', '%گرمدره%')
                        ->orwhereHas('Address', function ($y) {
                            $y->where('Details', 'LIKE', "%گرمدره%");
                        });
                });
            })
            ->pluck('StoreID');
//        $dat = InventoryVoucher::select("LGS3.InventoryVoucher.InventoryVoucherID", "LGS3.InventoryVoucher.Number",
//            "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartStoreRef", "AddressID",
//            'GNR3.RegionalDivision.Name as City')
//            ->join('LGS3.Store', 'LGS3.Store.StoreID', '=', 'LGS3.InventoryVoucher.CounterpartStoreRef')
//            ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
//            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
//            ->join('GNR3.RegionalDivision', 'GNR3.RegionalDivision.RegionalDivisionID', '=', 'GNR3.Address.RegionalDivisionRef')
//            ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(2))//
//            ->whereNotIn('LGS3.InventoryVoucher.InventoryVoucherID', $inventoryVoucherIDs)
//            ->whereIn('LGS3.Store.StoreID', $storeIDs)
//            ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
//            ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 68)
//            ->whereHas('OrderItems', function ($q) use ($partIDs) {
//                $q->whereIn('PartRef', $partIDs);
//            })
//            ->orderBy('LGS3.InventoryVoucher.InventoryVoucherID')
//            ->get();

        $dat = InventoryVoucher::where('Date', '>=', today()->subDays(2))//
        ->whereNotIn('InventoryVoucherID', $inventoryVoucherIDs)
            ->whereHas('Store', function ($s) use ($storeIDs) {
                $s->whereIn('StoreID', $storeIDs);
            })
            ->where('FiscalYearRef', 1405)
            ->where('InventoryVoucherSpecificationRef', 68)
            ->whereHas('OrderItems', function ($q) use ($partIDs) {
                $q->whereIn('PartRef', $partIDs);
            })
            ->orderBy('InventoryVoucherID')
            ->get();
        return $dat;
    }

    public function getInventoryVouchersDeputation($deputationIds)
    {
        $partIDs = Part::where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')->pluck("PartID");
//        $dat = InventoryVoucher::select("LGS3.InventoryVoucher.InventoryVoucherID", "LGS3.InventoryVoucher.Number",
//            "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartStoreRef",
//            "AddressID", 'GNR3.Address.Name as AddressName', 'GNR3.Address.Phone', 'Details', 'GNR3.RegionalDivision.Name as City')
//            ->join('GNR3.Party', 'GNR3.Party.PartyID', '=', 'LGS3.InventoryVoucher.CounterpartEntityRef')
//            ->join('GNR3.PartyAddress', 'GNR3.PartyAddress.PartyRef', '=', 'GNR3.Party.PartyID')
//            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'GNR3.PartyAddress.AddressRef')
//            ->join('GNR3.RegionalDivision', 'GNR3.RegionalDivision.RegionalDivisionID', '=', 'GNR3.Address.RegionalDivisionRef')
//            ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(2))//
//            ->whereNotIn('LGS3.InventoryVoucher.InventoryVoucherID', $deputationIds)
//            ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
//            ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 69)
//            ->whereHas('OrderItems', function ($q) use ($partIDs) {
//                $q->whereIn('PartRef', $partIDs);
//            })
//            ->where('GNR3.PartyAddress.IsMainAddress', "1")
//            ->orderBy('LGS3.InventoryVoucher.InventoryVoucherID')
//            ->get();
        $dat = InventoryVoucher::where('Date', '>=', today()->subDays(2))//
        ->whereNotIn('LGS3.InventoryVoucher.InventoryVoucherID', $deputationIds)
            ->where('FiscalYearRef', 1405)
            ->where('InventoryVoucherSpecificationRef', 69)
            ->whereHas('OrderItems', function ($q) use ($partIDs) {
                $q->whereIn('PartRef', $partIDs);
            })
            ->whereHas('Party', function ($x) use ($partIDs) {
                $x->whereHas('PartyAddress', function ($t) use ($partIDs) {
                    $t->where('IsMainAddress', "1");
                });
            })
            ->orderBy('InventoryVoucherID')
            ->get();
        return $dat;
    }

    public function getOrders($orderIDs)
    {
//        $dat2 = Order::select("SLS3.Order.OrderID", "SLS3.Order.Number",
//            "SLS3.Order.CreationDate", "Date as DeliveryDate", 'SLS3.Order.CustomerRef',
//            'GNR3.Address.AddressID', 'GNR3.RegionalDivision.Name as City')
//            ->join('SLS3.Customer', 'SLS3.Customer.CustomerID', '=', 'SLS3.Order.CustomerRef')
//            ->join('SLS3.CustomerAddress', 'SLS3.CustomerAddress.CustomerRef', '=', 'SLS3.Customer.CustomerID')
//            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'SLS3.CustomerAddress.AddressRef')
//            ->join('GNR3.RegionalDivision', 'GNR3.RegionalDivision.RegionalDivisionID', '=', 'GNR3.Address.RegionalDivisionRef')
////            ->where('SLS3.Order.Date', '>=', today()->subDays(2))
//            ->whereNotIn('SLS3.Order.OrderID', $orderIDs)
//            ->where('SLS3.Order.InventoryRef', 1)
//            ->where('SLS3.Order.State', 2)
//            ->where('SLS3.Order.FiscalYearRef', 1405)
//            ->where('SLS3.CustomerAddress.Type', 2)
//            ->whereHas('OrderItems')
//            ->whereHas('OrderItems', function ($q) {
//                $q->havingRaw('SUM(Quantity) >= ?', [50]);
//            })
//            ->orderBy('OrderID')
//            ->get();

//        $dat2 = OrderResource::collection($dat2);
        $dat2 = Order::
        where('Date', '>=', today()->subDays(2))
            ->whereNotIn('OrderID', $orderIDs)
            ->where('InventoryRef', 1)
            ->where('State', 2)
            ->where('FiscalYearRef', 1405)
            ->whereHas('Customer',function ($c){
                $c->whereHas('CustomerAddress',function ($a){
                    $a->where('Type', 2);
                });
            })
            ->whereHas('OrderItems')
            ->whereHas('OrderItems', function ($q) {
                $q->havingRaw('SUM(Quantity) >= ?', [50]);
            })
            ->orderBy('OrderID')
            ->get();
        return $dat2;
    }

    public function cacheInvoice()
    {
        try {
//


//            Invoice::query()->truncate();
//            InvoiceItem::query()->truncate();
//            InvoiceAddress::query()->truncate();
//            InvoiceProduct::query()->truncate();


            $this->cacheProducts();
            $inventoryVoucherIDs = Invoice::
            where('DeliveryDate', '>=', today()->subDays(2))->//
            where('Type', 'InventoryVoucher')->orderBy('id')->pluck('OrderID');
            $deputationIds = Invoice::
            where('DeliveryDate', '>=', today()->subDays(2))->//
            where('Type', 'Deputation')->orderBy('id')->pluck('OrderID');

//            $orderIDs = Invoice:://            where('DeliveryDate', '>=', today()->subDays(2))->
//            where('Type', 'Order')->orderBy('id')->pluck('OrderID');
            $d1 = $this->getInventoryVouchers($inventoryVoucherIDs);
            $d2 = $this->getInventoryVouchersDeputation($deputationIds);
//            $d3 = $this->getOrders($orderIDs);


            foreach ($d1 as $item) {
                $exx = Invoice::where('OrderID', $item->InventoryVoucherID)->where('OrderNumber', $item->Number)->where('Type', 'InventoryVoucher')->first();
                if (!$exx) {
                    $invoice = Invoice::create([
//                        'Type' => 'InventoryVoucher',
//                        'OrderID' => $item->InventoryVoucherID,
//                        'OrderNumber' => $item->Number,
//                        'AddressID' => $item->Store->Plant->Address->AddressID,
//                        'Sum' => $item->OrderItems->sum('Quantity'),
//                        'DeliveryDate' => $item->DeliveryDate
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
//                            'AddressID' => $item->Store->Plant->Address->AddressID,
//                            'AddressName' => $item->Store->Name,
//                            'Address' => $item->Store->Plant->Address->Details,
//                            'Phone' => $item->Store->Plant->Address->Phone,
//                            'city' => $item->City,
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
            foreach ($d2 as $item) {
                $exx2 = Invoice::where('OrderID', $item->InventoryVoucherID)->where('OrderNumber', $item->Number)->where('Type', 'Deputation')->first();
                if (!$exx2) {
                    $invoice = Invoice::create([
//                        'Type' => 'Deputation',
//                        'OrderID' => $item->InventoryVoucherID,
//                        'OrderNumber' => $item->Number,
//                        'AddressID' => $item->AddressID,
//                        'Sum' => $item->OrderItems->sum('Quantity'),
//                        'DeliveryDate' => $item->DeliveryDate
                        'Type' => 'Deputation',
                        'OrderID' => $item2->InventoryVoucherID,
                        'OrderNumber' => $item2->Number,
                        'AddressID' => $item2->Party->PartyAddress->Address->AddressID,
                        'Sum' => $item2->OrderItems->sum('Quantity'),
                        'DeliveryDate' => $item2->Date
                    ]);
                    $address = InvoiceAddress::where('AddressID', $item->AddressID)->first();
                    if (!$address) {
                        InvoiceAddress::create([
//                            'AddressID' => $item->AddressID,
//                            'AddressName' => $item->AddressName,
//                            'Address' => $item->Details,
//                            'Phone' => $item->Phone,
//                            'city' => $item->City
                            'AddressID' => $item2->Party->PartyAddress->Address->AddressID,
                            'AddressName' => $item2->Party->PartyAddress->Address->Name,
                            'Address' => $item2->Party->PartyAddress->Address->Details,
                            'Phone' => $item2->Party->PartyAddress->Address->Phone,
                            'city' => $item2->Party->PartyAddress->Address->Region->Name
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
//            foreach ($d3 as $item) {
//                $exx3 = Invoice::where('OrderID',$item->OrderID)->where('OrderNumber',$item->Number)->where('Type','Order')->first();
//                if(!$exx3){
//                    $invoice = Invoice::create([
//                        'Type' => 'Order',
//                        'OrderID' => $item->OrderID,
//                        'OrderNumber' => $item->Number,
//                        'AddressID' => $item->Customer->CustomerAddress->Address->AddressID,
//                        'Sum' => $item->OrderItems->sum('Quantity'),
//                        'DeliveryDate' => $item->DeliveryDate
//                    ]);
//                    $address = InvoiceAddress::where('AddressID', $item->Customer->CustomerAddress->Address->AddressID)->first();
//                    if (!$address) {
//                        InvoiceAddress::create([
//                            'AddressID' => $item->Customer->CustomerAddress->Address->AddressID,
//                            'AddressName' => $item->Customer->CustomerAddress->Address->Name,
//                            'Address' => $item->Customer->CustomerAddress->Address->Details,
//                            'Phone' => $item->Customer->CustomerAddress->Address->Phone,
//                            'city' => $item->Customer->CustomerAddress->Address->Region->Name
//                        ]);
//                    }
//                    foreach ($item->OrderItems as $item2) {
//                        $exist = InvoiceItem::where('invoice_id',$invoice->id)->where('ProductNumber',$item2->Product->Number)->first();
//                        if ($exist){
//                            $exist->update(['Quantity'=>$exist->Quantity + $item2->Quantity]);
//                        }else{
//                            if (!str_contains($item2->Product->Name,'لیوانی') && !str_contains($item2->Product->Name,'کیلویی')){
//                                $invoiceItem = InvoiceItem::create([
//                                    'invoice_id' => $invoice->id,
//                                    'ProductNumber' => $item2->Product->Number,
//                                    'Quantity' => $item2->Quantity,
//                                ]);
//                            }
//
//                        }
//                        $product = InvoiceProduct::where('ProductNumber', $item2->Product->Number)->first();
//                        if (!$product) {
//                            if (!str_contains($item2->Product->Name,'لیوانی') && !str_contains($item2->Product->Name,'کیلویی')){
//                                InvoiceProduct::create([
//                                    'ProductName' => $item2->Product->Name,
//                                    'ProductNumber' => $item2->Product->Number,
//                                    'Description' => $item2->Product->Description
//                                ]);
//                            }
//
//                        }
//                    }
//                }
//            }
            $datetime = new \DateTime("now", new \DateTimeZone("Asia/Tehran"));
            $nowTime = $datetime->format('Y-m-d G:i');
            echo $nowTime . ' - Tehran Time: cache is ok
';
        } catch (\Exception $exception) {
            $datetime = new \DateTime("now", new \DateTimeZone("Asia/Tehran"));
            $nowTime = $datetime->format('Y-m-d G:i');
            echo $nowTime . ' - Tehran Time: ' . $exception->getMessage() . '
';
        }
    }

}
