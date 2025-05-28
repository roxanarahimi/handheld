<?php


namespace App\Http\Controllers;

use App\Http\Middleware\Token;
use App\Http\Resources\InventoryVoucherItemResource;
use App\Http\Resources\InventoryVoucherResource;
use App\Http\Resources\InvoiceBarcodeResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceResource2;
use App\Http\Resources\OrderResource;
use App\Http\Resources\RemittanceResource;
use App\Models\Address;
use App\Models\InventoryVoucher;
use App\Models\InventoryVoucherItem;
use App\Models\Invoice;
use App\Models\InvoiceAddress;
use App\Models\InvoiceBarcode;
use App\Models\InvoiceItem;
use App\Models\InvoiceProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use App\Models\PartUnit;
use App\Models\Party;
use App\Models\PartyAddress;
use App\Models\Product;
use App\Models\Remittance;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\select;
use App\Models\Unit;
use function PHPUnit\Framework\returnSelf;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

class RemittanceController extends Controller
{
    public function __construct(Request $request)
    {
        $this->middleware(Token::class)->except('readOnly', 'readOnly1');
    }

    public function getInventoryVouchers()
    {
        $partIDs = Part::where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')->pluck("PartID");
        $storeIDs = DB::connection('sqlsrv')->table('LGS3.Store')
            ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
            ->whereNot(function ($query) {
                $query->where('LGS3.Store.Name', 'LIKE', "%مارکتینگ%")
                    ->orWhere('LGS3.Store.Name', 'LIKE', "%گرمدره%")
                    ->orWhere('GNR3.Address.Details', 'LIKE', "%گرمدره%")
                    ->orWhere('LGS3.Store.Name', 'LIKE', "%ضایعات%")
                    ->orWhere('LGS3.Store.Name', 'LIKE', "%برگشتی%");
            })
            ->pluck('StoreID');
        $dat = InventoryVoucher::select("LGS3.InventoryVoucher.InventoryVoucherID", "LGS3.InventoryVoucher.Number",
            "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartStoreRef", "AddressID",
            "InventoryVoucherSpecificationRef", 'GNR3.RegionalDivision.Name as City')
            ->join('LGS3.Store', 'LGS3.Store.StoreID', '=', 'LGS3.InventoryVoucher.CounterpartStoreRef')
            ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
            ->join('GNR3.RegionalDivision', 'GNR3.RegionalDivision.RegionalDivisionID', '=', 'GNR3.Address.RegionalDivisionRef')
            ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(2))//
//            ->whereNotIn('LGS3.InventoryVoucher.InventoryVoucherID', $inventoryVoucherIDs)
            ->whereIn('LGS3.Store.StoreID', $storeIDs)
            ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
            ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 68)
            ->whereHas('OrderItems', function ($q) use ($partIDs) {
                $q->whereIn('PartRef', $partIDs);
            })
            ->orderBy('LGS3.InventoryVoucher.InventoryVoucherID')
            ->get();


        $dat = InventoryVoucherResource::collection($dat);
        return $dat;
    }
    public function getInventoryVouchersDeputation()
    {
        $partIDs = Part::where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')->pluck("PartID");
        $dat = InventoryVoucher::select("LGS3.InventoryVoucher.InventoryVoucherID", "LGS3.InventoryVoucher.Number",
            "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartStoreRef","InventoryVoucherSpecificationRef",
            "AddressID", 'GNR3.Address.Name as AddressName', 'GNR3.Address.Phone', 'Details', 'GNR3.RegionalDivision.Name as City')
            ->join('GNR3.Party', 'GNR3.Party.PartyID', '=', 'LGS3.InventoryVoucher.CounterpartEntityRef')
            ->join('GNR3.PartyAddress', 'GNR3.PartyAddress.PartyRef', '=', 'GNR3.Party.PartyID')
            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'GNR3.PartyAddress.AddressRef')
            ->join('GNR3.RegionalDivision', 'GNR3.RegionalDivision.RegionalDivisionID', '=', 'GNR3.Address.RegionalDivisionRef')
            ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(2))//
//            ->whereNotIn('LGS3.InventoryVoucher.InventoryVoucherID', $deputationIds)
            ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
            ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 69)
            ->whereHas('OrderItems', function ($q) use ($partIDs) {
                $q->whereIn('PartRef', $partIDs);
            })
            ->where('GNR3.PartyAddress.IsMainAddress', "1")
            ->orderBy('LGS3.InventoryVoucher.InventoryVoucherID')
            ->get();
        $dat = InventoryVoucherResource::collection($dat);
        return $dat;
    }

    public function getOrders()
    {
        $dat2 = Order::select("SLS3.Order.OrderID", "SLS3.Order.Number",
            "SLS3.Order.CreationDate", "Date as DeliveryDate", 'SLS3.Order.CustomerRef')
            ->join('SLS3.Customer', 'SLS3.Customer.CustomerID', '=', 'SLS3.Order.CustomerRef')
            ->join('SLS3.CustomerAddress', 'SLS3.CustomerAddress.CustomerRef', '=', 'SLS3.Customer.CustomerID')
            ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'SLS3.CustomerAddress.AddressRef')
            ->where('SLS3.Order.Date', '>=', today()->subDays(7))
            ->where('SLS3.Order.InventoryRef', 1)
            ->where('SLS3.Order.State', 2)
            ->where('SLS3.Order.FiscalYearRef', 1405)
            ->where('SLS3.CustomerAddress.Type', 1)//2?
            ->whereHas('OrderItems')
            ->whereHas('OrderItems', function ($q) {
                $q->havingRaw('SUM(Quantity) >= ?', [50]);
            })
            ->orderBy('OrderID', 'DESC')
            ->get();

        $dat2 = OrderResource::collection($dat2);
        return $dat2;
    }

    public function getStores(Request $request)
    {

        try {
            $t = Store::select("LGS3.Store.StoreID", "LGS3.Store.Name as Name", "GNR3.Address.Details")
                ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
                ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
                ->whereNot('LGS3.Store.Name', 'LIKE', "%مارکتینگ%")
                ->whereNot('LGS3.Store.Name', 'LIKE', "%گرمدره%")
                ->whereNot('GNR3.Address.Details', 'LIKE', "%گرمدره%")
                ->whereNot('LGS3.Store.Name', 'LIKE', "%ضایعات%")
                ->whereNot('LGS3.Store.Name', 'LIKE', "%برگشتی%");
            if (isset($request['search'])) {
                $t = $t->where('LGS3.Store.Name', 'LIKE', "%" . $request['search'] . "%")
                    ->orWhere('GNR3.Address.Details', 'LIKE', "%" . $request['search'] . "%");
            }
            $t = $t->get();
            return response()->json($t, 200);
        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function readOnly1(Request $request)
    {
        // Old version, Direct request to ERP Server using relationships
        $dat = $this->getInventoryVouchers();
        $dat2 = $this->getInventoryVouchersDeputation();
        $filtered = json_decode(json_encode($dat));
        $filtered2 = json_decode(json_encode($dat2));
        $input1 = array_values($filtered);
        $input2 = array_values($filtered2);
        $input = array_merge($input1, $input2);


        $offset = 0;
        $perPage = 100;
        if ($request['page'] && $request['page'] > 1) {
            $offset = ($request['page'] - 1) * $perPage;
        }
        $info = array_slice($input, $offset, $perPage);
        $paginator = new LengthAwarePaginator($info, count($input), $perPage, $request['page']);

        return response()->json($paginator, 200);

    }

    public function readOnly(Request $request)
    {
        try {
            //Mainnnnnnnnn   // Old version, Direct request to ERP Server using join
            $partIDs = Part::where('Name', 'like', '%نودالیت%')->whereNot('Name', 'like', '%لیوانی%')->whereNot('Name', 'like', '%کیلویی%')->pluck("PartID");
            $storeIDs = DB::connection('sqlsrv')->table('LGS3.Store')
                ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
                ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
                ->whereNot(function ($query) {
                    $query->where('LGS3.Store.Name', 'LIKE', "%مارکتینگ%")
                        ->orWhere('LGS3.Store.Name', 'LIKE', "%گرمدره%")
                        ->orWhere('GNR3.Address.Details', 'LIKE', "%گرمدره%")
                        ->orWhere('LGS3.Store.Name', 'LIKE', "%ضایعات%")
                        ->orWhere('LGS3.Store.Name', 'LIKE', "%برگشتی%");
                })
                ->pluck('StoreID');

            $dat = DB::connection('sqlsrv')->table('LGS3.InventoryVoucher')->
            select([
                "LGS3.InventoryVoucher.InventoryVoucherID as OrderID", "LGS3.InventoryVoucher.Number as OrderNumber",
                "LGS3.Store.Name as AddressName", "GNR3.Address.Details as Address", "Phone",
                "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartEntityText"])
                ->join('LGS3.Store', 'LGS3.Store.StoreID', '=', 'LGS3.InventoryVoucher.CounterpartStoreRef')
                ->join('LGS3.Plant', 'LGS3.Plant.PlantID', '=', 'LGS3.Store.PlantRef')
                ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'LGS3.Plant.AddressRef')
                ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
                ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(7))
                ->whereIn('LGS3.Store.StoreID', $storeIDs)
                ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 68)
                ->orderByDesc('LGS3.InventoryVoucher.InventoryVoucherID')
                ->get()->toArray();
            $dat2 = DB::connection('sqlsrv')->table('LGS3.InventoryVoucher')->
            select([
                "LGS3.InventoryVoucher.InventoryVoucherID as OrderID", "LGS3.InventoryVoucher.Number as OrderNumber",
                "GNR3.Address.Name as AddressName", "GNR3.Address.Details as Address", "Phone",
                "LGS3.InventoryVoucher.CreationDate", "Date as DeliveryDate", "CounterpartEntityText", "CounterpartEntityRef"])
                ->join('GNR3.Party', 'GNR3.Party.PartyID', '=', 'LGS3.InventoryVoucher.CounterpartEntityRef')
                ->join('GNR3.PartyAddress', 'GNR3.PartyAddress.PartyRef', '=', 'GNR3.Party.PartyID')
                ->join('GNR3.Address', 'GNR3.Address.AddressID', '=', 'GNR3.PartyAddress.AddressRef')
                ->where('LGS3.InventoryVoucher.FiscalYearRef', 1405)
                ->where('LGS3.InventoryVoucher.Date', '>=', today()->subDays(7))
                ->where('LGS3.InventoryVoucher.InventoryVoucherSpecificationRef', 69)
                ->where('GNR3.PartyAddress.IsMainAddress', "1")
                ->orderByDesc('LGS3.InventoryVoucher.InventoryVoucherID')
                ->get()->toArray();
            foreach ($dat as $item) {
                $item->{'type'} = 'InventoryVoucher';
                $item->{'ok'} = 1;
                $item->{'AddressName'} = $item->{'AddressName'} . ' ' . $item->{'OrderNumber'};
                $details = DB::connection('sqlsrv')->table('LGS3.InventoryVoucherItem')
                    ->select(["LGS3.Part.Name as ProductName", "LGS3.InventoryVoucherItem.Quantity as Quantity",
                        "LGS3.Part.PartID as Id", "LGS3.Part.Code as ProductNumber"])
                    ->join('LGS3.Part', 'LGS3.Part.PartID', '=', 'LGS3.InventoryVoucherItem.PartRef')
                    ->where('InventoryVoucherRef', $item->{'OrderID'})
                    ->whereIn('PartRef', $partIDs)
                    ->get()->toArray();
                $item->{'OrderItems'} = $details;

            }
            foreach ($dat2 as $item) {
                $item->{'type'} = 'InventoryVoucher';
                $item->{'ok'} = 1;
                $item->{'AddressName'} = $item->{'CounterpartEntityText'} . ' ' . $item->{'OrderNumber'};
                $details = DB::connection('sqlsrv')->table('LGS3.InventoryVoucherItem')
                    ->select(["InventoryVoucherItemID", "LGS3.Part.Name as ProductName", "LGS3.InventoryVoucherItem.Quantity as Quantity",
                        "LGS3.InventoryVoucherItem.PartRef", "LGS3.Part.PartID as Id", "LGS3.Part.Code as ProductNumber"])
                    ->join('LGS3.Part', 'LGS3.Part.PartID', '=', 'LGS3.InventoryVoucherItem.PartRef')
                    ->where('InventoryVoucherRef', $item->{'OrderID'})
                    ->whereIn('PartRef', $partIDs)
                    ->OrderBy('PartRef')
                    ->get()->toArray();

                foreach ($details as $itemN) {
                    $itemX = InventoryVoucherItem::where('InventoryVoucherItemID', $itemN->{'InventoryVoucherItemID'})->first();
                    $q = $itemX->Quantity;
                    $int = (int)$itemX->Quantity;
                    if (str_contains($itemX->PartUnit->Name, 'پک')) {
                        $t = (int)PartUnit::where('PartID', $itemX->PartRef)->where('Name', 'like', '%کارتن%')->pluck('DSRatio')[0];
                        $q = (string)floor($int / $t);
                        $itemN->{'Quantity'} = $q;
                    }
                }

                $detailsU = [];
                foreach ($details as $d) {
                    $ref = array_filter($details, function ($b) use ($d) {
                        return $b->PartRef == $d->PartRef;
                    });
                    $q = array_sum(array_column($ref, 'Quantity'));

                    $f = array_filter($detailsU, function ($e) use ($d) {
                        return $e->PartRef == $d->PartRef;
                    });
                    if (!$f) {
                        $d->Quantity = (string)$q;
                        $detailsU[] = $d;
                    }


                }
                $item->{'OrderItems'} = $detailsU;
            }

            $filtered = array_filter($dat, function ($el) {
                return count($el->{'OrderItems'}) > 0;
            });
            $filtered2 = array_filter($dat2, function ($el) {
                return count($el->{'OrderItems'}) > 0;
            });
            $input1 = array_values($filtered);
            $input2 = array_values($filtered2);
            $input = [];
            foreach ($input1 as $item) {
                $input[] = $item;
            }
            foreach ($input2 as $item) {
                $input[] = $item;
            }
            $offset = 0;
            $perPage = 100;
            if ($request['page'] && $request['page'] > 1) {
                $offset = ($request['page'] - 1) * $perPage;
            }
            $info = array_slice($input, $offset, $perPage);
            $paginator = new LengthAwarePaginator($info, count($input), $perPage, $request['page']);
            return response()->json($paginator, 200);

        } catch (\Exception $exception) {
            return response($exception);
        }
    }

    public function test()
    {
        $t = PartUnit::where('PartID', "1746")->where('Name', 'like', '%کارتن%')->get();
        return $t;
    }


}
