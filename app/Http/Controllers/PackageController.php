<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Package;
use App\PackageProduct;
use App\PackageService;
use App\PackagePrice;
use App\Product;
use App\Service;
use Validator;
use Redirect;
use Response;
use Session;
use DB;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $packages = Package::where('isActive',1)->get();
        return View('package.index',compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = DB::table('product as p')
            ->join('product_type as pt','pt.id','p.typeId')
            ->join('product_brand as pb','pb.id','p.brandId')
            ->join('product_variance as pv','pv.id','p.varianceId')
            ->where('p.isActive',1)
            ->select('p.*','pt.name as type','pb.name as brand','pv.name as variance')
            ->get();
        $services = DB::table('service as s')
            ->join('service_category as c','c.id','s.categoryId')
            ->where('s.isActive',1)
            ->select('s.*','c.name as category')
            ->get();
        return View('package.create',compact('products','services'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $rules = [
            'name' => 'required|unique:package|max:50',
            'price' => 'required|numeric|between:0,10000',
            'qty.*' => 'sometimes|required|integer|max:3|between:0,100',
        ];
        $messages = [
            'unique' => ':attribute already exists.',
            'required' => 'The :attribute field is required.',
            'max' => 'The :attribute field must be no longer than :max characters.',
            'numeric' => 'The :attribute field must be a valid number.',
        ];
        $niceNames = [
            'name' => 'Package',
            'price' => 'Price',
            'qty.*' => 'Product Quantity',
        ];
        $validator = Validator::make($request->all(),$rules,$messages);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }
        else{
            try{
                DB::beginTransaction();
                Package::create([
                    'name' => trim($request->name),
                    'price' => trim($request->price),
                    'isActive' => 1
                ]);
                $package = Package::all()->last();
                $products = $request->product;
                $qty = $request->qty;
                $services = $request->service;
                if(!empty($products)){
                    foreach ($products as $key=>$product) {
                        PackageProduct::create([
                            'packageId' => $package->id,
                            'productId' => $product,
                            'quantity' => $qty[$key],
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($services)){
                    foreach ($services as $service) {
                        PackageService::create([
                            'packageId' => $package->id,
                            'serviceId' => $service,
                            'isActive' => 1
                        ]);
                    }
                }
                PackagePrice::create([
                    'packageId' => $package->id,
                    'price' => trim($request->price)
                ]);
                DB::commit();
            }catch(\Illuminate\Database\QueryException $e){
                DB::rollBack();
                $errMess = $e->getMessage();
                return Redirect::back()->withErrors($errMess);
            }
            $request->session()->flash('success', 'Successfully added.');  
            return Redirect::back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return View('layouts.404');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $package = Package::findOrFail($id);
        $products = DB::table('product as p')
            ->join('product_type as pt','pt.id','p.typeId')
            ->join('product_brand as pb','pb.id','p.brandId')
            ->join('product_variance as pv','pv.id','p.varianceId')
            ->where('p.isActive',1)
            ->select('p.*','pt.name as type','pb.name as brand','pv.name as variance')
            ->get();
        $services = DB::table('service as s')
            ->join('service_category as c','c.id','s.categoryId')
            ->where('s.isActive',1)
            ->select('s.*','c.name as category')
            ->get();
        return View('package.edit',compact('package','products','services'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'name' => ['required','max:50',Rule::unique('package')->ignore($id)],
            'price' => 'required|numeric|between:0,10000',
            'qty.*' => 'sometimes|required|integer|max:3|between:0,100',
        ];
        $messages = [
            'unique' => ':attribute already exists.',
            'required' => 'The :attribute field is required.',
            'max' => 'The :attribute field must be no longer than :max characters.',
            'numeric' => 'The :attribute field must be a valid number.',
        ];
        $niceNames = [
            'name' => 'Package',
            'price' => 'Price',
            'qty.*' => 'Product Quantity',
        ];
        $validator = Validator::make($request->all(),$rules,$messages);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }
        else{
            try{
                DB::beginTransaction();
                $package = Package::findOrFail($id);
                $package->update([
                    'name' => trim($request->name),
                    'price' => trim($request->price),
                ]);
                $products = $request->product;
                $qty = $request->qty;
                $services = $request->service;
                PackageProduct::where('packageId',$id)->update(['isActive'=>0]);
                PackageService::where('packageId',$id)->update(['isActive'=>0]);
                if(!empty($products)){
                    foreach ($products as $key=>$product) {
                        PackageProduct::updateOrCreate([
                            'packageId' => $package->id,
                            'productId' => $product,
                        ],[
                            'quantity' => $qty[$key],
                            'isActive' => 1
                        ]
                        );
                    }
                }
                if(!empty($services)){
                    foreach ($services as $service) {
                        PackageService::updateOrCreate([
                            'packageId' => $id,
                            'serviceId' => $service,
                        ],[
                            'isActive' => 1
                        ]);
                    }
                }
                PackagePrice::create([
                    'packageId' => $id,
                    'price' => trim($request->price)
                ]);
                DB::commit();
            }catch(\Illuminate\Database\QueryException $e){
                DB::rollBack();
                $errMess = $e->getMessage();
                return Redirect::back()->withErrors($errMess);
            }
            $request->session()->flash('success', 'Successfully updated.');  
            return Redirect::back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $item = Package::findOrFail($id);
        $item->update([
            'isActive' => 0
        ]);
        $request->session()->flash('success', 'Successfully deactivated.');  
        return Redirect::back();
    }

    public function product($id){
        $product = Product::with('type')->with('brand')->with('variance')->findOrFail($id);
        return response()->json(['product'=>$product]);
    }

    public function service($id){
        $service = Service::with('category')->findOrFail($id);
        return response()->json(['service'=>$service]);
    }
}