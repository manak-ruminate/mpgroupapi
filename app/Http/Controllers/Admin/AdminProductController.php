<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockOutHistory;
use App\Models\HoldQty;
use App\Models\ReturnProduct;
use App\Models\ProductDemage;
use App\Models\StockInReport;
use App\Models\StockOutReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;
use App\Models\Category;
class AdminProductController extends Controller
{
    public function index(Request $request)
   {
        $search = $request->get('search', '');
        $query = Product::select('products.*', 'godowns.name as godownsname', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
            ->leftjoin('godowns', 'products.godowns_id', 'godowns.id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->leftjoin('subcategories', 'products.sub_category', 'subcategories.id')
            ->leftjoin('brands', 'products.brand', 'brands.id')
            ->where('products.is_active', 1)
            ->with('stockInReports')
            ->latest();
            if (isset($search) && $search != '') {
    $query->where(function($q) use ($search) {
        $q->where('products.name', 'like', '%' . $search . '%')
          ->orWhere('products.sku', 'like', '%' . $search . '%');
    });
    $data = $query->get();
        foreach ($data as $datas) {
            $inventories = Inventory::where('sku_code', $datas->sku)
                ->where('godowns_id', \Auth::user()->godowns_id)
                ->with(['stockout', 'holdqty'])
                ->get();

            $datas->inventories = $inventories;
        }
        return response(['data' => $data]);
        // $data = Product::with('stockInReports')->latest()->get();
        // return response(['data' => $data]);
    }
}

    public function add(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }
            $existingSku = NULL;
            if ($request->get('sku')) {
                $validator = \Validator::make($request->all(), [
                    'sku' => 'required|unique:products,sku',
                    'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:1024',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()->first()], 422);
                }
            } else {
                do {
                    $sku_code = rand(1000000, 9999999); // Generate a random SKU code
                    $existingSku = Product::where('sku', $sku_code)->first();
                } while ($existingSku); // If SKU exists, regenerate until it's unique
            }
            $imagePath = NULL;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('product', 'public');
            }
            $product = new Product();
            $product->name = $request->get('name');
            $product->sku = $request->get('sku') ?: $existingSku;
            $product->size = $request->get('size');
            $product->color = $request->get('color');
            $product->brand = $request->get('brand');
            $product->category_id = $request->get('category_id');
            $product->sub_category = $request->get('sub_category');
            $product->product_in_date = $request->get('product_in_date');
            $product->thickness = $request->get('thickness');
            // $product->batch = $request->get('batch');
            $product->uom = $request->get('uom');
            $product->finish = $request->get('finish');
            $product->image = $imagePath;
            // $data->description = $request->get('description') ??NULL;
            $product->save();
            return response()->json(['msg' => 'Products created successfully'], 201);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage()); // Log the actual error message
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            $request->validate([
                // 'name' => 'required',
                'id' => 'required',
            ]);

            $data =  Product::find($request->get('id'));
            $imagePath = $data ? $data->image : '';
            if ($data) {
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('product', 'public');
                }

                $data->name = $request->get('name') ? $data->name = $request->get('name') : $data->name;
                $data->brand = $request->get('brand') ? $request->get('brand') : $data->brand;
                $data->size = $request->get('size') ? $request->get('size') : $data->size;
                $data->uom = $request->get('uom') ? $request->get('uom') : $data->uom;
                $data->category_id = $request->get('category_id') ? $request->get('category_id') : $data->category_id;
                $data->sub_category =  $request->get('sub_category');
                $data->image = $imagePath ? $imagePath : $data->image;
                $data->color = $request->get('color') ? $request->get('color') : $data->color;
                $data->dis_continue =  $request->get('dis_continue');
                $data->finish = $request->get('finish');
                $data->product_in_date = $request->get('product_in_date') ? $request->get('product_in_date') : date('Y-m-d');;
                $data->is_active = $request->get('is_active') ?  $request->get('is_active') : $data->is_active;
                $data->thickness = $request->get('thickness');
                $data->description = $request->get('description');
                $data->save();
                return response(['msg' => 'product update succesfully']);
            } else {
                return response(['msg' => 'not found']);
            }
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
    public function delete($id)
    {

        $data = Product::find($id);
        if ($data->delete()) {
            return response(['msg' => 'product delete successfully']);
        }
    }
    public function stock_in(Request $request)
    {

        //  $request->validate([
        //     'product_id' => 'required',
        //     'batch' => 'required',
        //     'qty' => 'required',
        // ]);
        foreach ($request->input('products') as $data) {
            $inventory = new Inventory();
            $inventory->user_id = \Auth::user()->id;
            $inventory->product_id = $data['id'];
            $inventory->batch_id = $data['batch'] ?? NULL;
            $inventory->total_qty  = $data['qty'] ?? 0;
            $inventory->qty = $data['qty'] ?? 0;
            $inventory->mrp = $data['mrp'] ?? 0;
            $inventory->sku_code = $data['sku'];
            $inventory->godowns_id = \Auth::user()->godowns_id;
            $inventory->type = 'stock-in';
            $inventory->product_date = $data['product_date'] ? $data['product_date'] : '';
            $inventory->save();
            // stock in report 
            $report = new StockInReport();
            $report->user_id = \Auth::user()->id;
            $report->product_id = $data['id'];
            $report->batch_id = $data['batch'] ?? NULL;
            $report->total_qty  = $data['qty'] ?? 0;
            $report->qty = $data['qty'] ?? 0;
            $report->mrp = $data['mrp'] ?? 0;
            $report->sku_code = $data['sku'];
            $report->godowns_id = \Auth::user()->godowns_id;
            $report->product_date = $data['product_date'] ? $data['product_date'] : '';
            $report->type = 'stock-in';
            // $report->product_date = $data['product_date'] ? $data['product_date'] :'';
            $report->save();
        }

        return response(['msg' => 'stock-in add successfully']);
    }


    public function stock_in_bulk(Request $request)
    {
        try {
            $bulkStock = $request->input('bulkStock');
            if (!is_array($bulkStock)) {
                return response()->json(['error' => 'Invalid data structure. Expected an array of bulkStock.'], 400);
            }

            foreach ($bulkStock as $data) {
                $inventory = new Inventory();
                $inventory->user_id = \Auth::user()->id;
                // $inventory->product_id = $data['product_id'];
                $inventory->batch_id = $data['batch_id'] ?? NULL;
                $inventory->total_qty  = $data['qty'] ?? 0;;
                $inventory->qty = $data['qty'] ?? 0;
                $inventory->mrp = $data['mrp'] ?? NULL;
                $inventory->sku_code = $data['sku_code'];
                $inventory->godowns_id = \Auth::user()->godowns_id;
                $inventory->type = 'stock-in';
                $inventory->product_date = $request->get('product_date');
                $inventory->save();

                // stock in report 
                $report = new StockInReport();
                $report->user_id = \Auth::user()->id;
                // $inventory->product_id = $data['product_id'];
                $report->batch_id = $data['batch_id'] ?? NULL;
                $report->total_qty  = $data['qty'] ?? 0;;
                $report->qty = $data['qty'] ?? 0;
                $report->mrp = $data['mrp'] ?? NULL;
                $report->sku_code = $data['sku_code'];
                $report->godowns_id = \Auth::user()->godowns_id;
                $report->type = 'stock-in';
                $report->product_date = $request->get('product_date');
                $report->save();
            }

            return response()->json(['msg' => 'Inventory updated successfully'], 201);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage()); // Log the actual error message
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
    public function stock_out(Request $request)
    {
        foreach ($request->input('updateProducts') as $data) {
            $inventory = Inventory::find($data['id']);
            $inventory->qty = $data['qty'] ? $inventory->qty - $data['qty'] : $inventory->qty;
            $inventory->type = 'stock-out';
            if ($inventory->save()) {
                $history =   new StockOutHistory();
                $history->user_id = \Auth::user()->id;
                $history->product_id = $inventory->product_id ?? NULL;
                $history->inventory_id = $data['id'];
                $history->sku_code =  $inventory->sku_code;
                $history->previous_qty = $data['qty'];
                $history->current_qty = $data['qty'] ?  $inventory->qty  : $inventory->qty;
                $history->batch = $inventory->batch_id;
                $history->remarks = $data['remarks'];
                $history->product_date = $data['product_date'];
                $history->save();


                $report =   new StockOutReport();
                $report->stock_report_id = $data['report_id'];
                $report->user_id = \Auth::user()->id;
                $report->product_id = $inventory->product_id ?? NULL;
                $report->inventory_id = $data['id'];
                $report->sku_code =  $inventory->sku_code;
                $report->previous_qty = $data['qty'];
                $report->current_qty = $data['qty'] ?  $inventory->qty  : $inventory->qty;
                $report->batch = $inventory->batch_id;
                $report->remarks = $data['remarks'];
                $report->product_date = $data['product_date'];
                $report->save();
            }
        }
        return response(['msg' => 'stock-update  successfully'], 200);
    }

    public function stock_delete($id)
    {
        $data = Inventory::find($id);
        if ($data->delete()) {
            return response(['msg' => 'stock-delete  successfully'], 200);
        }
    }
    public function add_bluk(Request  $request)
    {
        DB::beginTransaction();
        foreach ($request->input('bulkProduct') as $data) {
            if ($data['sku']) {
                $validator = \Validator::make($request->all(), [
                    // 'bulkProduct' => 'required|array',
                    'bulkProduct.*.sku' => 'unique:products,sku',
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['errors' => $validator->errors() . $data['name']], 422);
                }
            }
        }
        foreach ($request->input('bulkProduct') as $data) {
            $image = NULL;
            $existingSku = NULL;
            do {
                $sku_code = rand(1000000, 9999999); // Generate a random SKU code
                $existingSku = Product::where('sku', $sku_code)->first();
            } while ($existingSku); // If SKU exists, regenerate until it's unique
            if ($request->hasFile('image')) {
                $image = $request->file('image')->store('product', 'public');
            }
            $product = new Product();
            $product->name = $data['name'];
            $product->sku = $data['sku'] ?: $existingSku;
            $product->size = $data['size'];
            $product->color = $data['color'];
            $product->brand = $data['brand'];
            $product->category_id = $data['category_id'];
            $product->sub_category = $data['sub_category'];
            // $product->godowns_id = $data['godowns_id'];
            // $product->product_in_date = $data['product_in_date'];
            $product->thickness = $request->get('thickness');
            $product->mrp = $data['mrp'] ?? 0;
            $product->batch = $data['batch'];
            $product->uom = $data['uom'];
            $product->finish = $data['finish'];
            $product->image = $image;
            $product->save();
        }
        DB::commit();
        return response()->json(['msg' => 'Product created successfully'], 201);
    }
    public function hold_release(Request $request)
    {
        $data = HoldQty::where('id', $request->get('id'))->where('godowns_id', \Auth::user()->godowns_id)->first();
        if ($data) {
            $data->delete();
            return response(['data' => 'product hold  release succesfully'], 200);
        }
        return response(['data' => 'not found'], 400);
    }

    public function product_return(Request $request)
    {
        foreach ($request->input('updateProducts') as $data) {
            $inventory = Inventory::find($data['id']);
            $inventory->qty = $data['qty'] ? $inventory->qty + $data['qty'] : $inventory->qty;

            if ($inventory->save()) {
                $history =   new ReturnProduct();
                $history->inventories_id = $inventory->id;
                $history->sku_code = $inventory->sku_code;
                $history->remarks = $data['remarks'];
                $history->godowns_id = $inventory->godowns_id;
                $history->product_date = $data['product_date'];
                $history->qty = $data['qty'];
                $history->save();
                //   if($history->save()){
                //       $stockout =    StockOutHistory::where('sku_code',$inventory->sku_code)->where('inventory_id',$inventory->id)->where('user_id',\Auth::user()->id)->first();
                //       $stockout->previous_qty = $data['qty'] ?  $stockout->previous_qty -$data['qty'] : $stockout->previous_qty;
                //       $stockout->current_qty = $data['qty'] ?  $stockout->current_qty -$data['qty'] : $stockout->current_qty;
                //       $stockout->save();
                //   }
            }
        }
        return response(['msg' => 'stock-update  successfully'], 200);
    }

    public function product_demage(Request $request)
    {
        DB::beginTransaction();
         try {
        foreach ($request->input('updateProducts') as $data) {
            $inventory = Inventory::find($data['id']);
            if (!$inventory) {
                DB::rollBack();
                return response(['msg' => 'Inventory not found'], 404);
            }
            $inventory->qty =  $inventory->qty - $data['qty'];
            $inventory->save();
          
            $history =   new ProductDemage();
            $history->inventories_id = $inventory->id;
            $history->sku_code = $inventory->sku_code;
            $history->qty = $data['qty'];
            $history->godowns_id = $inventory->godowns_id;
            $history->product_date = $data['product_date'] ? $data['product_date'] : date('YYYY-MM-DD');
            $history->remarks = $data['remarks'] ?? NULL;

            $history->save();
        
        }
          DB::commit();
        return response(['msg' => 'product remove from Inventory '], 200);
         } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Error occurred in product_demage: ' . $e->getMessage());

        return response(['msg' => 'An error occurred while processing the request'], 500);
    }
    }

    // public function product_demage_history()
    // {
    //     $data = ProductDemage::with(['inventory' => function($query) {
    //       $query->withTrashed();  
    //         }])
    //         ->where('godowns_id', \Auth::user()->godowns_id)
    //         ->latest()
    //         ->get();
    //     return response(['data' => $data]);
    // }
    public function product_demage_history(Request $request)
    {
        // $query = ProductDemage::with(['inventory' => function($query) {
        //   $query->withTrashed();  
        //     }])
        //     ->where('godowns_id', \Auth::user()->godowns_id)
        //     ->latest();
      $query = ProductDemage::with(['inventory'])
            ->where('godowns_id', \Auth::user()->godowns_id)
            ->latest();

    if ($request->has('search') && $request->get('search') != '') {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('product_demages.sku_code', 'like', '%' . $search . '%')
              ->orWhere('product_demages.remarks', 'like', '%' . $search . '%')
              ->orWhereHas('inventory.product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }
    
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('inventory.product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    if ($request->has('category') && $request->get('category') != '') {
        $category = $request->get('category');
        $query->whereHas('inventory.product', function ($q) use ($category) {
            $q->where('category_id', $category);
        });
    }

    
    if ($request->has('size') && $request->get('size') != '') {
        $size = $request->get('size');
        $query->whereHas('inventory.product', function ($q) use ($size) {
            $q->where('size', 'like', '%' . $size . '%');
        });
    }

    if ($request->has('finish') && $request->get('finish') != '') {
        $finish = $request->get('finish');
        $query->whereHas('inventory.product', function ($q) use ($finish) {
            $q->where('finish', 'like', '%' . $finish . '%');
        });
    }

    if ($request->has('from') && $request->get('from') != '') {
        $fromDate = $request->get('from');
        $query->whereDate('product_demages.product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('product_demages.product_date', '<=', $toDate);
    }

    $limit = $request->has('limit') ? (int) $request->get('limit') : 10;
    $page = $request->has('page') ? (int) $request->get('page') : 1;
    $offset = ($page - 1) * $limit;
$totalRecords = $query->count();
    $data = $query->skip($offset)->take($limit)->get();

    
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$totalRecords], 200);
    }
    return response()->json(['data' => $data,'total'=>$totalRecords], 200);
        
    }
    // public function product_return_history()
    // {
    //     $data = ReturnProduct::with(['inventory' => function($query) {
    //       $query->withTrashed();  
    //         }])
    //         ->where('godowns_id', \Auth::user()->godowns_id)->latest()->get();
    //     return response(['data' => $data]);
    // }
  public function product_return_history(Request $request)
    {
        //   $query = ReturnProduct::with(['inventory'])
        //     ->where('godowns_id', \Auth::user()->godowns_id)->latest();
        
    $query = ReturnProduct::with(['inventory' => function($query) {
          $query->withTrashed();  
            }])
            ->where('godowns_id', \Auth::user()->godowns_id)->latest();
    
    if ($request->has('search') && $request->get('search') != '') {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('return_products.sku_code', 'like', '%' . $search . '%')
              ->orWhere('return_products.remarks', 'like', '%' . $search . '%')
              ->orWhereHas('inventory.product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('inventory.product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    if ($request->has('category') && $request->get('category') != '') {
        $category = $request->get('category');
        $query->whereHas('inventory.product', function ($q) use ($category) {
            $q->where('category_id', $category);
        });
    }

    // Apply 'size' filter (if provided)
    if ($request->has('size') && $request->get('size') != '') {
        $size = $request->get('size');
        $query->whereHas('inventory.product', function ($q) use ($size) {
            $q->where('size', 'like', '%' . $size . '%');
        });
    }

    // Apply 'finish' filter (if provided)
    if ($request->has('finish') && $request->get('finish') != '') {
        $finish = $request->get('finish');
        $query->whereHas('inventory.product', function ($q) use ($finish) {
            $q->where('finish', 'like', '%' . $finish . '%');
        });
    }

    // Apply 'from' and 'to' date filters (if provided)
    if ($request->has('from') && $request->get('from') != '') {
        $fromDate = $request->get('from');
        $query->whereDate('return_products.product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('return_products.product_date', '<=', $toDate);
    }

    // Pagination (default limit 10 if not provided)
    $limit = $request->has('limit') ? (int) $request->get('limit') : 10;
    $page = $request->has('page') ? (int) $request->get('page') : 1;
    $offset = ($page - 1) * $limit;
    $totalRecords = $query->count();
    
    $data = $query->skip($offset)->take($limit)->get();

    
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$totalRecords], 200);
    }
        return response(['data' => $data,'total'=>$totalRecords],200);
    }
    public function add_bluk_product(Request $request)
    {
        DB::beginTransaction();
        foreach ($request->input('bulkProduct') as $data) {
            if ($data['sku']) {
                $validator = \Validator::make($request->all(), [
                    // 'bulkProduct' => 'required|array',
                    'bulkProduct.*.sku' => 'unique:products,sku',
                ]);
                if ($validator->fails()) {

                    return response()->json(['errors' => $validator->errors() . $data['name']], 422);
                }
            }
            foreach ($request->input('bulkProduct') as $data) {
                try {
                    $imagePath = NULL;
                    $existingSku = NULL;
                    do {
                        $sku_code = rand(1000000, 9999999); // Generate a random SKU code
                        $existingSku = Product::where('sku', $sku_code)->first();
                    } while ($existingSku); // If SKU exists, regenerate until it's unique
                    if ($request->hasFile('image')) {
                        $imagePath = $request->file('image')->store('product', 'public');
                    }

                    $product = new Product();
                    $product->name = $data['name'];
                    $product->sku = $data['sku'] ?: $existingSku;
                    $product->size = $data['size'];
                    $product->color = $data['color'];
                    $product->brand = $data['brand'];
                    $product->category_id = $data['category_id'];
                    $product->sub_category = $data['sub_category'];
                    // $product->godowns_id = $data['godowns_id'];
                    // Uncomment and add these if needed
                    $product->product_in_date = Carbon::now()->format('Y-m-d');
                    // $product->mrp = $data['mrp'];
                    // $product->batch = $data['batch'];
                    $product->thickness = $data['thickness'];
                    $product->uom = $data['uom'];
                    $product->finish = $data['finish'];
                    $product->image = $imagePath;
                    $product->save();
                    DB::commit();
                } catch (\Exception $e) {
                    // Rollback the transaction if something failed
                    DB::rollBack();

                    return response()->json(['error' => 'Transaction failed.'], 500);
                }
            }

            return response()->json(['msg' => 'Products created successfully'], 201);
        }
    }

    public function report()
    {
        $data = StockOutHistory::with('product')->get();
        return response()->json(['data' => $data], 201);
    }
    // public function stock_in_reports()
    // {

    //     $data = StockInReport::with('product')
    //         ->where('user_id', \Auth::user()->id)
    //         ->get();
    //     if ($data) {
    //         return response()->json(['data' => $data], 201);
    //     }
    //     return response()->json(['msg' => 'not found'], 422);
    // }
public function stock_in_reports(Request $request)
    {
    $query = StockInReport::with('product')
        ->where('godowns_id', \Auth::user()->godowns_id);

    if ($request->has('search') && $request->get('search') != '') {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('sku_code', 'like', '%' . $search . '%')
              ->orWhereHas('product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

    // Apply 'brand' filter
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    // Apply 'category' filter
    if ($request->has('category') && $request->get('category') != '') {
        $category = $request->get('category');
        $query->whereHas('product', function ($q) use ($category) {
            $q->where('category_id', $category);
        });
    }

    // Apply 'size' filter
    if ($request->has('size') && $request->get('size') != '') {
        $size = $request->get('size');
        $query->whereHas('product', function ($q) use ($size) {
            $q->where('size', 'like', '%' . $size . '%');
        });
    }

    // Apply 'finish' filter
    if ($request->has('finish') && $request->get('finish') != '') {
        $finish = $request->get('finish');
        $query->whereHas('product', function ($q) use ($finish) {
            $q->where('finish', 'like', '%' . $finish . '%');
        });
    }

    // Apply 'from' and 'to' date filters
    if ($request->has('from') && $request->get('from') != '') {
        $fromDate = $request->get('from');
        $query->whereDate('product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('product_date', '<=', $toDate);
    }

    $limit = $request->has('limit') ? (int) $request->get('limit') : 10; // Default limit to 10
    $page = $request->has('page') ? (int) $request->get('page') : 1; // Default page to 1
$totalRecords = $query->count();

    $offset = ($page - 1) * $limit;
    $data = $query->skip($offset)->take($limit)->get();

    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$totalRecords], 200);
    }
    return response()->json(['msg' => $data], 200);
}
    // public function stock_out_reports()
    // {
    //     $data = StockOutReport::with(['product', 'stockInReports'])
    //         ->where('user_id', \Auth::user()->id)
    //         ->get();
    //     if ($data) {
    //         return response()->json(['data' => $data], 201);
    //     }
    //     return response()->json(['msg' => 'not found'], 422);
    // }
 public function stock_out_reports(Request $request)
{
    $query = StockOutReport::with(['product', 'stockInReports'])
    ->whereHas('stockInReports', function($query) {
        $query->where('godowns_id', \Auth::user()->godowns_id); 
    });
    
    
   if ($request->has('godown') && $request->get('godown') != '') {
        $godown = $request->get('godown');
        $query->whereHas('stockInReports', function ($q) use ($godown) {
            $q->where('godowns_id', $godown);
        });
    }

    // Apply 'search' filter (search in sku_code or product name)
    if ($request->has('search') && $request->get('search') != '') {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('sku_code', 'like', '%' . $search . '%')
              ->orWhereHas('product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

    // Apply 'brand' filter (if provided)
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    // Apply 'category' filter (if provided)
    if ($request->has('category') && $request->get('category') != '') {
        $category = $request->get('category');
        $query->whereHas('product', function ($q) use ($category) {
            $q->where('category_id', $category);
        });
    }

    // Apply 'size' filter (if provided)
    if ($request->has('size') && $request->get('size') != '') {
        $size = $request->get('size');
        $query->whereHas('product', function ($q) use ($size) {
            $q->where('size', 'like', '%' . $size . '%');
        });
    }

    // Apply 'finish' filter (if provided)
    if ($request->has('finish') && $request->get('finish') != '') {
        $finish = $request->get('finish');
        $query->whereHas('product', function ($q) use ($finish) {
            $q->where('finish', 'like', '%' . $finish . '%');
        });
    }

    // Apply 'from' and 'to' date filters (if provided)
    if ($request->has('from') && $request->get('from') != '') {
        $fromDate = $request->get('from');
        $query->whereDate('product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('product_date', '<=', $toDate);
    }

    // Apply pagination (default limit 10 if not provided)
    $limit = $request->has('limit') ? (int) $request->get('limit') : 10;
    $page = $request->has('page') ? (int) $request->get('page') : 1;
    $totalRecords = $query->count();
    $offset = ($page - 1) * $limit;
    $data = $query->skip($offset)->take($limit)->get();

    // Return response
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$totalRecords], 200);
    }
    return response()->json(['msg' => $data], 200);
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
        $limit = $request->get('limit', 10);


        $filteredCount = $query->count();

        $data = $query->skip(($page - 1) * $limit)->take($limit)->latest()->get();

        return response()->json([
            'data' => $data,
            'count' => $filteredCount,
        ], 200);
    }


    public function inventory(Request $request)
    {
        try {
            $query = Inventory::with(['product.category', 'product.subcategory'])
                ->where('user_id', \Auth::user()->id);

            if ($request->has('category') && $request->input('category') !== null) {
                $categoryName = $request->get('category');
                $query->whereHas('product', function ($q) use ($categoryName) {
                    $q->where('category_id', $categoryName);
                });
            }

            if ($request->has('search') && $request->input('search') !== null) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('sku_code', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->has('brand') && $request->input('brand') !== null) {
                $brand = $request->input('brand');
                $query->whereHas('product', function ($q) use ($brand) {
                    $q->where('brand', $brand);
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

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $filteredCount = $query->count();

            if (!$request->has('category') && !$request->get('page') && !$request->get('limit')) {
                $data = $query->paginate(10);
            } else {
                $data = $query->skip(($page - 1) * $limit)->take($limit)->latest()->get();
            }

            return response()->json([
                'data' => $data,
                'count' => $filteredCount,
            ]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getLine()]);
        }
    }

    // public function transaction()
    // {
    //     $inventories = DB::table('inventories')
    //         ->select('id',  'created_at')
    //         ->orderBy('created_at');


    //     $stockOutHistories = DB::table('stock_out_histories')
    //         ->select('id', 'created_at')
    //         ->orderBy('created_at');


    //     $combinedQuery = $inventories->union($stockOutHistories);


    //     $data = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
    //         ->mergeBindings($inventories)
    //         ->orderBy('created_at')
    //         ->get();

    //     return response()->json(['data' => $data]);
    // }
// public function transaction(Request $request)
//   {
//         try {
//             $todate = $request->get('to');
//             $formdate = $request->get('from');
//             $search = $request->get('search');
//             $page = $request->get('page', 1);
//             $limit = $request->get('limit', 10);

//             $offset = ($page - 1) * $limit;

//             // $applyFilters = function ($query) use ($todate, $formdate, $search) {
//             //     if ($todate && $formdate) {
//             //         $query->whereBetween('product_date', [$formdate, $todate]);
//             //     }
//             //     if (!empty($search)) {
//             //         $q->where('products.sku', 'like', '%' . $search . '%')
//             //   ->orWhere('products.category_id', 'like', '%' . $search . '%')
//             //   ->orWhere('products.brand', 'like', '%' . $search . '%')
//             //   ->orWhere('stock_in_reports.godowns_id', 'like', '%' . $search . '%')
//             //   ->orWhere('return_products.godowns_id', 'like', '%' . $search . '%')
//             //   ->orWhere('product_demages.godowns_id', 'like', '%' . $search . '%');
//             //     }
//             // };

//             // Stock In
//             $stockIn = \DB::table('stock_in_reports')
//                 ->join('products', 'products.sku', '=', 'stock_in_reports.sku_code')
//                 ->join('godowns', 'stock_in_reports.godowns_id', '=', 'godowns.id')
//                 ->leftJoin('brands', 'products.brand', '=', 'brands.id')
//                 ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
//                 ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
//               ->where('stock_in_reports.godowns_id',\Auth::user()->godowns_id)
//                 ->selectRaw("
//                 stock_in_reports.id,
//                 godowns.name as godown,
//                 products.sku,
//                 products.name,
//                 products.category_id,
//                 products.brand as brand_id,
//                 brands.name as brand,
//                 categories.name as category,
//                 subcategories.name as subcategory,
//                 products.size,
//                 products.color,
//                 products.finish,
//                 products.thickness,
//                 stock_in_reports.mrp,
//                  stock_in_reports.godowns_id as godowns_id,
//                 stock_in_reports.product_date as date,
//                 stock_in_reports.total_qty as stockInQty,
//                 0 as stockOutQty,
//                 0 as returnQty,
//                 0 as damageQty,
//                 products.uom,
//                 stock_in_reports.batch_id as batch,
//                 'StockIn' as type
//             ")
//             ->offset($offset)->limit($limit);

//             // $applyFilters($stockIn);

//             // Stock Out
//             $stockOut = \DB::table('stock_out_reports')
//                 ->join('products', 'products.sku', '=', 'stock_out_reports.sku_code')
//                 ->join('inventories', 'inventories.id', '=', 'stock_out_reports.inventory_id')
//                 ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
//                 ->leftJoin('brands', 'products.brand', '=', 'brands.id')
//                 ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
//                 ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
//                 ->where('inventories.godowns_id',\Auth::user()->godowns_id)
//                 ->selectRaw("
//                 stock_out_reports.id,
//                 godowns.name as godown,
//                 products.sku,
//                 products.name,
//                 products.category_id,
//                 products.brand as brand_id,
//                 brands.name as brand,
//                 categories.name as category,
//                 subcategories.name as subcategory,
//                 products.size,
//                 products.color,
//                 products.finish,
//                 products.thickness,
//                 inventories.mrp,
//                 inventories.godowns_id as  godowns_id,
//                 stock_out_reports.product_date as date,
//                 0 as stockInQty,
//                 stock_out_reports.previous_qty as stockOutQty,
//                 0 as returnQty,
//                 0 as damageQty,
//                 products.uom,
//                 stock_out_reports.batch as batch,
//                 'StockOut' as type
//             ")->offset($offset)->limit($limit);

//             // $applyFilters($stockOut);

//             // Returns
//             $returns = \DB::table('return_products')
//                 ->join('inventories', 'inventories.id', '=', 'return_products.inventories_id')
//                 ->join('products', 'products.sku', '=', 'inventories.sku_code')
//                 ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
//                 ->leftJoin('brands', 'products.brand', '=', 'brands.id')
//                 ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
//                 ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
//               ->where('inventories.godowns_id',\Auth::user()->godowns_id)
//                 ->selectRaw("
//                 return_products.id,
//                 godowns.name as godown,
//                 products.sku,
//                 products.name,
//                 products.category_id,
//                 products.brand as brand_id,
//                 brands.name as brand,
//                 categories.name as category,
//                 subcategories.name as subcategory,
//                 products.size,
//                 products.color,
//                 products.finish,
//                 products.thickness,
//                 inventories.mrp,
//                 inventories.godowns_id as godowns_id, 
//                 return_products.product_date as date,
//                 0 as stockInQty,
//                 0 as stockOutQty,
//                 return_products.qty as returnQty,
//                 0 as damageQty,
//                 products.uom,
//                 inventories.batch_id as batch,
//                 'Return' as type
//             ")
//             ->offset($offset)->limit($limit);

//             // $applyFilters($returns);

//             // Damages
//             $damages = \DB::table('product_demages')
//                 ->join('inventories', 'inventories.id', '=', 'product_demages.inventories_id')
//                 ->join('products', 'products.sku', '=', 'inventories.sku_code')
//                 ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
//                 ->leftJoin('brands', 'products.brand', '=', 'brands.id')
//                 ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
//                 ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
//               ->where('inventories.godowns_id',\Auth::user()->godowns_id)
//                 ->selectRaw("
//                 product_demages.id,
//                 godowns.name as godown,
//                 products.sku,
//                 products.name,
//                 products.category_id,
//                 products.brand as brand_id,
//                 brands.name as brand,
//                 categories.name as category,
//                 subcategories.name as subcategory,
//                 products.size,
//                 products.color,
//                 products.finish,
//                 products.thickness,
//                 inventories.mrp,
//                  inventories.godowns_id as godowns_id, 
//                 product_demages.product_date as date,
//                 0 as stockInQty,
//                 0 as stockOutQty,
//                 0 as returnQty,
//                 product_demages.qty as damageQty,
//                 products.uom,
//                 inventories.batch_id as batch,
//                 'Damage' as type
//             ")
//             ->offset($offset)->limit($limit);
//              $unionQuery = $stockIn
//                 ->unionAll($stockOut)
//                 ->unionAll($returns)
//                 ->unionAll($damages);
//             $query  = \DB::table(\DB::raw("({$unionQuery->toSql()}) as combined"))
//                 ->mergeBindings($stockIn);
//             if (isset($formdate) && isset($todate)) {
//                 $query->whereBetween('date', [$formdate, $todate]);
//             }
//             $results = $query->get();

//             $total = \DB::table(\DB::raw("({$unionQuery->toSql()}) as total_count"))
//                 ->mergeBindings($stockIn)
//                 ->count();
// $total = \DB::table('stock_in_reports') ->where('stock_in_reports.godowns_id',\Auth::user()->godowns_id)->count();
// $total += \DB::table('stock_out_reports')->join('inventories', 'inventories.id', '=', 'stock_out_reports.inventory_id')->where('inventories.godowns_id',\Auth::user()->godowns_id)->count();
// $total +=   $returns = \DB::table('return_products')->join('inventories', 'inventories.id', '=', 'return_products.inventories_id')->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')->where('inventories.godowns_id',\Auth::user()->godowns_id)->count();
// $total += \DB::table('product_demages')
//                 ->join('inventories', 'inventories.id', '=', 'product_demages.inventories_id')->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')->where('inventories.godowns_id',\Auth::user()->godowns_id)->count();           
//             return response()->json([
//                 'data' => $results,
//                 'count' => $total
//             ]);
//         } catch (\Exception $ex) {
//             return response()->json(['error' => $ex->getMessage()], 500);
//         }
//     }
public function transaction(Request $request) {
 try {
    $todate = $request->get('to');
    $formdate = $request->get('from');
    $search = $request->get('search');
    $page = $request->get('page', 1);
    $limit = $request->get('limit', 10);
    $offset = ($page - 1) * $limit;

    // Stock In
    $stockIn = \DB::table('stock_in_reports')
        ->join('products', 'products.sku', '=', 'stock_in_reports.sku_code')
        ->join('godowns', 'stock_in_reports.godowns_id', '=', 'godowns.id')
        ->leftJoin('brands', 'products.brand', '=', 'brands.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
        ->where('stock_in_reports.godowns_id', \Auth::user()->godowns_id)
        ->selectRaw("
            stock_in_reports.id,
            godowns.name as godown,
            products.sku,
            products.name,
            products.category_id,
            products.brand as brand_id,
            brands.name as brand,
            categories.name as category,
            subcategories.name as subcategory,
            products.size,
            products.color,
            products.finish,
            products.thickness,
            stock_in_reports.mrp,
            stock_in_reports.godowns_id as godowns_id,
            stock_in_reports.product_date as date,
            stock_in_reports.total_qty as stockInQty,
            0 as stockOutQty,
            0 as returnQty,
            0 as damageQty,
            products.uom,
            stock_in_reports.batch_id as batch,
            'StockIn' as type
        ");

    // Apply filters to stockIn
    if ($formdate && $todate) {
        $stockIn->whereBetween('stock_in_reports.product_date', [$formdate, $todate]);
    }
    if ($search) {
        $stockIn->where(function ($query) use ($search) {
            $query->where('products.sku', 'like', '%' . $search . '%')
                  ->orWhere('products.name', 'like', '%' . $search . '%')
                  ->orWhere('brands.name', 'like', '%' . $search . '%')
                  ->orWhere('categories.name', 'like', '%' . $search . '%');
        });
    }
    // Apply brand filter directly to stockIn query if provided
    if ($request->has('brand') && $request->get('brand') != NULL) {
        $stockIn->where('products.brand', $request->get('brand'));
    }
    if ($request->has('category') && $request->get('category') != NULL) {
        $stockIn->where('products.category_id', $request->get('category'));
    }

if ($request->has('finish') && $request->get('finish') != NULL) { 
    $stockIn->where('finish', $request->get('finish'));
    } 
    if ($request->has('godown') && $request->get('godown') != NULL)
    { 
        $stockIn->where('godowns_id', $request->get('godown')); 
        
    }
    if ($request->has('size') && $request->get('size') != NULL)
    { 
        $stockIn->where('size', $request->get('size')); 
        
    }
    // Stock Out
    $stockOut = \DB::table('stock_out_reports')
        ->join('products', 'products.sku', '=', 'stock_out_reports.sku_code')
        ->join('inventories', 'inventories.id', '=', 'stock_out_reports.inventory_id')
        ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
        ->leftJoin('brands', 'products.brand', '=', 'brands.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
        ->where('inventories.godowns_id', \Auth::user()->godowns_id)
        ->selectRaw("
            stock_out_reports.id,
            godowns.name as godown,
            products.sku,
            products.name,
            products.category_id,
            products.brand as brand_id,
            brands.name as brand,
            categories.name as category,
            subcategories.name as subcategory,
            products.size,
            products.color,
            products.finish,
            products.thickness,
            inventories.mrp,
            inventories.godowns_id as godowns_id,
            stock_out_reports.product_date as date,
            0 as stockInQty,
            stock_out_reports.previous_qty as stockOutQty,
            0 as returnQty,
            0 as damageQty,
            products.uom,
            stock_out_reports.batch as batch,
            'StockOut' as type
        ");

    // Apply filters to stockOut
    if ($formdate && $todate) {
        $stockOut->whereBetween('stock_out_reports.product_date', [$formdate, $todate]);
    }
    if ($search) {
        $stockOut->where(function ($query) use ($search) {
            $query->where('products.sku', 'like', '%' . $search . '%')
                  ->orWhere('products.name', 'like', '%' . $search . '%')
                  ->orWhere('brands.name', 'like', '%' . $search . '%')
                  ->orWhere('categories.name', 'like', '%' . $search . '%');
        });
    }
    // Apply brand filter directly to stockOut query if provided
    if ($request->has('brand') && $request->get('brand') != NULL) {
        $stockOut->where('products.brand', $request->get('brand'));
    }
if ($request->has('category') && $request->get('category') != NULL) {
        $stockOut->where('products.category_id', $request->get('category'));
    }
    if ($request->has('finish') && $request->get('finish') != NULL) { 
    $stockOut->where('finish', $request->get('finish'));
    } 
    if ($request->has('godown') && $request->get('godown') != NULL)
    { 
        $stockOut->where('godowns_id', $request->get('godown')); 
        
    }
    if ($request->has('size') && $request->get('size') != NULL)
    { 
        $stockOut->where('size', $request->get('size')); 
        
    }
    // Returns
    $returns = \DB::table('return_products')
        ->join('inventories', 'inventories.id', '=', 'return_products.inventories_id')
        ->join('products', 'products.sku', '=', 'inventories.sku_code')
        ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
        ->leftJoin('brands', 'products.brand', '=', 'brands.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
        ->where('inventories.godowns_id', \Auth::user()->godowns_id)
        ->selectRaw("
            return_products.id,
            godowns.name as godown,
            products.sku,
            products.name,
            products.category_id,
            products.brand as brand_id,
            brands.name as brand,
            categories.name as category,
            subcategories.name as subcategory,
            products.size,
            products.color,
            products.finish,
            products.thickness,
            inventories.mrp,
            inventories.godowns_id as godowns_id,
            return_products.product_date as date,
            0 as stockInQty,
            0 as stockOutQty,
            return_products.qty as returnQty,
            0 as damageQty,
            products.uom,
            inventories.batch_id as batch,
            'Return' as type
        ");

    // Apply filters to returns
    if ($formdate && $todate) {
        $returns->whereBetween('return_products.product_date', [$formdate, $todate]);
    }
    if ($search) {
        $returns->where(function ($query) use ($search) {
            $query->where('products.sku', 'like', '%' . $search . '%')
                  ->orWhere('products.name', 'like', '%' . $search . '%')
                  ->orWhere('brands.name', 'like', '%' . $search . '%')
                  ->orWhere('categories.name', 'like', '%' . $search . '%');
        });
    }
    // Apply brand filter directly to returns query if provided
    if ($request->has('brand') && $request->get('brand') != NULL) {
        $returns->where('products.brand', $request->get('brand'));
    }
if ($request->has('category') && $request->get('category') != NULL) {
        $returns->where('products.category_id', $request->get('category'));
    }
    if ($request->has('finish') && $request->get('finish') != NULL) { 
    $returns->where('finish', $request->get('finish'));
    } 
    if ($request->has('godown') && $request->get('godown') != NULL)
    { 
        $returns->where('godowns_id', $request->get('godown')); 
        
    }
    if ($request->has('size') && $request->get('size') != NULL)
    { 
        $returns->where('size', $request->get('size')); 
        
    }
    // Damages
    $damages = \DB::table('product_demages')
        ->join('inventories', 'inventories.id', '=', 'product_demages.inventories_id')
        ->join('products', 'products.sku', '=', 'inventories.sku_code')
        ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
        ->leftJoin('brands', 'products.brand', '=', 'brands.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
        ->where('inventories.godowns_id', \Auth::user()->godowns_id)
        ->selectRaw("
            product_demages.id,
            godowns.name as godown,
            products.sku,
            products.name,
            products.category_id,
            products.brand as brand_id,
            brands.name as brand,
            categories.name as category,
            subcategories.name as subcategory,
            products.size,
            products.color,
            products.finish,
            products.thickness,
            inventories.mrp,
            inventories.godowns_id as godowns_id,
            product_demages.product_date as date,
            0 as stockInQty,
            0 as stockOutQty,
            0 as returnQty,
            product_demages.qty as damageQty,
            products.uom,
            inventories.batch_id as batch,
            'Damage' as type
        ");

    // Apply filters to damages
    if ($formdate && $todate) {
        $damages->whereBetween('product_demages.product_date', [$formdate, $todate]);
    }
    if ($search) {
        $damages->where(function ($query) use ($search) {
            $query->where('products.sku', 'like', '%' . $search . '%')
                  ->orWhere('products.name', 'like', '%' . $search . '%')
                  ->orWhere('brands.name', 'like', '%' . $search . '%')
                  ->orWhere('categories.name', 'like', '%' . $search . '%');
        });
    }
    // Apply brand filter directly to damages query if provided
    if ($request->has('brand') && $request->get('brand') != NULL) {
        $damages->where('products.brand', $request->get('brand'));
    }
if ($request->has('category') && $request->get('category') != NULL) {
        $damages->where('products.category_id', $request->get('category'));
    }
    if ($request->has('finish') && $request->get('finish') != NULL) { 
    $damages->where('finish', $request->get('finish'));
    } 
    if ($request->has('godown') && $request->get('godown') != NULL)
    { 
        $damages->where('godowns_id', $request->get('godown')); 
        
    }
    if ($request->has('size') && $request->get('size') != NULL)
    { 
        $damages->where('size', $request->get('size')); 
        
    }
    // Combine all queries using UNION
    $unionQuery = $stockIn
        ->unionAll($stockOut)
        ->unionAll($returns)
        ->unionAll($damages);

    // Final query with pagination
    $query = \DB::table(\DB::raw("({$unionQuery->toSql()}) as combined"))
        ->mergeBindings($stockIn)
        ->offset($offset)
        ->limit($limit);

    // Fetch results
    $results = $query->get();

    // Count total records (for pagination)
    $total = \DB::table(\DB::raw("({$unionQuery->toSql()}) as total_count"))
        ->mergeBindings($stockIn)
        ->count();

    return response()->json([
        'data' => $results,
        'count' => $total
    ]);
} catch (\Exception $ex) {
    return response()->json(['error' => $ex->getMessage()], 500);
}

}
    public function stock_in_recent($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $data = Inventory::with('product')->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('total_qty', 'desc')
            ->where('user_id', \Auth::user()->id)
            ->latest()
            ->take(10)
            ->get();

        return response(['data' => $data]);
    }

    public function stock_out_recent($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $data = StockOutReport::with('product')->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)

            ->orderBy('previous_qty', 'desc')
            ->where('user_id', \Auth::user()->id)
            ->latest()
            ->take(10)
            ->get();
        return response(['data' => $data]);
    }
    public function stock_in_brand($date)
    {
        
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        try {
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);

            $data = Inventory::with(['product.category', 'product.subcategory', 'product.brand', 'godownsName'])
                ->whereMonth('created_at', $month)
                ->when(request()->has('brand'), function ($query) {
                    $brand = request()->input('brand');
                    $query->whereHas('product', function ($q) use ($brand) {
                        $q->where('brand', $brand);
                    });
                })
                ->orderBy('total_qty', 'desc')
                ->where('user_id', \Auth::user()->id)
                ->take(10)
                ->paginate(10);

            return response(['data' => $data]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }

    public function stock_out_brand($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $data = StockOutHistory::with(['product.category', 'product.subcategory', 'product.brand'])
            ->whereMonth('created_at', $month)
            ->when(request()->has('brand'), function ($query) {
                $brand = request()->input('brand');
                $query->whereHas('product', function ($q) use ($brand) {
                    $q->where('brand', $brand);
                });
            })
            ->orderBy('previous_qty', 'desc')
            ->latest()
            ->take(10)
            ->paginate(10);

        return response(['data' => $data]);
    }
    public function product_categorys($date)
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $data = StockInReport::with('product.category')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('total_qty', 'desc')
            ->where('user_id', \Auth::user()->id)
            ->take(10)
            ->get();


        return response(['data' => $data]);
    }
     public function total_sku(Request $request)
{
        $result = [];
        $fromDate = $request->filled('from') ? \Carbon\Carbon::parse($request->get('from'))->startOfMonth() : null;
        $toDate = $request->filled('to') ? \Carbon\Carbon::parse($request->get('to'))->endOfMonth() : null;

        $months = [];
        if ($fromDate && $toDate) {
            $current = $fromDate->copy();
            while ($current <= $toDate) {
                $months[] = $current->format('m-Y');
                $current->addMonth();
            }
        } else {
            $now = now();
            for ($i = 0; $i < 12; $i++) {
                $months[] = $now->copy()->subMonths($i)->format('m-Y');
            }
        }

        foreach ($months as $monthKey) {
            $month = \Carbon\Carbon::createFromFormat('m-Y', $monthKey);

            // Stock In
            $stockIn = DB::table('stock_in_reports')
                ->select('sku_code', 'godowns_id', 'product_date', DB::raw('SUM(total_qty) as total_qty'))
                
                ->whereMonth('product_date', $month->format('m'))
                ->whereYear('product_date', $month->format('Y'))
                ->where('stock_in_reports.godowns_id', \Auth::user()->godowns_id)
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('stock_in_reports.product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('stock_in_reports.product_date', '<=', $toDate);
                    }
                })
                ->groupBy('sku_code', 'godowns_id', 'product_date')
                ->get()
                ->groupBy('sku_code');

            // Stock Out
            $stockOut = DB::table('stock_out_reports')
                ->select('stock_out_reports.sku_code', 'stock_out_reports.product_date', DB::raw('SUM(previous_qty) as total_qty'))
                ->join('stock_in_reports','stock_out_reports.stock_report_id','stock_in_reports.id')
                ->whereMonth('stock_out_reports.product_date', $month->format('m'))
                ->whereYear('stock_out_reports.product_date', $month->format('Y'))
                ->where('stock_in_reports.godowns_id', \Auth::user()->godowns_id)
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('stock_out_reports.product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('stock_out_reports.product_date', '<=', $toDate);
                    }
                })
                ->groupBy('sku_code', 'product_date')
                ->get()
                ->groupBy('sku_code');

            // Returns
            $returns = DB::table('return_products')
                ->select('sku_code', 'godowns_id', 'product_date', DB::raw('SUM(qty) as total_qty'))
                ->whereMonth('product_date', $month->format('m'))
                ->whereYear('product_date', $month->format('Y'))
                ->where('return_products.godowns_id', \Auth::user()->godowns_id)
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('return_products.product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('return_products.product_date', '<=', $toDate);
                    }
                })
                ->groupBy('sku_code', 'godowns_id', 'product_date')
                ->get()
                ->groupBy('sku_code');

            // Damages
            $damages = DB::table('product_demages')
                ->select('sku_code', 'godowns_id', 'product_date', DB::raw('SUM(qty) as total_qty'))
                ->whereMonth('product_date', $month->format('m'))
                ->whereYear('product_date', $month->format('Y'))
                ->where('product_demages.godowns_id', \Auth::user()->godowns_id)
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('product_demages.product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('product_demages.product_date', '<=', $toDate);
                    }
                })
                ->groupBy('sku_code', 'godowns_id', 'product_date')
                ->get()
                ->groupBy('sku_code');

            $allSkuCodes = collect($stockIn->keys())
                ->merge($stockOut->keys())
                ->merge($returns->keys())
                ->merge($damages->keys())
                ->unique();

            foreach ($allSkuCodes as $sku) {
                $productQuery = Product::select('products.*', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
                    ->leftJoin('categories', 'products.category_id', 'categories.id')
                    ->leftJoin('subcategories', 'products.sub_category', 'subcategories.id')
                    ->leftJoin('brands', 'products.brand', 'brands.id')
                    ->where('sku', $sku)
                    ->where('products.is_active', 1);

               if ($request->has('search') && $request->get('search') != 'null') {
                $searchTerm = $request->get('search');
                $productQuery->where(function ($query) use ($searchTerm) {
                    $query->where('sku', 'like', '%' . $searchTerm . '%')
                        ->orWhere('categories.name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('products.name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('brands.name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('subcategories.name', 'like', '%' . $searchTerm . '%');
                });
}

                if ($request->filled('brand')) {
                    $productQuery->where('brand', $request->get('brand'));
                }
                if ($request->filled('category')) {
                    $productQuery->where('products.category_id', $request->get('category'));
                }
                if ($request->filled('size')) {
                    $productQuery->where('size', $request->get('size'));
                }
                if ($request->filled('finish')) {
                    $productQuery->where('finish', $request->get('finish'));
                }
                // Add more if needed

                $product = $productQuery->first();

                // Skip iteration if product doesn't match filter
                if (!$product) {
                    continue;
                }
                // Initialize quantities and godown names
                $stockInQty = 0;
                $stockInGodowns = [];
                $stockOutQty = 0;
                $stockOutGodowns = [];
                $returnQty = 0;
                $returnGodowns = [];
                $damageQty = 0;
                $damageGodowns = [];

                // Stock In processing
                if (isset($stockIn[$sku])) {
                    foreach ($stockIn[$sku] as $entry) {
                        $stockInQty += $entry->total_qty;
                        if ($entry->godowns_id) {
                            $name = DB::table('godowns')->where('id', $entry->godowns_id)->value('name');
                            if ($name && !in_array($name, $stockInGodowns)) {
                                $stockInGodowns[] = $name;
                            }
                        }
                    }
                }

                // Stock Out processing
                if (isset($stockOut[$sku])) {
                    foreach ($stockOut[$sku] as $entry) {
                        $stockOutQty += $entry->total_qty;
                        if (isset($entry->godowns_id)) {
                            $name = DB::table('godowns')->where('id', $entry->godowns_id)->value('name');
                            if ($name && !in_array($name, $stockOutGodowns)) {
                                $stockOutGodowns[] = $name;
                            }
                        }
                    }
                }

                // Return processing
                if (isset($returns[$sku])) {
                    foreach ($returns[$sku] as $entry) {
                        $returnQty += $entry->total_qty;
                        if ($entry->godowns_id) {
                            $name = DB::table('godowns')->where('id', $entry->godowns_id)->value('name');
                            if ($name && !in_array($name, $returnGodowns)) {
                                $returnGodowns[] = $name;
                            }
                        }
                    }
                }

                // Damage processing
                if (isset($damages[$sku])) {
                    foreach ($damages[$sku] as $entry) {
                        $damageQty += $entry->total_qty;
                        if ($entry->godowns_id) {
                            $name = DB::table('godowns')->where('id', $entry->godowns_id)->value('name');
                            if ($name && !in_array($name, $damageGodowns)) {
                                $damageGodowns[] = $name;
                            }
                        }
                    }
                }

                $result[] = [
                    'sku_code' => $sku,
                    'stockIn' => $stockInQty,
                    'stockInGodowns' => $stockInGodowns,
                    'stockOut' => $stockOutQty,
                    'stockOutGodowns' => $stockOutGodowns,
                    'return' => $returnQty,
                    'returnGodowns' => $returnGodowns,
                    'damage' => $damageQty,
                    'damageGodowns' => $damageGodowns,
                    'month' => $monthKey,
                    'product' => $product,
                ];
            }
        }

        $search = strtolower($request->get('search', ''));
        if (!empty($search)) {
            $result = array_filter($result, function ($item) use ($search) {
                $product = $item['product'];
                return strpos(strtolower($item['sku_code']), $search) !== false ||
                    (isset($product->name) && strpos(strtolower($product->name), $search) !== false) ||
                    (isset($product->categoriename) && strpos(strtolower($product->categoriename), $search) !== false) ||
                    (isset($product->subcategoriesname) && strpos(strtolower($product->subcategoriesname), $search) !== false) ||
                    (isset($product->brandname) && strpos(strtolower($product->brandname), $search) !== false);
            });
        }

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('limit', 10);
        $offset = ($page - 1) * $perPage;

        $pagedData = array_slice($result, $offset, $perPage);

        return response()->json([
            'data' => array_values($pagedData),
            'count' => count($result),
        ]);
    }
     public function hold_product(){
    
        $data  = HoldQty::select('id','hold_qty','holdername','user_id','godowns_id','inventories_id','created_at','remarks')->with(['user','godowns','inventory'])->where('godowns_id',\Auth::user()->godowns_id)->get();
        return response()->json(['data'=>$data]);
    }
      public function dashboard(Request $request)
    {
        $data =[];
        $data['product'] = Product::count();
        $data['dis_continue_product'] = Product::where('dis_continue',0)->count();
        $data['inventory'] = Inventory::count();
        $data['holdQty'] = HoldQty::count();
        $data['brand'] =Brand::count();
        $data['category'] =Category::count();
        
        return response()->json(['data'=>$data]);
    }
}
