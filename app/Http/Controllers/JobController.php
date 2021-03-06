<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\JobHeader;
use App\JobProduct;
use App\JobService;
use App\JobPackage;
use App\JobPromo;
use App\JobDiscount;
use App\JobTechnician;
use App\JobPayment;
use App\Technician;
use App\Vehicle;
use App\Customer;
use App\Inventory;
use Validator;
use Redirect;
use Response;
use Session;
use DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon as Carbon;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $jobs = DB::table('job_header as j')
            ->join('customer as c','c.id','j.customerId')
            ->join('vehicle as v','v.id','j.vehicleId')
            ->join('vehicle_model as vd','vd.id','v.modelId')
            ->join('vehicle_make as vk','vk.id','vd.makeId')
            ->select('j.*','j.id as jobId','c.*','v.*','vd.name as model','vd.year as year','v.isManual as transmission','vk.name as make')
            ->get();
        $date = date('Y-m-d');
        $customers = DB::table('customer')
            ->select('customer.*')
            ->get();
        $autos = DB::table('vehicle_model as vd')
            ->join('vehicle_make as vk','vd.makeId','vk.id')
            ->select('vd.*','vk.name as make')
            ->where('hasAuto',1)
            ->get();
        $manuals = DB::table('vehicle_model as vd')
            ->join('vehicle_make as vk','vd.makeId','vk.id')
            ->select('vd.*','vk.name as make')
            ->where('hasManual',1)
            ->get();
        $technicians = Technician::where('isActive',1)->get();
        $racks = DB::table('rack as r')
            ->where(DB::raw('(SELECT COUNT(*) FROM job_header as jh WHERE jh.rackId=r.id AND jh.release IS NULL)'),0)
            ->where('r.isActive',1)
            ->select('r.*')
            ->get();
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
        $packages = DB::table('package as p')
            ->where('p.isActive',1)
            ->select('p.*')
            ->get();
        $promos = DB::table('promo as p')
            ->where('dateStart','>=',$date)
            ->where('dateEnd','<=',$date)
            ->where('p.isActive',1)
            ->select('p.*')
            ->get();
        $discounts = DB::table('discount as d')
            ->where('d.isActive',1)
            ->where('d.isWhole',1)
            ->select('d.*')
            ->get();
        return View('job.index',compact('jobs','customers','autos','manuals','technicians','racks','products','services','packages','promos','discounts','date'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return View('layouts.404');
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
            'firstName' => 'required|max:45',
            'middleName' => 'max:45',
            'lastName' => 'required|max:45',
            'contact' => 'required',
            'email' => 'nullable|email',
            'street' => 'nullable|max:140',
            'brgy' => 'nullable|max:140',
            'city' => 'required|max:140',
            'plate' => 'required',
            'modelId' => 'required',
            'mileage' => 'nullable|between:0,1000000',
            'technician.*' => 'required',
            'rackId.*' => 'required',
            'product.*' => 'sometimes|required',
            'productQty.*' => 'sometimes|required|numeric',
            'service.*' => 'sometimes|required',
            'package.*' => 'sometimes|required',
            'packageQty.*' => 'sometimes|required|numeric',
            'promo.*' => 'sometimes|required',
            'promoQty.*' => 'sometimes|required|numeric',
        ];
        $messages = [
            'unique' => ':attribute already exists.',
            'required' => 'The :attribute field is required.',
            'max' => 'The :attribute field must be no longer than :max characters.'
        ];
        $niceNames = [
            'firstName' => 'First Name',
            'middleName' => 'Middle Name',
            'lastName' => 'Last Name',
            'contact' => 'Contact No.',
            'email' => 'Email Address',
            'street' => 'No. & St./Bldg.',
            'brgy' => 'Brgy./Subd.',
            'city' => 'City/Municipality',
            'plate' => 'Plate No.',
            'modelId' => 'Vehicle Model',
            'mileage' => 'Mileage',
            'technician.*' => 'Technician Assigned',
            'rackId.*' => 'Rack',
            'product.*' => 'Product',
            'productQty.*' => 'Product Quantity',
            'service.*' => 'Service',
            'package.*' => 'Package',
            'packageQty.*' => 'Package Quantity',
            'promo.*' => 'Promo Quantity',
            'promoQty.*' => 'Promo Quantity',
        ];
        $validator = Validator::make($request->all(),$rules,$messages);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }
        else{
            try{
                DB::beginTransaction();
                $customer = Customer::updateOrCreate(
                    [
                        'firstName' => trim($request->firstName),
                        'middleName' => trim($request->middleName),
                        'lastName' => trim($request->lastName)
                    ],[
                        'contact' => str_replace('_','',trim($request->contact)),
                        'email' => $request->email,
                        'card' => $request->card,
                        'street' => trim($request->street),
                        'brgy' => trim($request->brgy),
                        'city' => trim($request->city),
                    ]
                );
                $mileage = ($request->mileage==''||$request->mileage==null ? 0 : $request->mileage);
                $model = explode(',',$request->modelId);
                $vehicle = Vehicle::updateOrCreate(
                    ['plate' => str_replace('_','',trim($request->plate))],
                    [
                        'modelId' => $model[0],
                        'isManual' => $model[1],
                        'mileage' => str_replace(' km','',$mileage)
                    ]
                );
                $time = date('H:i:s');
                $job = JobHeader::create([
                    'customerId' => $customer->id,
                    'vehicleId' => $vehicle->id,
                    'rackId' => $request->rackId,
                    'total' => str_replace(',','',$request->computed),
                    'paid' => 0,
                    'start' => $request->start." ".$time
                ]);
                $products = $request->product;
                $prodQty = $request->productQty;
                $services = $request->service;
                $packages = $request->package;
                $packQty = $request->packageQty;
                $promos = $request->promo;
                $promoQty = $request->promoQty;
                $discounts = $request->discount;
                if(!empty($products)){
                    foreach($products as $key=>$product){
                        JobProduct::create([
                            'jobId' => $job->id,
                            'productId' => $product,
                            'quantity' => $prodQty[$key],
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($services)){
                    foreach($services as $key=>$service){
                        JobService::create([
                            'jobId' => $job->id,
                            'serviceId' => $service,
                        ]);
                    }
                }
                if(!empty($packages)){
                    foreach($packages as $key=>$package){
                        JobPackage::create([
                            'jobId' => $job->id,
                            'packageId' => $package,
                            'quantity' => $packQty[$key],
                        ]);
                    }
                }
                if(!empty($promos)){
                    foreach($promos as $key=>$promo){
                        JobPromo::create([
                            'jobId' => $job->id,
                            'promoId' => $promo,
                            'quantity' => $promoQty[$key],
                        ]);
                    }
                }
                if(!empty($discounts)){
                    foreach($discounts as $key=>$discount){
                        JobDiscount::create([
                            'jobId' => $job->id,
                            'discountId' => $discount,
                        ]);
                    }
                }
                $technicians = $request->technician;
                foreach($technicians as $technician){
                    JobTechnician::create([
                        'jobId' => $job->id,
                        'technicianId' => $technician,
                    ]);
                }
                DB::commit();
            }catch(\Illuminate\Database\QueryException $e){
                DB::rollBack();
                $errMess = $e->getMessage();
                return Redirect::back()->withErrors($errMess);
            }
            $request->session()->flash('success', 'Successfully added.');  
            return Redirect('job');
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
        $job = JobHeader::findOrFail($id);
        $customers = DB::table('customer')
            ->select('customer.*')
            ->get();
        $autos = DB::table('vehicle_model as vd')
            ->join('vehicle_make as vk','vd.makeId','vk.id')
            ->select('vd.*','vk.name as make')
            ->where('hasAuto',1)
            ->get();
        $manuals = DB::table('vehicle_model as vd')
            ->join('vehicle_make as vk','vd.makeId','vk.id')
            ->select('vd.*','vk.name as make')
            ->where('hasManual',1)
            ->get();
        $technicians = Technician::where('isActive',1)->get();
        $racks = DB::table('rack as r')
            ->where(DB::raw('(SELECT COUNT(*) FROM job_header as jh WHERE jh.rackId=r.id AND jh.release IS NULL AND r.id!='.$id.')'),0)
            ->where('r.isActive',1)
            ->select('r.*')
            ->get();
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
        $packages = DB::table('package as p')
            ->where('p.isActive',1)
            ->select('p.*')
            ->get();
        $promos = DB::table('promo as p')
            ->where('dateStart','>=',$job->created_at)
            ->where('dateEnd','<=',$job->created_at)
            ->where('p.isActive',1)
            ->select('p.*')
            ->get();
        $discounts = DB::table('discount as d')
            ->where('d.isActive',1)
            ->where('d.isWhole',1)
            ->select('d.*')
            ->get();
        return View('job.edit',compact('job','customers','autos','manuals','technicians','racks','products','services','packages','promos','discounts'));
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
            'firstName' => 'required|max:45',
            'middleName' => 'max:45',
            'lastName' => 'required|max:45',
            'contact' => 'required',
            'email' => 'nullable|email',
            'street' => 'nullable|max:140',
            'brgy' => 'nullable|max:140',
            'city' => 'required|max:140',
            'plate' => 'required',
            'modelId' => 'required',
            'mileage' => 'nullable|between:0,1000000',
            'technician.*' => 'required',
            'rackId.*' => 'required',
            'product.*' => 'sometimes|required',
            'productQty.*' => 'sometimes|required|numeric',
            'service.*' => 'sometimes|required',
            'package.*' => 'sometimes|required',
            'packageQty.*' => 'sometimes|required|numeric',
            'promo.*' => 'sometimes|required',
            'promoQty.*' => 'sometimes|required|numeric',
        ];
        $messages = [
            'unique' => ':attribute already exists.',
            'required' => 'The :attribute field is required.',
            'max' => 'The :attribute field must be no longer than :max characters.'
        ];
        $niceNames = [
            'firstName' => 'First Name',
            'middleName' => 'Middle Name',
            'lastName' => 'Last Name',
            'contact' => 'Contact No.',
            'email' => 'Email Address',
            'street' => 'No. & St./Bldg.',
            'brgy' => 'Brgy./Subd.',
            'city' => 'City/Municipality',
            'plate' => 'Plate No.',
            'modelId' => 'Vehicle Model',
            'mileage' => 'Mileage',
            'technician.*' => 'Technician Assigned',
            'rackId.*' => 'Rack',
            'product.*' => 'Product',
            'productQty.*' => 'Product Quantity',
            'service.*' => 'Service',
            'package.*' => 'Package',
            'packageQty.*' => 'Package Quantity',
            'promo.*' => 'Promo Quantity',
            'promoQty.*' => 'Promo Quantity',
        ];
        $validator = Validator::make($request->all(),$rules,$messages);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }
        else{
            try{
                DB::beginTransaction();
                $customer = Customer::updateOrCreate(
                    [
                        'firstName' => trim($request->firstName),
                        'middleName' => trim($request->middleName),
                        'lastName' => trim($request->lastName)
                    ],[
                        'contact' => str_replace('_','',trim($request->contact)),
                        'email' => $request->email,
                        'card' => $request->card,
                        'street' => trim($request->street),
                        'brgy' => trim($request->brgy),
                        'city' => trim($request->city),
                    ]
                );
                $mileage = ($request->mileage==''||$request->mileage==null ? 0 : $request->mileage);
                $model = explode(',',$request->modelId);
                $vehicle = Vehicle::updateOrCreate(
                    ['plate' => str_replace('_','',trim($request->plate))],
                    [
                        'modelId' => $model[0],
                        'isManual' => $model[1],
                        'mileage' => str_replace(' km','',$mileage)
                    ]
                );
                $job = JobHeader::findOrFail($id);
                $job->update([
                    'customerId' => $customer->id,
                    'vehicleId' => $vehicle->id,
                    'rackId' => $request->rackId,
                    'total' => str_replace(',','',$request->computed),
                ]);
                $products = $request->product;
                $prodQty = $request->productQty;
                $services = $request->service;
                $packages = $request->package;
                $packQty = $request->packageQty;
                $promos = $request->promo;
                $promoQty = $request->promoQty;
                $discounts = $request->discount;
                JobProduct::where('jobId',$id)->update(['isActive'=>0]);
                JobService::where('jobId',$id)->update(['isActive'=>0]);
                JobPackage::where('jobId',$id)->update(['isActive'=>0]);
                JobPromo::where('jobId',$id)->update(['isActive'=>0]);
                if(!empty($products)){
                    foreach($products as $key=>$product){
                        JobProduct::create([
                            'jobId' => $job->id,
                            'productId' => $product,
                            'quantity' => $prodQty[$key],
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($services)){
                    foreach($services as $key=>$service){
                        JobService::create([
                            'jobId' => $job->id,
                            'serviceId' => $service,
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($packages)){
                    foreach($packages as $key=>$package){
                        JobPackage::create([
                            'jobId' => $job->id,
                            'packageId' => $package,
                            'quantity' => $packQty[$key],
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($promos)){
                    foreach($promos as $key=>$promo){
                        JobPromo::create([
                            'jobId' => $job->id,
                            'promoId' => $promo,
                            'quantity' => $promoQty[$key],
                            'isActive' => 1
                        ]);
                    }
                }
                if(!empty($discounts)){
                    foreach($discounts as $key=>$discount){
                        JobDiscount::create([
                            'jobId' => $job->id,
                            'discountId' => $discount,
                            'isActive' => 1
                        ]);
                    }
                }
                $technicians = $request->technician;
                foreach($technicians as $technician){
                    JobTechnician::create([
                        'jobId' => $job->id,
                        'technicianId' => $technician,
                        'isActive' => 1
                    ]);
                }
                DB::commit();
            }catch(\Illuminate\Database\QueryException $e){
                DB::rollBack();
                $errMess = $e->getMessage();
                return Redirect::back()->withErrors($errMess);
            }
            $request->session()->flash('success', 'Successfully updated.');  
            return Redirect('job');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return View('layouts.404');
    }

    public function get($id){
        $job = JobHeader::with('product','service','package','promo','discount','payment')->findOrFail($id);
        $paid = $job->payment->sum('paid');
        return response()->json(['job'=>$job,'paid'=>$paid]);
    }

    public function process($id){
        return View('layouts.404');
    }

    public function pay(Request $request){
        try{
            DB::beginTransaction();
            $job = JobHeader::with('payment')->findOrFail($request->id);
            $payment = str_replace(',','',$request->payment);
            $method =  ($request->method == '0' ? 0 : 1);
            $jp = JobPayment::create([
                'jobId' => $job->id,
                'paid' => $payment,
                'creditCard' => bcrypt($request->credit),
                'isCredit' => $method
            ]);
            $now = $job->paid + $payment;
            $job->update([
                'paid' => $now
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        return response()->json(['message'=>'Payment successfully added','job'=>$job,'payment'=>$jp]);
    }

    public function updatePay(Request $request){
        try{
            DB::beginTransaction();
            $job = JobHeader::findOrFail($request->jobId);
            $jp = JobPayment::findOrFail($request->id);
            $less = str_replace(',','',$request->less);
            $add = str_replace(',','',$request->add);
            $now = $job->paid-$less+$add;
            $jp->update([
                'paid' => $add,
            ]);
            $job->update([
                'paid' => $now
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        return response()->json(['message'=>'Payment successfully updated','job'=>$job]);
    }

    public function finalize(Request $request, $id){
        try{
            DB::beginTransaction();
            $job = JobHeader::findOrFail($id);
            $job->update([
                'isFinalize' => 1
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
            return Redirect::back()->withErrors($errMess);
        }
        $request->session()->flash('success', 'Successfully finalized.');  
        return Redirect('job');
    }

    public function release(Request $request, $id){
        try{
            DB::beginTransaction();
            $job = JobHeader::findOrFail($id);
            $job->update([
                'release' => Carbon::now()
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
            return Redirect::back()->withErrors($errMess);
        }
        $request->session()->flash('success', 'Successfully released.');  
        return Redirect('job');
    }

    public function check($id){
        $job = JobHeader::with('rack','customer','vehicle.model.make','technician.technician')->findOrFail($id);
        return response()->json(['job'=>$job]);
    }

    public function jobProduct(Request $request){
        try{
            DB::beginTransaction();
            $product = JobProduct::findOrFail($request->detailId);
            $inventory = Inventory::where('productId',$request->productId)->first();
            $stack = $inventory->quantity + $product->completed;
            $inventory->update([
                'quantity' => $stack
            ]);
            $completed = ($request->detailQty==$product->quantity ? 1 : 0);
            $product->update([
                'completed' => $request->detailQty,
                'isComplete' => $completed
            ]);
            $stack = $inventory->quantity - $request->detailQty;
            $inventory->update([
                'quantity' => $stack
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        return response()->json(['message'=>'Job successfully updated','completed'=>$completed]);
    }

    public function jobService(Request $request){
        try{
            DB::beginTransaction();
            $service = JobService::findOrFail($request->detailId);
            $completed = $request->detailDone;
            $service->update([
                'isComplete' => $completed
            ]);
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        return response()->json(['message'=>'Job successfully updated','completed'=>$completed]);
    }
    
    public function jobPackage(Request $request){
        try{
            DB::beginTransaction();
            $package = JobPackage::findOrFail($request->detailId);
            $completed = ($request->detailQty==$package->quantity ? 1 : 0);
            $check = 1;
            foreach($package->package->product as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = ($product->quantity*$package->completed)+$inventory->quantity;
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            foreach($package->package->product as $product){
                $total = $product->quantity*$request->detailQty;
                if($product->product->inventory->quantity<$total){
                    $check = 0;
                }
            }
            foreach($package->package->product as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = $inventory->quantity-($product->quantity*$package->completed);
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            if($check==1){
                foreach($package->package->product as $product){
                    $inventory = Inventory::where('productId',$product->productId)->first();
                    $reset = ($product->quantity*$package->completed)+$inventory->quantity;
                    $total = $product->quantity*$request->detailQty;
                    $inventory->update([
                        'quantity' => $reset
                    ]);
                    $total = $inventory->quantity - $total;
                    $inventory->update([
                        'quantity' => $total
                    ]);
                }
                $package->update([
                    'completed' => $request->detailQty,
                    'isComplete' => $completed
                ]);   
            }
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        if($check){
            return response()->json(['error'=>0,'message'=>'Job successfully updated','completed'=>$completed]);
        }else{
            return response()->json(['error'=>1,'message'=>'Insufficient resources','completed'=>$completed]);
        }
    }

    public function jobPromo(Request $request){
        try{
            DB::beginTransaction();
            $promo = JobPromo::findOrFail($request->detailId);
            $completed = ($request->detailQty==$promo->quantity ? 1 : 0);
            $check = 1;
            foreach($promo->promo->product as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = ($product->quantity*$promo->completed)+$inventory->quantity;
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            foreach($promo->promo->freeProduct as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = ($product->quantity*$promo->completed)+$inventory->quantity;
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            foreach($promo->promo->product as $product){
                $total = $product->quantity*$request->detailQty;
                if($product->product->inventory->quantity<$total){
                    $check = 0;
                }
            }
            foreach($promo->promo->freeProduct as $product){
                $total = $product->quantity*$request->detailQty;
                if($product->product->inventory->quantity<$total){
                    $check = 0;
                }
            }
            foreach($promo->promo->product as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = $inventory->quantity-($product->quantity*$promo->completed);
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            foreach($promo->promo->freeProduct as $product){
                $inventory = Inventory::where('productId',$product->productId)->first();
                $reset = $inventory->quantity-($product->quantity*$promo->completed);
                $inventory->update([
                    'quantity' => $reset
                ]);
            }
            if($check){
                foreach($promo->promo->product as $product){
                    $inventory = Inventory::where('productId',$product->productId)->first();
                    $reset = ($product->quantity*$promo->completed)+$inventory->quantity;
                    $total = $product->quantity*$request->detailQty;
                    $inventory->update([
                        'quantity' => $reset
                    ]);
                    $total = $inventory->quantity - $total;
                    $inventory->update([
                        'quantity' => $total
                    ]);
                }
                foreach($promo->promo->freeProduct as $product){
                    $inventory = Inventory::where('productId',$product->productId)->first();
                    $reset = ($product->quantity*$promo->completed)+$inventory->quantity;
                    $total = $product->quantity*$request->detailQty;
                    $inventory->update([
                        'quantity' => $reset
                    ]);
                    $total = $inventory->quantity - $total;
                    $inventory->update([
                        'quantity' => $total
                    ]);
                }
                $promo->update([
                    'completed' => $request->detailQty,
                    'isComplete' => $completed
                ]);   
            }
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        if($check){
            return response()->json(['error'=>0,'message'=>'Job successfully updated','completed'=>$completed]);
        }else{
            return response()->json(['error'=>1,'message'=>'Insufficient resources','completed'=>$completed]);
        }
    }

    public function jobFinal(Request $request){
        try{
            DB::beginTransaction();
            $job = JobHeader::findOrFail($request->id);
            $count = 0;
            $completed = 0;
            $check = 1;
            foreach($job->product as $product){
                $count += $product->quantity;
                $completed += $product->completed;
                if(!$product->isComplete){
                    $check = 0;
                }
            }
            foreach($job->service as $service){
                $count++;
                $completed += $service->isComplete;
                if(!$service->isComplete){
                    $check = 0;
                }
            }
            foreach($job->package as $package){
                $count += $package->quantity;
                $completed += $package->completed;
                if(!$package->isComplete){
                    $check = 0;
                }
            }
            foreach($job->promo as $promo){
                $count += $promo->quantity;
                $completed += $promo->completed;
                if(!$promo->isComplete){
                    $check = 0;
                }
            }
            $end = ($check ? Carbon::now() : null);
            $job->update([
                'isComplete' => $check,
                'end' => $end
            ]);
            $jobs = DB::table('job_header as j')
                ->join('customer as c','c.id','j.customerId')
                ->join('vehicle as v','v.id','j.vehicleId')
                ->join('vehicle_model as vd','vd.id','v.modelId')
                ->join('vehicle_make as vk','vk.id','vd.makeId')
                ->select('j.*','j.id as jobId','c.*','v.*','vd.name as model','vd.year as year','v.isManual as transmission','vk.name as make')
                ->get();
            DB::commit();
        }catch(\Illuminate\Database\QueryException $e){
            DB::rollBack();
            $errMess = $e->getMessage();
        }
        return response()->json(['message'=>'Job updated','jobs'=>$jobs,'count'=>$count,'completed'=>$completed]);
    }
}
