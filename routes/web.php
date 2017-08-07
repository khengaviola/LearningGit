<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['middleware' => 'auth'], function(){
	Route::resource('dashboard', 'DashboardController');
	Route::resource('vehicle','VehicleController');
	Route::resource('supplier','SupplierController');
	Route::resource('type','ProductTypeController');
	Route::resource('brand','ProductBrandController');
	Route::resource('unit','ProductUnitController');
	Route::resource('variance','ProductVarianceController');
	Route::resource('product','ProductController');
	Route::resource('category','ServiceCategoryController');
	Route::resource('service','ServiceController');
	Route::resource('inspection','InspectionController');
	Route::get('/inspection/data/{id}','InspectionController@getData');
	Route::resource('package','PackageController');
	Route::resource('promo','PromoController');
	Route::resource('discount','DiscountController');
	Route::resource('technician','TechnicianController');
	// Transactions
	Route::resource('purchase','PurchaseController');
	Route::patch('purchase/finalize/{id}','PurchaseController@finalize');
	Route::get('purchase/finalz/{id}','PurchaseController@finalz');
	Route::resource('delivery','DeliveryController');
	Route::resource('return','ReturnController');
	Route::resource('inspect','InspectController');
	//Route::resource('estimate','EstimateController');
	Route::resource('job','JobController');
	Route::resource('query','QueryController');
	Route::post('query/load','QueryController@load');
	Route::resource('report','ReportController');
	Route::resource('utility','UtilitiesController');

	//PDF
	Route::get('purchase/pdf/{id}','PdfController@purchase');
	Route::get('delivery/pdf/{id}','PdfController@delivery');
	Route::get('estimate/pdf/{id}','PdfController@estimate');
	Route::get('job/pdf/{id}','PdfController@job');

	//GetJSON
	Route::get('vehicle/remove/{id}','VehicleController@remove');
	Route::get('type/remove/{id}','ProductTypeController@remove');
	Route::get('variance/category/{id}','ProductVarianceController@category');
	Route::get('product/type/{id}','ProductController@type');
	Route::get('inspection/remove/{id}','InspectionController@remove');
	Route::get('item/customer/{name}','ItemController@customer');
	Route::get('item/vehicle/{name}','ItemController@vehicle');
	Route::get('item/product/{id}','ItemController@product');
	Route::get('item/service/{id}','ItemController@service');
	Route::get('item/package/{id}','ItemController@package');
	Route::get('item/promo/{id}','ItemController@promo');
	Route::get('item/discount/{id}','ItemController@discount');
	Route::post('item/user','ItemController@user');
	Route::get('delivery/header/{id}','DeliveryController@header');
	Route::get('delivery/detail/{id}','DeliveryController@detail');
	Route::get('job/check/{id}','JobController@check');
	Route::get('job/get/{id}','JobController@get');
	Route::patch('job/finalize/{id}','JobController@finalize');
	Route::patch('job/process/{id}','JobController@process');
	Route::post('job/pay','JobController@pay');

	// Reactivate
	Route::patch('vehicle/reactivate/{id}','VehicleController@reactivate');
	Route::patch('supplier/reactivate/{id}','SupplierController@reactivate');
	Route::patch('type/reactivate/{id}','ProductTypeController@reactivate');
	Route::patch('brand/reactivate/{id}','ProductBrandController@reactivate');
	Route::patch('unit/reactivate/{id}','ProductUnitController@reactivate');
	Route::patch('variance/reactivate/{id}','ProductVarianceController@reactivate');
	Route::patch('product/reactivate/{id}','ProductController@reactivate');
	Route::patch('category/reactivate/{id}','ServiceCategoryController@reactivate');
	Route::patch('service/reactivate/{id}','ServiceController@reactivate');
	Route::patch('inspection/reactivate/{id}','InspectionController@reactivate');
	Route::patch('technician/reactivate/{id}','TechnicianController@reactivate');
	Route::patch('package/reactivate/{id}','PackageController@reactivate');
	Route::patch('promo/reactivate/{id}','PromoController@reactivate');
	Route::patch('discount/reactivate/{id}','DiscountController@reactivate');
	Route::patch('purchase/reactivate/{id}','PurchaseController@reactivate');
});