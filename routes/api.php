 <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Super\AuthController;
use App\Http\Controllers\Super\CategoryController;
use App\Http\Controllers\Super\RoleController;
use App\Http\Controllers\Super\GodownsController;
use App\Http\Controllers\Super\BrandController;
use App\Http\Controllers\Super\UserController;
use App\Http\Controllers\Super\SizeController;
use App\Http\Controllers\Super\ProductController;
use App\Http\Controllers\Super\SubcategoryController;
use App\Http\Controllers\Super\UomController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Grodowns\GrodownsController;
use App\Http\Controllers\UserController as WebUserController;
use App\Http\Controllers\Device\DeviceController;
use App\Http\Controllers\Device\DeviceAuthController;
use App\Http\Controllers\Admin\AdminBrandControler;
Route::post('login',[AuthController::class,'login']);
Route::post('send-otp',[DeviceAuthController::class,'send_otp']);
Route::post('/verify-otp', [DeviceAuthController::class, 'verifyOtp']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('super-admin')->group(function(){
        Route::prefix('category')->group(function(){
            Route::get('/',[CategoryController::class,'index']);
            
            Route::post('add',[CategoryController::class,'add']);
            Route::post('update',[CategoryController::class,'update']);
            Route::get('delete/{id}',[CategoryController::class,'delete']);
            Route::get('product/{date}',[CategoryController::class,'product_categorys']);
            Route::get('stock-in/{date}',[CategoryController::class,'stock_in_categorys']);
            Route::get('stock-out/{date}',[CategoryController::class,'stock_out_categorys']);
        });
        Route::prefix('sub-category')->group(function(){
            Route::get('/',[SubcategoryController::class,'index']);
            Route::post('add',[SubcategoryController::class,'add']);
            Route::post('update',[SubcategoryController::class,'update']);
            Route::get('delete/{id}',[SubcategoryController::class,'delete']);
        });
        Route::prefix('finish')->group(function(){
             Route::get('/',[SubcategoryController::class,'finish']);
            Route::post('add',[SubcategoryController::class,'finish_add']);
             Route::post('update',[SubcategoryController::class,'finish_update']);
              Route::get('delete/{id}',[SubcategoryController::class,'finish_delete']);
        });
        Route::prefix('role')->group(function(){
            Route::get('/',[RoleController::class,'index']);
            Route::post('add',[RoleController::class,'add']);
            Route::post('update',[RoleController::class,'update']);
            Route::get('delete/{id}',[RoleController::class,'delete']);
        });
        Route::prefix('godowns')->group(function(){
            Route::get('/',[GodownsController::class ,'index']);
            Route::post('add',[GodownsController::class,'add']);
            Route::post('update',[GodownsController::class,'update']);
            Route::get('delete/{id}',[GodownsController::class,'delete']);
        });
       
         Route::prefix('brand')->group(function(){
            Route::get('/',[BrandController::class,'index']);
           
            Route::get('recent',[BrandController::class,'recent']);
            Route::post('add',[BrandController::class,'add']);
            Route::post('update',[BrandController::class,'update']);
            Route::get('delete/{id}',[BrandController::class,'delete']);
             Route::get('product-brands-stock-in/{date}',[BrandController::class,'product_brand_stock_in']);
             Route::get('product-brands-stock-out/{date}',[BrandController::class,'product_brand_stock_out']);
        });
       
       Route::prefix('user')->group(function(){
           Route::get('/',[UserController::class,'index']);
           Route::post('add',[UserController::class,'add']);
           Route::post('update',[UserController::class,'update']);
           Route::get('delete/{id}',[UserController::class,'delete']);
           Route::get('web-user',[WebUserController::class,'index']);
           Route::get('log-info',[UserController::class,'log_info']);
       });
       Route::prefix('device')->group(function(){
           Route::get('/',[UserController::class,'device']);
           Route::post('/update',[UserController::class,'device_edit']);
           });
       
       Route::prefix('file')->group(function(){
           Route::get('/',[GodownsController::class,'file']);
            Route::post('add',[GodownsController::class,'file_add']);
            Route::post('update',[GodownsController::class,'file_update']);
            Route::get('delete/{id}',[GodownsController::class,'file_delete']);
           });
           Route::prefix('request')->group(function(){
           Route::get('/',[WebUserController::class,'request']);
         
           });
           Route::prefix('enquiry')->group(function(){
           Route::get('/',[WebUserController::class,'enquiry']);
          
           });
       Route::prefix('size')->group(function(){
           Route::get('/',[SizeController::class,'index']);
           Route::post('add',[SizeController::class,'add']);
           Route::post('update',[SizeController::class,'update']);
           Route::get('delete/{id}',[SizeController::class,'delete']);
       });
        Route::prefix('product')->group(function(){
            // reports
             Route::get('stock-in/reports',[ProductController::class,'stock_in_reports']);
            Route::get('stock-out/reports',[ProductController::class,'stock_out_reports']);
            
           Route::get('/',[ProductController::class,'index']);
           Route::get('recent',[ProductController::class,'recent']);
           Route::post('add',[ProductController::class,'add']);
           Route::post('update',[ProductController::class,'update']);
          Route::get('delete/{id}',[ProductController::class,'delete']);
           Route::post('stock-in',[ProductController::class,'stock_in']);
            Route::post('stock-in-bulk',[ProductController::class,'stock_in_bulk']);
           
           Route::post('stock-in-update',[ProductController::class,'stock_in_update']);
           Route::post('stock-out',[ProductController::class,'stock_out']);
           Route::get('stock-out/{date}',[ProductController::class,'stock_out_recent']);
           Route::get('stock-out/delete/{id}', [ProductController::class, 'stock_out_delete']);
              Route::post('add-bulk',[ProductController::class,'add_bluk']);
           Route::get('inventories-history',[ProductController::class,'inventories_history']);
            Route::get('return/history',[ProductController::class,'product_return_history']);
             Route::get('return/history/delete/{id}',[ProductController::class,'product_return_history_delete']);
           Route::get('demage/history',[ProductController::class,'product_demage_history']);
           Route::get('demage/history/delete/{id}',[ProductController::class,'product_demage_history_delete']);
           Route::post('return',[ProductController::class,'product_return']);
           Route::post('demage',[ProductController::class,'product_demage']);
            Route::post('hold-release',[ProductController::class,'hold_release']);
            //recent
            Route::get('stock-in-recent/{date}',[ProductController::class,'stock_in_recent']);
            // power-bi
            Route::get('power-bi/inventory',[ProductController::class,'inventory']);
            Route::get('products',[ProductController::class,'products']);
            Route::get('inventory',[ProductController::class,'inventorys']);
               Route::get('transactions',[ProductController::class,'transactions']);
             Route::get('transactions-dump',[ProductController::class,'transactions_dump']);
            Route::get('total_skus',[ProductController::class,'total_skus']);
             Route::get('hold',[ProductController::class,'hold_product']);
              Route::get('dashboard',[ProductController::class,'dashboard']);
       });
       Route::get('unique-batch',[ProductController::class,'unqiue_batch']);
       Route::prefix('uom')->group(function(){
           Route::get('/',[UomController::class,'index']);
           Route::post('add',[UomController::class,'add']);
           Route::post('update',[UomController::class,'update']);
           Route::get('delete/{id}',[UomController::class,'delete']);
       });
       
    });
    Route::prefix('admin')->group(function(){
         Route::prefix('brand')->group(function(){
              Route::get('stock-in/{date}',[AdminBrandControler::class,'stock_in_brand']);
                Route::get('stock-out/{date}',[AdminBrandControler::class,'stock_out_brand']);
         });
          Route::prefix('category')->group(function(){
              Route::get('stock-in/{date}',[AdminBrandControler::class,'stock_in_categorys']);
                Route::get('stock-out/{date}',[AdminBrandControler::class,'stock_out_categorys']);
         });
        Route::prefix('product')->group(function(){
            Route::get('/',[AdminProductController::class,'index']);
            Route::get('products',[AdminProductController::class,'products']);
            Route::post('add',[AdminProductController::class,'add']);
            Route::post('update',[AdminProductController::class,'update']);
            Route::post('stock-in',[AdminProductController::class,'stock_in']);
           Route::post('stock-in-bulk',[AdminProductController::class,'stock_in_bulk']);
             Route::post('stock-out',[AdminProductController::class,'stock_out']);
             Route::get('stock-delete/{id}',[AdminProductController::class,'stock_delete']);
             Route::post('add-bulk',[AdminProductController::class,'add_bluk']);
             Route::post('hold-release',[AdminProductController::class,'hold_release']);
             Route::post('return',[AdminProductController::class,'product_return']);
             Route::post('demage',[AdminProductController::class,'product_demage']);
              Route::get('return/history',[AdminProductController::class,'product_return_history']);
             Route::get('demage/history',[AdminProductController::class,'product_demage_history']); 
             Route::post('add-bluk-product',[AdminProductController::class,'add_bluk_product']);
             Route::get('report',[AdminProductController::class,'report']);
             Route::get('stock-in/reports',[AdminProductController::class,'stock_in_reports']);
             Route::get('stock-out/reports',[AdminProductController::class,'stock_out_reports']);
             Route::get('inventory',[AdminProductController::class,'inventory']);
             Route::get('transaction',[AdminProductController::class,'transaction']);
              Route::get('sku-month',[AdminProductController::class,'total_sku']);
               Route::get('stock-in/recent/{date}',[AdminProductController::class,'stock_in_recent']);
                Route::get('stock-out/recent/{date}',[AdminProductController::class,'stock_out_recent']);
                   Route::get('hold',[AdminProductController::class,'hold_product']);
             Route::get('dashboard',[AdminProductController::class,'dashboard']);
        });
        
        
        Route::prefix('godowns')->group(function(){
            Route::get('godowns',[AdminController::class,'godown']);
        });
    });
    Route::prefix('gowdowns')->group(function(){
        Route::get('product',[GrodownsController::class,'product']);
        Route::get('products',[GrodownsController::class,'products']);
        Route::post('product/update',[GrodownsController::class,'update']);
        Route::post('hold-in',[GrodownsController::class,'hold_in']);
        Route::post('hold-release',[GrodownsController::class,'hold_release']);
        Route::get('profile',[GrodownsController::class,'profile']);
        Route::get('hold-quantity',[GrodownsController::class,'hold_quantity']);
        Route::get('hold-quantity-user',[GrodownsController::class,'hold_quantity_user']);
        Route::get('hold-quantity-comman',[GrodownsController::class,'hold_quantity_comman']); 
        Route::get('inventory',[GrodownsController::class,'inventory']);
    });
});


Route::prefix('category')->group(function(){
       Route::get('/',[CategoryController::class,'index']);
       Route::get('category/{id}',[CategoryController::class,'product_category']);
      Route::get('sub_category/{type?}/{id}', [CategoryController::class, 'product_sub_category']);
    

  Route::get('product',[CategoryController::class,'category_wise']);
    Route::get('products/{date}',[CategoryController::class,'product_categorys']);
});


Route::prefix('product')->group(function(){
    Route::get('/{id}',[CategoryController::class,'product']);
     Route::get('{type}/{search?}', [CategoryController::class, 'search']); 
});
Route::prefix('user')->group(function(){
    Route::get('/',[WebUserController::class,'index']);
    Route::post('add',[WebUserController::class,'add']);
});
Route::prefix('feedback')->group(function(){
    Route::post('add',[WebUserController::class,'add_feedback']);
});
Route::prefix('enquiry')->group(function(){
    Route::post('add',[WebUserController::class,'add_enquiry']);
     Route::get('/',[WebUserController::class,'enquiry']);
});
Route::prefix('request')->group(function(){
    Route::post('add',[WebUserController::class,'add_request']);
      Route::get('/',[WebUserController::class,'request']);
});

Route::prefix('godowns')->group(function(){
    Route::get('/',[WebUserController::class,'godowns']);
});
Route::prefix('file')->group(function(){
    Route::get('/',[WebUserController::class,'file']);
});


