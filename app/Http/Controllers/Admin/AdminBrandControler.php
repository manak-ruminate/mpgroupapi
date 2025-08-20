<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockOutHistory;
use App\Models\Inventory;

class AdminBrandControler extends Controller
{
    public function stock_in_brand($date)
    {

        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        try {
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);

            $results = \DB::table('inventories')
                ->join('products', 'products.sku', '=', 'inventories.sku_code')
                ->join('brands', 'brands.id', '=', 'products.brand')
                ->whereNotNull('products.brand')
                ->select('products.brand', 'brands.name', \DB::raw('SUM(inventories.total_qty) as total_qty'))
                ->groupBy('products.brand', 'brands.name')
                ->orderByDesc(\DB::raw('SUM(inventories.total_qty)'))
                ->where('inventories.user_id', \Auth::user()->id)
                ->limit(10)
                ->get();


            return response(['data' => $results]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }

    public function stock_out_brand($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $results = \DB::table('stock_out_histories')
            ->join('products', 'products.sku', '=', 'stock_out_histories.sku_code')
            ->join('brands', 'brands.id', '=', 'products.brand')
            ->whereNotNull('products.brand')
            ->select('products.brand', 'brands.name', \DB::raw('SUM(stock_out_histories.previous_qty) as previous_qty'))
            ->groupBy('products.brand', 'brands.name')
            ->orderByDesc(\DB::raw('SUM(stock_out_histories.previous_qty)'))
            ->where('stock_out_histories.user_id', \Auth::user()->id)
            ->limit(10)
            ->get();


        return response(['data' => $results]);
    }

    public function stock_in_categorys($date)
    {
        try {
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);

           $results = \DB::table('inventories')
                ->join('products as p1', 'p1.sku', '=', 'inventories.sku_code')
                ->join('categories', 'categories.id', '=', 'p1.category_id')
                ->whereNotNull('p1.category_id')
                ->select('p1.category_id', 'categories.name', \DB::raw('SUM(inventories.total_qty) as total_qty'))
                ->groupBy('p1.category_id', 'categories.name')
                ->orderByDesc(\DB::raw('SUM(inventories.total_qty)'))
                ->where('inventories.user_id', \Auth::user()->id)
                ->limit(10)
                ->get();
            return response(['data' => $results]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }


    public function stock_out_categorys($date)
    {
        try {
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            $results = \DB::table('stock_out_histories')
                ->join('products', 'products.sku', '=', 'stock_out_histories.sku_code')
                ->join('categories', 'categories.id', '=', 'products.category_id')  // Fixed the join with categories
                ->whereNotNull('products.category_id')
                ->select('products.category_id', 'categories.name', \DB::raw('SUM(stock_out_histories.previous_qty) as previous_qty'))
                ->groupBy('products.category_id', 'categories.name')
                ->orderByDesc(\DB::raw('SUM(stock_out_histories.previous_qty)'))
                ->where('stock_out_histories.user_id', \Auth::user()->id)
                ->limit(10)
                ->get();

            return response(['data' => $results]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}
