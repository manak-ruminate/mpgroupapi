<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Inventory;
use App\Models\StockOutHistory;

class CategoryController extends Controller
{
    public function index()
    {
        $data = Category::with('subcategories')
            ->where('is_active', 1)
            ->withCount('products')
            ->get();
        return response(['data' => $data]);
    }
    public function add(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
        ]);

        $data = new Category();
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'category created successfully']);
    }



    public function update(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',
        ]);

        $data = Category::find($request->get('id'));
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'category update successfully']);
    }

    public function delete($id)
    {
        $data = Category::find($id);
        $data->delete();
        return response(['msg' => 'category update successfully']);
    }

    public function product_category($category)
    {
        $data = Subcategory::where('category_id', $category)->get();
        return response(['data' => $data]);
    }

    public function product_sub_category($type, $id)
    {

        if ($type == 'category') {
            $data = Product::select('products.*', 'brands.name as brandname')
                ->leftjoin('brands', 'products.brand', 'brands.id')
                ->where('products.is_web', 1)
                ->where('category_id', $id)->get();
        } else {
            $data = Product::select('products.*', 'brands.name as brandname')
                ->leftjoin('brands', 'products.brand', 'brands.id')
                ->where('products.is_web', 1)
                ->where('sub_category', $id)->get();
        }


        return response(['data' => $data]);
    }

    public function product($id)
    {
        $data =  Product::select('products.*', 'godowns.name as godownsname', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
            ->leftjoin('godowns', 'products.godowns_id', 'godowns.id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->leftjoin('subcategories', 'products.sub_category', 'subcategories.id')
            ->leftjoin('brands', 'products.brand', 'brands.id')
            ->where('products.is_active', 1)
            ->where('products.is_web', 1)
            ->where('products.id', $id)
            ->first();

        return response(['data' => $data], 200);
    }
    public function category_wise()
    {
        $categories = Category::where('is_active', 1)
            ->get();

        foreach ($categories as $category) {
            $category->products = Product::select('name', 'image', 'id')
                ->where('category_id', $category->id)
                ->where('products.is_web', 1)
                ->limit(8)
                ->get();
        }

        // Return response with the categories and their products
        return response(['data' => $categories]);
    }
    public function search($type, $search)
    {
        try {
            $produtc = Product::select('products.*', 'brands.name as brandname')
                ->join('brands', 'products.brand', '=', 'brands.id')
                ->where('products.is_web', 1);

            if ($search) {
                $produtc->where(function ($query) use ($search) {
                    $query->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%");
                });
            }

            $produtc = $produtc->limit(10)->get();







            if ($produtc) {
                return response()->json([
                    'status' => true,
                    'data' => $produtc,
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ], 500);
        }
    }
    public function product_categorys($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $data = Inventory::with(['product.category' => function ($query) {
            $query->where('is_web', 1);
        }])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('total_qty', 'desc')
            ->take(10)
            ->get();


        return response(['data' => $data]);
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
                ->whereYear('inventories.product_date', $year)
                 ->whereMonth('inventories.product_date', $month)
                ->groupBy('p1.category_id', 'categories.name')
                ->orderByDesc(\DB::raw('SUM(inventories.total_qty)'))
                ->limit(10)
                ->get();
            return response(['data' => $results]);

            return response(['data' => $data]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }

    public function stock_out_categorys($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $results = \DB::table('stock_out_histories')
            ->join('products', 'products.sku', '=', 'stock_out_histories.sku_code')
            ->join('categories', 'categories.id', '=', 'products.category_id')  // Fixed the join with categories
            ->whereNotNull('products.category_id')
            ->select('products.category_id', 'categories.name', \DB::raw('SUM(stock_out_histories.previous_qty) as previous_qty'))
            ->whereYear('stock_out_histories.product_date', $year)
                 ->whereMonth('stock_out_histories.product_date', $month)
            ->groupBy('products.category_id', 'categories.name')
            ->orderByDesc(\DB::raw('SUM(stock_out_histories.previous_qty)'))
            ->limit(10)
            ->get();

        return response(['data' => $results]);
    }
}
