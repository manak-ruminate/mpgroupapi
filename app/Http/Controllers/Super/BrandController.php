<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockOutReport;
use App\Models\StockInReport;
use App\Models\StockOutHistory;
class BrandController extends Controller
{
    public function index()
    {
        $data = Brand::where('is_active',1)->get();
        return response(['data' => $data]);
    }

public function recent()
    {
        $data = Brand::withCount('product')->latest()
            ->where('is_active',1)
            ->get();
        return response(['data' => $data]);
    }
    public function add(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
        ]);

        $data = new Brand();
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'Brand created successfully']);
    }

    public function update(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'id',
        ]);

        $data =  Brand::find($request->get('id'));
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'Brand update successfully']);
    }

    public function delete($id)
    {
       
        $data =  Brand::find($id);
    
        $data->delete();
        return response(['msg' => 'Brand delete successfully']);
    }
  public function product_brand_stock_in($date)
{
    try {
         $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $results = \DB::table('inventories')
            ->join('products', 'products.sku', '=', 'inventories.sku_code')
            ->join('brands', 'brands.id', '=', 'products.brand')
            ->whereNotNull('products.brand')
            ->select('products.brand', 'brands.name',\DB::raw('SUM(inventories.total_qty) as total_qty')) 
            ->whereYear('inventories.product_date', $year)
                 ->whereMonth('inventories.product_date', $month)
            ->groupBy('products.brand', 'brands.name')
            ->orderByDesc(\DB::raw('SUM(inventories.total_qty)'))
            ->limit(10)
            ->get();


        return response(['data' => $results]);

    } catch (\Exception $ex) {
        return response()->json(['error' => $ex->getMessage()], 500);
    }
}


public function product_brand_stock_out($date)
{
     $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
              $results = \DB::table('stock_out_histories')
            ->join('products', 'products.sku', '=', 'stock_out_histories.sku_code')
            ->join('brands', 'brands.id', '=', 'products.brand')
            ->whereNotNull('products.brand')
            ->select('products.brand', 'brands.name',\DB::raw('SUM(stock_out_histories.previous_qty) as previous_qty'))
            ->whereYear('stock_out_histories.product_date', $year)
                 ->whereMonth('stock_out_histories.product_date', $month)
            ->groupBy('products.brand', 'brands.name')
            ->orderByDesc(\DB::raw('SUM(stock_out_histories.previous_qty)'))
            ->limit(10)
            ->get();


        return response(['data' => $results]);
}

}
