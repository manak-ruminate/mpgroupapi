<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockOutHistory;
use App\Models\ReturnProduct;
use App\Models\ProductDemage;
use App\Models\HoldQty;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\StockInReport;
use App\Models\StockOutReport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User; 
use App\Models\Godowns;
class ProductController extends Controller
{

    public function __construct()
    {
        if (\Auth::user()->role_id == 1) {
            return true;
        } else {
            return response(['error' => 'unauthozied']);
        }
    }
  public function index(Request $request)
    {
        $search = $request->get('search');
        // $data = Product::select('products.*', 'godowns.name as godownsname', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
          $query =   Product::select('products.*', 'godowns.name as godownsname', 'categories.name as categoriename', 'brands.name as brandname', 'subcategories.name as subcategoriesname')
            ->leftjoin('godowns', 'products.godowns_id', 'godowns.id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->leftjoin('subcategories', 'products.sub_category', 'subcategories.id')
            ->leftjoin('brands', 'products.brand', 'brands.id')
            ->where('products.is_active', 1)
            ->with('stockInReports')
            ->latest();
            // ->get();
        if (isset($search) && $search != '') {
    $query->where(function($q) use ($search) {
        $q->where('products.name', 'like', '%' . $search . '%')
          ->orWhere('products.sku', 'like', '%' . $search . '%');
    });
}
        $data = $query->get();
         $skuCodes = $data->pluck('sku')->toArray();

        $inventories = Inventory::whereIn('sku_code', $skuCodes)
            ->with('godownsName')
            ->with(['stockout', 'holdqty'])
            ->latest()
            ->get()
            ->groupBy('sku_code'); // Group inventories by sku_code

        foreach ($data as $datas) {
            $datas->inventories = isset($inventories[$datas->sku]) ? $inventories[$datas->sku] : [];
        }

        return response(['data' => $data]);
        
    }

    public function recent()
    {
        $data = Inventory::latest()->paginate(10);
        return response(['data' => $data]);
    }
    public function add(Request $request)
    {
        try {
            $sku_code = NULL;
            if ($request->get('sku')) {
                $validator = \Validator::make($request->all(), [
                    'sku' => 'required|unique:products,sku',
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
            $image = NULL;
            $image2 = NULL;
            $image3 = NULL;
            $image4 = NULL;
            if ($request->hasFile('image')) {
                $image = $request->file('image')->store('product', 'public');
            }
            if ($request->hasFile('image2')) {
                $image2 = $request->file('image2')->store('product', 'public');
            }
            if ($request->hasFile('image3')) {
                $image3 = $request->file('image3')->store('product', 'public');
            }
            if ($request->hasFile('image4')) {
                $image4 = $request->file('image4')->store('product', 'public');
            }

            $product = new Product();
            $product->name = $request->get('name');
            $product->sku = $request->get('sku') ?: $sku_code;
            $product->size = $request->get('size');
            $product->thickness = $request->get('thickness');
            $product->color = $request->get('color');
            $product->brand = $request->get('brand');
            $product->category_id = $request->get('category_id');
            $product->sub_category = $request->get('sub_category');
            // $product->godowns_id = $data['godowns_id'];
            $product->product_in_date = $request->get('product_in_date');
            $product->uom = $request->get('uom');
            $product->finish = $request->get('finish');
            //$data->description = $request->get('description')??NULL;
            $product->image = $image;
            $product->image2 = $image2;
            $product->image3 = $image3;
            $product->image4 = $image4;
            $product->save();
            // }

            return response()->json(['msg' => 'Product created successfully'], 201);
        } catch (\Exception $ex) {
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
            $data->is_web = $request->get('is_web');
            $imagePath = $data ? $data->image : '';
            $imagePath2 = $data ? $data->image2 : '';
            $imagePath3 = $data ? $data->image3 : '';
            $imagePath4 = $data ? $data->image4 : '';
            if ($data) {
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('product', 'public');
                }
                if ($request->hasFile('image2')) {
                    $imagePath2 = $request->file('image2')->store('product', 'public');
                }
                if ($request->hasFile('image3')) {
                    $imagePath3 = $request->file('image3')->store('product', 'public');
                }
                if ($request->hasFile('image4')) {
                    $imagePath4 = $request->file('image4')->store('product', 'public');
                }
                $data->name = $request->get('name') ? $data->name = $request->get('name') : $data->name;
                $data->brand = $request->get('brand') ? $request->get('brand') : $data->brand;
                $data->size = $request->get('size') ? $request->get('size') : $data->size;
                $data->uom = $request->get('uom') ? $request->get('uom') : $data->uom;
                $data->category_id = $request->get('category_id') ? $request->get('category_id') : $data->category_id;
                $data->sub_category =  $request->get('sub_category') ?  $request->get('sub_category') : $data->sub_category;
                $data->image = $imagePath ? $imagePath : $data->image;
                $data->image2 = $imagePath2 ? $imagePath2 : $data->image2;
                $data->image3 = $imagePath3 ? $imagePath3 : $data->image3;
                $data->image4 = $imagePath4 ? $imagePath4 : $data->image4;
                $data->color = $request->get('color') ? $request->get('color') : $data->color;
                $data->dis_continue =  $request->get('dis_continue') ? $request->get('dis_continue') :$data->dis_continue;
                $data->is_active = $request->get('is_active') ?  $request->get('is_active') : $data->is_active;
                
                $data->finish = $request->get('finish');
                $data->product_in_date = $request->get('product_in_date') ? $request->get('product_in_date') : $data->product_in_date;
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


        return response()->json(['msg' => 'Products updated successfully']);
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
            // $inventory->product_id = $data['product_id'];
            $inventory->batch_id = $data['batch'] ?? NULL;
            // $product->thickness = $data['thickness']?? NULL;
            $inventory->total_qty  = $data['qty'] ?? 0;
            $inventory->qty = $data['qty'] ?? 0;
            $inventory->mrp = $data['mrp'] ?? 0;
            $inventory->sku_code = $data['sku'];
            $inventory->godowns_id = $data['godowns_id'];
            $inventory->type = 'stock-in';
            $inventory->product_date = $data['product_date'];
            $inventory->save();
            // reports 
            $report = new StockInReport();
            $report->user_id = \Auth::user()->id;
            // $inventory->product_id = $data['product_id'];
            $report->batch_id = $data['batch'] ?? NULL;
            // $product->thickness = $data['thickness']?? NULL;
            $report->total_qty  = $data['qty'] ?? 0;
            $report->qty = $data['qty'] ?? 0;
            $report->mrp = $data['mrp'] ?? 0;
            $report->sku_code = $data['sku'];
            $report->godowns_id = $data['godowns_id'];
            $report->type = 'stock-in';
            $report->product_date = $data['product_date'];
            $report->save();
        }
        return response(['msg' => 'stock-in add successfully']);
    }

    public function stock_in_update(Request $request)
    {
        //  $request->validate([
        //     'id' => 'required',
        //     'qty' => 'required',
        // ]);
        foreach ($request->input('products') as $data) {
            $inventory = Inventory::find($data['id']);
            $inventory->product_id = $data['product_id'];
            $inventory->batch_id = $data['batch'];
            $inventory->total_qty  = $data['qty'] ?? 0;
            $inventory->qty = $data['qty'] ?? 0;
            $inventory->mrp = $data['mrp'];
            $inventory->sku_code = $data['sku'];
            $inventory->godowns_id = $data['godowns_id'];
            $inventory->type = 'stock-in';
            $inventory->product_date = $data['product_date'];
            $inventory->save();
        }
        return response(['msg' => 'Stock update succesfully']);
    }

    public function inventories_history()
    {
        $data = StockOutHistory::all();
        return response(['data' => $data]);
    }
    public function stock_out(Request $request)
    {
        try {
            foreach ($request->input('updateProducts') as $data) {
                $inventory = Inventory::find($data['id']);
                $inventory->qty = $data['qty'] ? $inventory->qty - $data['qty'] : $inventory->qty;
                $inventory->type = 'stock-out';
                if ($inventory->save()) {
                    $history =   new StockOutHistory();
                    $history->user_id = \Auth::user()->id;
                    $history->product_id = $inventory->product_id;
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
                    $report->product_id = $inventory->product_id;
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
        } catch (\Exception $ex) {
            return response(['errors' => $ex->getMessage()], 200);
        }
    }
    public function stock_out_delete($id)
    {
        // $id = $request->get('id');$report_id = $request->get('report_id');
        // $data = StockOutHistory::find($id);
        // $qty = $data->previous_qty ?? NULL;
        // $inventory_id = $data->inventory_id ?? NULL;

        // if ($data->delete()) {
        //     $inventory = Inventory::find($inventory_id);
        //     $inventory->qty =  $qty ? $inventory->qty + $qty : $inventory->qty;
        //     $inventory->save();


        // }

        $report = StockOutReport::find($id);
        $report_qty = $report->previous_qty ?? NULL;
        $stock_report_id = $report->stock_report_id ?? NULL;
        $inventory_id = $report->inventory_id ?? NULL;
        if ($report) {
            if ($report->delete()) {
                $report = StockInReport::find($stock_report_id);
                $report->qty =  $report_qty ? $report->qty + $report_qty : $report->qty;
                $report->save();
                $inventory = Inventory::find($inventory_id);
                $inventory->qty =  $report_qty ? $inventory->qty + $report_qty : $report->qty;
                $inventory->save();
                return response()->json(['error' => 'stock-out delete succefully'], 200);
            }
        }

        return response()->json(['error' => 'not found'], 422);
    }


    public function add_bluk(Request $request)
    {
        DB::beginTransaction();
        $sku_code = NULL;

        foreach ($request->input('bulkProduct') as $data) {
            if ($data['sku']) {
                $validator = \Validator::make($request->all(), [
                    'bulkProduct.*.sku' => 'unique:products,sku',
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['errors' => $validator->errors() . $data['name']], 422);
                }
            } else {
                do {
                    $sku_code = rand(1000000, 9999999); // Generate a random SKU code
                    $existingSku = Product::where('sku', $sku_code)->first();
                } while ($existingSku); // If SKU exists, regenerate until it's unique
            }
        }

        foreach ($request->input('bulkProduct') as $data) {
            try {
                $imagePath = NULL;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('product', 'public');
                }

                $product = new Product();
                $product->name = $data['name'];
                $product->sku = $data['sku'] ?: $sku_code;
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
            } catch (\Exception $e) {
                // Rollback the transaction if something failed
                DB::rollBack();

                return response()->json(['error' => 'Transaction failed.'], 500);
            }
        }
        DB::commit();
        return response()->json(['msg' => 'Products created successfully'], 200);
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
                $inventory->batch_id = $data['batch_id'];
                $inventory->total_qty  = $data['qty'] ?? 0;;
                $inventory->qty = $data['qty'] ?? 0;
                $inventory->mrp = $data['mrp'];
                $inventory->sku_code = $data['sku_code'];
                $inventory->godowns_id = $data['godowns_id'];
                $inventory->type = 'stock-in';
                $inventory->product_date = $request->get('product_date');
                $inventory->save();
                $report = new StockInReport();
                $report->user_id = \Auth::user()->id;
                // $inventory->product_id = $data['product_id'];
                $report->batch_id = $data['batch_id'];
                $report->total_qty  = $data['qty'] ?? 0;;
                $report->qty = $data['qty'] ?? 0;
                $report->mrp = $data['mrp'];
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



    // public function product_demage_history()
    // {
    //     $data = ProductDemage::select('product_demages.*', 'godowns.name as godownsname')
    //         ->join('godowns', 'product_demages.godowns_id', 'godowns.id')
    //          ->with(['inventory' => function($query) {
    //     $query->withTrashed();  
    //         }])
    //         ->latest()
    //         ->get();
    //     return response(['data' => $data]);
    // }
       public function product_demage_history(Request $request)
    {
        
       $query = ProductDemage::select('product_demages.*', 'godowns.name as godownsname')
    ->join('godowns', 'product_demages.godowns_id', '=', 'godowns.id')
    ->with(['inventory'])
    ->latest();
    if ($request->has('godown') && $request->get('godown') != '' && $request->get('godown') != '') {
        $godown = $request->get('godown');
        $query->where('product_demages.godowns_id', $godown);
    }

    // Apply 'search' filter (search in sku_code, remarks, or product name)
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

    // Apply 'brand' filter (if provided)
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('inventory.product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    // Apply 'category' filter (if provided)
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
        $query->whereDate('product_demages.product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('product_demages.product_date', '<=', $toDate);
    }

    // Pagination (default limit 10 if not provided)
    $limit = $request->has('limit') ? (int) $request->get('limit') : 10;
    $page = $request->has('page') ? (int) $request->get('page') : 1;
    $offset = ($page - 1) * $limit;

    $total = $query->count();
    $data = $query->skip($offset)->take($limit)->get();

    // Return response
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$total], 200);
    }
    return response()->json(['data' => $data,'total'=>$total], 200);
        
    }

    // public function product_return_history()
    // {
    //     $data = ReturnProduct::select('return_products.*', 'godowns.name as godownsname')
    //         ->join('godowns', 'return_products.godowns_id', 'godowns.id')
    //         ->with(['inventory' => function($query) {
    //       $query->withTrashed();  
    //         }])
    //         ->latest()
    //         ->get();
    //     return response(['data' => $data]);
    // }
public function product_return_history(Request $request)
    {
        // $data = ReturnProduct::select('return_products.*', 'godowns.name as godownsname')
        //     ->join('godowns', 'return_products.godowns_id', 'godowns.id')
        //     ->latest()
        //     ->get();
        
          $query = ReturnProduct::select('return_products.*', 'godowns.name as godownsname')
            ->join('godowns', 'return_products.godowns_id', 'godowns.id')
            ->with(['inventory'])
            ->latest();
             if ($request->has('godown') && $request->get('godown') != '') {
        $godown = $request->get('godown');
        $query->where('return_products.godowns_id', $godown);
    }

    // Apply 'search' filter (search in sku_code, remarks, or product name)
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

    // Apply 'brand' filter (if provided)
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('inventory.product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    // Apply 'category' filter (if provided)
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

     $total = $query->count();
    $data = $query->skip($offset)->take($limit)->get();

    // Return response
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data,'total'=>$total], 200);
    }
    
        return response(['data' => $data,'total'=>$total],200);
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
                $history->qty = $data['qty'];
                $history->product_date = $data['product_date'];
                $history->save();
                // if ($history->save()) {
                //     $stockout =    StockOutHistory::where('sku_code', $inventory->sku_code)->where('inventory_id', $inventory->id)->where('user_id', \Auth::user()->id)->first();
                //     $stockout->previous_qty = $data['qty'] ?  $stockout->previous_qty - $data['qty'] : $stockout->previous_qty;
                //     $stockout->current_qty = $data['qty'] ?  $stockout->current_qty - $data['qty'] : $stockout->current_qty;
                //     $stockout->save();
                // }
            }
        }
        return response(['msg' => 'stock-update  successfully'], 200);
    }
    public function product_return_history_delete($id)
    {
        $data = ReturnProduct::find($id);
        $qty = $data->qty ?? NULL;
        $inventory_id = $data->inventories_id ?? NULL;
        if ($data->delete()) {
            $inventory = Inventory::find($inventory_id);
            if(isset($inventory)){
            $inventory->qty  = $qty ?  $inventory->qty + $qty :  $inventory->qty;
            $inventory->save();
            }
        }
        return response(['msg' => 'succesfully'], 200);
    }



    public function product_demage(Request $request)
    {
        foreach ($request->input('updateProducts') as $data) {
            $inventory = Inventory::find($data['id']);
            $inventory->qty =  $inventory->qty - $data['qty'];
            $inventory->save();
            $history =   new ProductDemage();
            $history->inventories_id = $inventory->id;
            $history->sku_code = $inventory->sku_code;
            $history->qty = $data['qty'];
            $history->godowns_id = $inventory->godowns_id;
            $history->remarks = $data['remarks'];
            $history->product_date = $data['product_date'] ? $data['product_date'] : date('YYYY-MM-DD');
            $history->save();
        }
        return response(['msg' => 'product remove from Inventory '], 200);
    }

    public function product_demage_history_delete($id)
    {
        $data = ProductDemage::find($id);
        $qty = $data->qty ?? NULL;
        $invertory_id = $data->inventories_id ?? NULL;
        if ($data->delete()) {
            $inventory = Inventory::find($invertory_id);
            if(isset($inventory)){
            $inventory->qty = $qty ?  $inventory->qty + $qty :  $inventory->qty;
            $inventory->save();
            }
        }
        return response(['msg' => 'succefully'], 200);
    }
    public function hold_release(Request $request)
    {
        $data = HoldQty::where('id', $request->get('id'))->first();
        if ($data) {
            $data->delete();
            return response(['data' => 'product hold  release succesfully'], 200);
        }
        return response(['data' => 'not found'], 400);
    }

    public function inventory()
    {
        $data = Inventory::all();
        return response(['data' => $data]);
    }

    // public function stock_in_reports()
    // {
    //     $data = StockInReport::with(['product', 'godownsName'])->latest()->get();
    //     return response()->json(['data' => $data], 201);
    // }
     public function stock_in_reports(Request $request)
    {
        $query = StockInReport::with(['product', 'godownsName'])->latest();
         if ($request->has('search') && $request->get('search') != '') {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('sku_code', 'like', '%' . $search . '%')
              ->orWhereHas('product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

    // Filter by godown
    if ($request->has('godown') && $request->get('godown') != '') {
        $godown = $request->get('godown');
        $query->where('godowns_id', $godown);
    }

    // Filter by brand
    if ($request->has('brand') && $request->get('brand') != '') {
        $brand = $request->get('brand');
        $query->whereHas('product', function ($q) use ($brand) {
            $q->where('brand', $brand);
        });
    }

    // Filter by category
    if ($request->has('category') && $request->get('category') != '') {
        $category = $request->get('category');
        $query->whereHas('product', function ($q) use ($category) {
            $q->where('category_id', $category);
        });
    }

    // Filter by size (optional)
    if ($request->has('size') && $request->get('size') != '') {
        $size = $request->get('size');
        $query->whereHas('product', function ($q) use ($size) {
            $q->where('size', 'like', '%' . $size . '%');
        });
    }

    // Filter by finish (optional)
    if ($request->has('finish') && $request->get('finish') != '') {
        $finish = $request->get('finish');
        $query->whereHas('product', function ($q) use ($finish) {
            $q->where('finish', 'like', '%' . $finish . '%');
        });
    }

    // Filter by date range (from and to date)
    if ($request->has('from') && $request->get('from') != '') {
        $fromDate = $request->get('from');
        $query->whereDate('product_date', '>=', $fromDate);
    }

    if ($request->has('to') && $request->get('to') != '') {
        $toDate = $request->get('to');
        $query->whereDate('product_date', '<=', $toDate);
    }

    // Paginate the results
    $limit = $request->has('limit') ? $request->get('limit') : 10; // default limit to 10
    $page = $request->has('page') ? $request->get('page') : 1; 
       $offset = ($page - 1) * $limit;
       $total = $query->count();
    $data = $query->skip($offset)->take($limit)->get();
        return response()->json(['data' => $data,'total'=>$total], 201);
    }

    // public function stock_out_reports()
    // {

    //     $data = StockOutReport::with(['product', 'stockInReports'])->get();

    //     return response()->json(['data' => $data], 201);
    // }
     public function stock_out_reports(Request $request)
    {

        $query = StockOutReport::with(['product', 'stockInReports']);
        
        if ($request->has('godown') && $request->get('godown') != '') {
        $godown = $request->get('godown');
        $query->whereHas('stockInReports', function ($q) use ($godown) {
            $q->where('godowns_id', $godown);
        });
    }

    
    if ($request->has('search') && $request->get('search') != '') {
    
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('sku_code', 'like', '%' . $search . '%')
              ->orWhereHas('product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

    
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
    
    $offset = ($page - 1) * $limit;
    $total = $query->count();
    $data = $query->skip($offset)->take($limit)->get();

    // Return response
    if ($data->isNotEmpty()) {
        return response()->json(['data' => $data ,'total'=>$total], 200);
    }
    return response()->json(['data' => $data,'total'=>$total], 200);
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

    
    $count = $query->count();

    
    $data = $query->skip(($page - 1) * $limit)->take($limit)->latest()->get();

    return response()->json([
        'data' => $data,
        'count' => $count,
    ], 200);
}



  public function inventorys(Request $request)
{
    $query = Inventory::with(['product.category', 'product.subcategory', 'product.brand', 'godownsName']);
    
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

    if ($request->has('godown') && $request->input('godown') !== null) {
        $godown = $request->get('godown');
        $query->where('godowns_id', $godown);
    }

    $page = $request->get('page', 1);
    $limits = $request->get('limit', 10);
    $filteredCount = $query->count();

    if (!$request->has('category_name') && !$request->has('subcategory_name') && !$request->get('page') && !$request->get('limit')) {
        $data = $query->paginate(10);
    } else {
        $data = $query->skip(($page - 1) * $limits)->take($limits)->latest()->get();
    }

    return response()->json([
        'data' => $data,
        'count' => $filteredCount, 
    ]);
}


    public function stock_in_recent($date)
{
    try {
       $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $data = Inventory::selectRaw('product_id, sku_code, SUM(total_qty) as total_qty')
    ->whereYear('created_at', $year)
    ->whereMonth('created_at', $month)
    ->groupBy('product_id', 'sku_code')
    ->orderByDesc('total_qty')
    ->take(10)
    ->with('product')
    ->get();
            

        return response(['data' => $data]);
    } catch (\Exception $ex) {
        return response()->json(['error' => $ex->getMessage()], 500);
    }
}
    public function stock_out_recent($date)
    {
         $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        // $data = StockOutReport::with('product')
        // ->whereYear('created_at', $year)
        //     ->whereMonth('created_at', $month)
        //     ->orderBy('previous_qty', 'desc')
        //     ->latest()
        //     ->take(10)
        //     ->get();
$data = StockOutReport::selectRaw('sku_code, SUM(previous_qty) as previous_qty')
    ->whereYear('created_at', $year)
    ->whereMonth('created_at', $month)
    ->groupBy('sku_code')
    ->orderByDesc('previous_qty')
    ->take(10)
    ->with('product') // Move after get() if needed
    ->get();
        return response(['data' => $data]);
    }
    //  public function transactions(Request $request)
    // {
    //     try {
    //         $todate = $request->get('to');
    //         $formdate = $request->get('from');
    //         $search = $request->get('search');
    //         $page = $request->get('page', 1);
    //         $limit = $request->get('limit', 10);

    //         $offset = ($page - 1) * $limit;

    //         // $applyFilters = function ($query) use ($todate, $formdate, $search) {
    //         //     if ($todate && $formdate) {
    //         //         $query->whereBetween('product_date', [$formdate, $todate]);
    //         //     }
    //         //     if (!empty($search)) {
    //         //         $q->where('products.sku', 'like', '%' . $search . '%')
    //         //   ->orWhere('products.category_id', 'like', '%' . $search . '%')
    //         //   ->orWhere('products.brand', 'like', '%' . $search . '%')
    //         //   ->orWhere('stock_in_reports.godowns_id', 'like', '%' . $search . '%')
    //         //   ->orWhere('return_products.godowns_id', 'like', '%' . $search . '%')
    //         //   ->orWhere('product_demages.godowns_id', 'like', '%' . $search . '%');
    //         //     }
    //         // };

    //         // Stock In
    //         $stockIn = \DB::table('stock_in_reports')
    //             ->join('products', 'products.sku', '=', 'stock_in_reports.sku_code')
    //             ->join('godowns', 'stock_in_reports.godowns_id', '=', 'godowns.id')
    //             ->leftJoin('brands', 'products.brand', '=', 'brands.id')
    //             ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    //             ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
    //             // ->orWhere('products.category_id', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.sku', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.name', 'like', '%' . $search . '%')
    //             ->selectRaw("
    //             stock_in_reports.id,
    //             godowns.name as godown,
    //             products.sku,
    //             products.name,
    //             products.category_id,
    //             products.brand as brand_id,
    //             brands.name as brand,
    //             categories.name as category,
    //             subcategories.name as subcategory,
    //             products.size,
    //             products.color,
    //             products.finish,
    //             products.thickness,
    //             stock_in_reports.mrp,
    //              stock_in_reports.godowns_id as godowns_id,
    //             stock_in_reports.product_date as date,
    //             stock_in_reports.total_qty as stockInQty,
    //             0 as stockOutQty,
    //             0 as returnQty,
    //             0 as damageQty,
    //             products.uom,
    //             stock_in_reports.batch_id as batch,
    //             'StockIn' as type
    //         ");

    //         // $applyFilters($stockIn);

    //         // Stock Out
    //         $stockOut = \DB::table('stock_out_reports')
    //             ->join('products', 'products.sku', '=', 'stock_out_reports.sku_code')
    //             ->join('inventories', 'inventories.id', '=', 'stock_out_reports.inventory_id')
    //             ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
    //             ->leftJoin('brands', 'products.brand', '=', 'brands.id')
    //             ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    //             ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
    //             //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.sku', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.name', 'like', '%' . $search . '%')
    //             ->selectRaw("
    //             stock_out_reports.id,
    //             godowns.name as godown,
    //             products.sku,
    //             products.name,
    //             products.category_id,
    //             products.brand as brand_id,
    //             brands.name as brand,
    //             categories.name as category,
    //             subcategories.name as subcategory,
    //             products.size,
    //             products.color,
    //             products.finish,
    //             products.thickness,
    //             inventories.mrp,
    //             inventories.godowns_id as  godowns_id,
    //             stock_out_reports.product_date as date,
    //             0 as stockInQty,
    //             stock_out_reports.previous_qty as stockOutQty,
    //             0 as returnQty,
    //             0 as damageQty,
    //             products.uom,
    //             stock_out_reports.batch as batch,
    //             'StockOut' as type
    //         ");

    //         // $applyFilters($stockOut);

    //         // Returns
    //         $returns = \DB::table('return_products')
    //             ->join('inventories', 'inventories.id', '=', 'return_products.inventories_id')
    //             ->join('products', 'products.sku', '=', 'inventories.sku_code')
    //             ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
    //             ->leftJoin('brands', 'products.brand', '=', 'brands.id')
    //             ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    //             ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
    //             //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.sku', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.name', 'like', '%' . $search . '%')
    //             ->selectRaw("
    //             return_products.id,
    //             godowns.name as godown,
    //             products.sku,
    //             products.name,
    //             products.category_id,
    //             products.brand as brand_id,
    //             brands.name as brand,
    //             categories.name as category,
    //             subcategories.name as subcategory,
    //             products.size,
    //             products.color,
    //             products.finish,
    //             products.thickness,
    //             inventories.mrp,
    //             inventories.godowns_id as godowns_id, 
    //             return_products.product_date as date,
    //             0 as stockInQty,
    //             0 as stockOutQty,
    //             return_products.qty as returnQty,
    //             0 as damageQty,
    //             products.uom,
    //             inventories.batch_id as batch,
    //             'Return' as type
    //         ");

    //         // $applyFilters($returns);

    //         // Damages
    //         $damages = \DB::table('product_demages')
    //             ->join('inventories', 'inventories.id', '=', 'product_demages.inventories_id')
    //             ->join('products', 'products.sku', '=', 'inventories.sku_code')
    //             ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
    //             ->leftJoin('brands', 'products.brand', '=', 'brands.id')
    //             ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    //             ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
    //             //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.sku', 'like', '%' . $search . '%')
    //             //  ->orWhere('products.name', 'like', '%' . $search . '%')
    //             ->selectRaw("
    //             product_demages.id,
    //             godowns.name as godown,
    //             products.sku,
    //             products.name,
    //             products.category_id,
    //             products.brand as brand_id,
    //             brands.name as brand,
    //             categories.name as category,
    //             subcategories.name as subcategory,
    //             products.size,
    //             products.color,
    //             products.finish,
    //             products.thickness,
    //             inventories.mrp,
    //              inventories.godowns_id as godowns_id, 
    //             product_demages.product_date as date,
    //             0 as stockInQty,
    //             0 as stockOutQty,
    //             0 as returnQty,
    //             product_demages.qty as damageQty,
    //             products.uom,
    //             inventories.batch_id as batch,
    //             'Damage' as type
    //         ");

    //         // $applyFilters($damages);

    //         // Merge all using UNION
    //         $unionQuery = $stockIn
    //             ->unionAll($stockOut)
    //             ->unionAll($returns)
    //             ->unionAll($damages);
    //         $query  = \DB::table(\DB::raw("({$unionQuery->toSql()}) as combined"))
    //             ->mergeBindings($stockIn);

    //         if ($request->has('search')) {
    //             $query->where(function ($q) use ($request) {
    //                 $q->orWhere('category', 'like', '%' . $request->get('search') . '%')
    //                     ->orWhere('sku', 'like', '%' . $request->get('search') . '%')
    //                     ->orWhere('name', 'like', '%' . $request->get('search') . '%')
    //                     ->orWhere('finish', 'like', '%' . $request->get('search') . '%')
    //                     ->orWhere('thickness', 'like', '%' . $request->get('search') . '%');
    //             });
    //         }


    //         if ($request->has('brand') && $request->get('brand') != NULL) {
    //             $query->where('brand_id', $request->get('brand'));
    //         }
    //         if ($request->has('size') && $request->get('size') != NULL) {
    //             $query->where('size', $request->get('size'));
    //         }
    //         if ($request->has('finish') && $request->get('finish') != NULL) {
    //             $query->where('finish', $request->get('finish'));
    //         }
    //         if ($request->has('godown') && $request->get('godown') != NULL) {
    //             $query->where('godowns_id', $request->get('godown'));
    //         }

    //         if ($request->has('category') && $request->get('category') != NULL) {
    //             $query->where('category_id', $request->get('category'));
    //         }
    //         if (isset($formdate) && isset($todate)) {
    //             $query->whereBetween('date', [$formdate, $todate]);
    //         }
    //         $results = $query
    //             ->orderByDesc('date')
    //             ->offset($offset)
    //             ->limit($limit)
    //             ->get();

    //         $total = \DB::table(\DB::raw("({$unionQuery->toSql()}) as total_count"))
    //             ->mergeBindings($stockIn)
    //             ->count();

    //         return response()->json([
    //             'data' => $results,
    //             'count' => $total
    //         ]);
    //     } catch (\Exception $ex) {
    //         return response()->json(['error' => $ex->getMessage()], 500);
    //     }
    // }
     public function transactions(Request $request)
    {
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
        $stockIn->where('stock_in_reports.godowns_id', $request->get('godown')); 
        
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
        $stockOut->where('inventories.godowns_id', $request->get('godown')); 
        
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
        $returns->where('inventories.godowns_id', $request->get('godown')); 
        
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
        $damages->where('inventories.godowns_id', $request->get('godown')); 
        
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
    public function total_skus(Request $request)
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
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('product_date', '<=', $toDate);
                    }
                })
                ->groupBy('sku_code', 'godowns_id', 'product_date')
                ->get()
                ->groupBy('sku_code');

            // Stock Out
            $stockOut = DB::table('stock_out_reports')
                ->select('sku_code', 'product_date', DB::raw('SUM(previous_qty) as total_qty'))
                ->whereMonth('product_date', $month->format('m'))
                ->whereYear('product_date', $month->format('Y'))
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('product_date', '<=', $toDate);
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
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('product_date', '<=', $toDate);
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
                ->where(function ($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->where('product_date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->where('product_date', '<=', $toDate);
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
    
    public function transactions_dump(Request $request)
    {
        try {
            $todate = $request->get('to');
            $formdate = $request->get('from');
            $search = $request->get('search');
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $offset = ($page - 1) * $limit;

            // $applyFilters = function ($query) use ($todate, $formdate, $search) {
            //     if ($todate && $formdate) {
            //         $query->whereBetween('product_date', [$formdate, $todate]);
            //     }
            //     if (!empty($search)) {
            //         $q->where('products.sku', 'like', '%' . $search . '%')
            //   ->orWhere('products.category_id', 'like', '%' . $search . '%')
            //   ->orWhere('products.brand', 'like', '%' . $search . '%')
            //   ->orWhere('stock_in_reports.godowns_id', 'like', '%' . $search . '%')
            //   ->orWhere('return_products.godowns_id', 'like', '%' . $search . '%')
            //   ->orWhere('product_demages.godowns_id', 'like', '%' . $search . '%');
            //     }
            // };

            // Stock In
            $stockIn = \DB::table('stock_in_reports')
                ->join('products', 'products.sku', '=', 'stock_in_reports.sku_code')
                ->join('godowns', 'stock_in_reports.godowns_id', '=', 'godowns.id')
                ->leftJoin('brands', 'products.brand', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
                // ->orWhere('products.category_id', 'like', '%' . $search . '%')
                //  ->orWhere('products.sku', 'like', '%' . $search . '%')
                //  ->orWhere('products.name', 'like', '%' . $search . '%')
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
            ")
             ->offset($offset)
                ->limit($limit);

            // $applyFilters($stockIn);

            // Stock Out
            $stockOut = \DB::table('stock_out_reports')
                ->join('products', 'products.sku', '=', 'stock_out_reports.sku_code')
                ->join('inventories', 'inventories.id', '=', 'stock_out_reports.inventory_id')
                ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
                ->leftJoin('brands', 'products.brand', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
                //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
                //  ->orWhere('products.sku', 'like', '%' . $search . '%')
                //  ->orWhere('products.name', 'like', '%' . $search . '%')
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
                inventories.godowns_id as  godowns_id,
                stock_out_reports.product_date as date,
                0 as stockInQty,
                stock_out_reports.previous_qty as stockOutQty,
                0 as returnQty,
                0 as damageQty,
                products.uom,
                stock_out_reports.batch as batch,
                'StockOut' as type
            ")
             ->offset($offset)
                ->limit($limit);

            // $applyFilters($stockOut);

            // Returns
            $returns = \DB::table('return_products')
                ->join('inventories', 'inventories.id', '=', 'return_products.inventories_id')
                ->join('products', 'products.sku', '=', 'inventories.sku_code')
                ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
                ->leftJoin('brands', 'products.brand', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
                //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
                //  ->orWhere('products.sku', 'like', '%' . $search . '%')
                //  ->orWhere('products.name', 'like', '%' . $search . '%')
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
            ")
             ->offset($offset)
                ->limit($limit);

            // $applyFilters($returns);

            // Damages
            $damages = \DB::table('product_demages')
                ->join('inventories', 'inventories.id', '=', 'product_demages.inventories_id')
                ->join('products', 'products.sku', '=', 'inventories.sku_code')
                ->join('godowns', 'inventories.godowns_id', '=', 'godowns.id')
                ->leftJoin('brands', 'products.brand', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.sub_category', '=', 'subcategories.id')
                //  ->orWhere('products.category_id', 'like', '%' . $search . '%')
                //  ->orWhere('products.sku', 'like', '%' . $search . '%')
                //  ->orWhere('products.name', 'like', '%' . $search . '%')
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
            ")
             ->offset($offset)
                ->limit($limit);
             $unionQuery = $stockIn
                ->unionAll($stockOut)
                ->unionAll($returns)
                ->unionAll($damages);
            $query  = \DB::table(\DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($stockIn);
            if (isset($formdate) && isset($todate)) {
                $query->whereBetween('date', [$formdate, $todate]);
            }
            $results = $query->get();

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
    public function unqiue_batch()
    {
        $data = Inventory::select('batch_id', 'sku_code')
        ->groupBy('batch_id', 'sku_code')
        ->get();
    return response()->json(['data'=>$data]);
    }
     public function hold_product(){
    
        $data  = HoldQty::select('id','hold_qty','holdername','user_id','godowns_id','inventories_id','created_at','remarks')->with(['user','godowns','inventory'])->get();
        return response()->json(['data'=>$data]);
    }
    
    public function dashboard(Request $request)
    {
        $data =[];
        $data['product'] = Product::count();
        $data['dis_continue_product'] = Product::where('dis_continue',0)->count();
        $data['inventory'] = Inventory::count();
        $data['holdQty'] = HoldQty::count();
        $data['godowns'] = Godowns::count();
        $data['brand'] =Brand::count();
        $data['category'] =Category::count();
        $data['user'] =User::count();
        $data['active_user'] =User::where('is_active',1)->count();
        $data['appUser'] = User::where('role_id',3)->count();
        $data['godowns_admin'] = User::where('role_id',2)->count();
        return response()->json(['data'=>$data]);
    }
}
