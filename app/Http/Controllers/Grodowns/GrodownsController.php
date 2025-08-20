<?php

namespace App\Http\Controllers\Grodowns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\HoldQty;
use App\Models\Device;

class GrodownsController extends Controller
{
    public function product()
    {

        $data = Product::select('products.*', 'godowns.name as godownsname', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
            ->leftjoin('godowns', 'products.godowns_id', 'godowns.id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->leftjoin('subcategories', 'products.sub_category', 'subcategories.id')
            ->leftjoin('brands', 'products.brand', 'brands.id')
            ->where('products.is_active', 1)
            ->get();
        foreach ($data as $datas) {
            $inventories = Inventory::where('product_id', $datas->id)
                // ->where('godowns_id',\Auth::user()->godowns_id)
                ->with('godownsName')
                ->with('stockout')
                ->with('holdqty')->get();
            $datas->inventories = $inventories;
        }
        return response(['data' => $data]);
    }

    public function update(Request $request)
    {
        $inventory = new  Inventory();
        $inventory->user_id = \Auth::user()->id;
        $inventory->product_id = $request->get('product_id');
        $inventory->batch_id = $request->get('batch');
        $inventory->save();
        return response(['data' => $inventory, 'msg' => 'inventory update successfully']);
    }

    public function hold_in(Request $request)
    {
        $data = new HoldQty();
        $data->inventories_id = $request->get('id');
        $data->hold_qty = $request->get('hold_qty');
        $data->user_id = \Auth::user()->id;
        $data->holdername = \Auth::user()->name;
        $data->godowns_id = $request->get('godowns_id');
        $data->remarks = $request->get('remarks');
        $data->save();
        return response(['data' => 'product hold succesfully']);
    }
    public function hold_release(Request $request)
    {
        $data = HoldQty::where('id', $request->get('id'))->where('user_id', \Auth::user()->id)->first();
        if ($data) {
            $data->delete();
            return response(['data' => 'product hold  release succesfully'], 200);
        }
        return response(['data' => 'no found'], 400);
    }

    public function profile()
    {
        $data = \Auth::user();

        $data['device'] =  Device::where('user_id', $data->id)->latest()->first();
        return response(['data' => $data]);
    }
    public function hold_quantity(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $query = HoldQty::with(['inventory']);

        if ($request->has('search') && $request->input('search') !== null) {
            $search = $request->input('search');
            $query->whereHas('inventory.product', function ($query) use ($search) {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            })
                ->orWhere('holdername', 'like', "%{$search}%");;
        }

        $data = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->latest()
            ->get();
        return $data;
        return response(['data' => $data]);
    }


    public function hold_quantity_user(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $query = HoldQty::with(['inventory'])
            ->where('user_id', \Auth::user()->id);
        if ($request->has('search') && $request->input('search') !== null) {
            $search = $request->input('search');
            $query->whereHas('inventory.product', function ($query) use ($search) {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            })
                ->orWhere('holdername', 'like', "%{$search}%");;
        }
        $data = $query->skip(($page - 1) * $limit)->take($limit)->latest()->get();
        return response(['data' => $data]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['category', 'subcategory', 'brand']);

        if ($request->has('search') && $request->input('search') !== null) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('brand') && $request->input('brand') !== null) {
            $brand = $request->input('brand');
            $query->where('brand', $brand);
        }
        if ($request->has('finish') && $request->input('finish') !== null) {
            $finish = $request->input('finish');
            $query->where('finish', $finish);
        }
        if ($request->has('category') && $request->input('category') !== null) {
            $category = $request->input('category');
            $query->where('category_id', $category);
        }

        if ($request->has('size') && $request->input('size') !== null) {
            $size = $request->input('size');
            $query->where('size', $size);
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);


        $count = $query->count();


        $data = $query->skip(($page - 1) * $limit)->take($limit)->latest()->get();
        return response(['data' => $data]);
    }

    public function inventory(Request $request)
    {
        $query = Inventory::with(['product.category', 'product.subcategory', 'product.brand', 'godownsName']);

        if ($request->has('search') && $request->input('search') !== null) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                //   $query->orWhere('sku_code', $search);
                $query->whereHas('product', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', $search);
                });
            });
        }
        if ($request->has('category') && $request->input('category') !== null) {
            $categoryName = $request->get('category');
            $query->whereHas('product.category', function ($q) use ($categoryName) {
                $q->where('name', $categoryName);
            });
        }
        if ($request->has('subcategory') && $request->input('subcategory') !== null) {
            $subcategoryName = $request->get('subcategory');
            $query->whereHas('product.subcategory', function ($q) use ($subcategoryName) {
                $q->where('name', $subcategoryName);
            });
        }
        if ($request->has('brand') && $request->input('brand') !== null) {
             $brand = $request->input('brand');
            $query->whereHas('product.brand', function ($q) use ($brand) {
                $q->where('name', $brand);
            });
        }

        if ($request->has('size') && $request->input('size') !== null) {
            $size = $request->input('size');
            $query->whereHas('product', function ($q) use ($size) {
                $q->where('size', $size);
            });
        }

        if ($request->has('finish') && $request->input('finish') !== null) {
            $finish = $request->input('finish');
            $query->whereHas('product', function ($q) use ($finish) {
                $q->where('finish', $finish);
            });
        }

        if ($request->has('godown') && $request->input('godown') !== null) {
            $godown = $request->get('godown');
            $query->where('godowns_id', $godown);
        }

        $page = $request->get('page', 1);
        $limits = $request->get('limit', 10);
        $filteredCount = $query->count();
        $data = $query->skip(($page - 1) * $limits)->take($limits)->latest()->get();
        return response()->json([
            'data' => $data,
            'count' => $filteredCount,
        ]);
    }

    public function hold_quantity_comman(Request $request)
    {
        $value =  \DB::table('hold_qties')
            ->join('inventories', 'inventories.id', '=', 'hold_qties.inventories_id')
            ->select('hold_qties.inventories_id', 'inventories.id', \DB::raw('COUNT(*) as count'))
            ->groupBy('hold_qties.inventories_id', 'inventories.id')
            ->having(\DB::raw('COUNT(*)'), '>', 1)
            ->first();
            if(isset($value)){
        $query =   HoldQty::with(['inventory', 'user'])
            ->where('inventories_id', $value->inventories_id);

        if ($request->has('search') && $request->input('search') !== null) {
            $search = $request->input('search');
            $query->whereHas('inventory.product', function ($query) use ($search) {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            })
                ->orWhere('holdername', 'like', "%{$search}%");;
        }

        $page = $request->get('page', 1);
        $limits = $request->get('limit', 10);
        $filteredCount = $query->count();
        $data = $query->skip(($page - 1) * $limits)->take($limits)->latest()->get();
        return response()->json(['data' => $data]);
            }
            $data = [];
             return response()->json(['data' =>$data]);
    }
}
