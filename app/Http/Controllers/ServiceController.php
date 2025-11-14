<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
class ServiceController extends Controller
{
    public function index(Request $request){
        $services = QueryBuilder::for(Service::class)
        ->allowedFilters([
            AllowedFilter::exact('service_type'),
            AllowedFilter::exact('transfer_speed'),
        ])
        ->allowedSorts(['service_id', 'service_type', 'transfer_speed', 'base_fee', 'fee_percentage', 'created_at'])
        ->paginate($request->get('perPage', 10))
        ->appends($request->query());

        return response(['success'=>true,'data'=>$services]);
    }

    public function show($id){
        $service=Service::findOrFail($id);
        return response(['success'=>true,'data'=>$service]);
    }

    public function store(Request $request){
       $formfields=$request->validate([
         'service_type' => 'required|in:transfer,payment,cash_out',
         'transfer_speed'=>'required|in:instant,same_day,1-3_days',
         'fee_percentage'=>'required|numeric',
       ]);
       $service=Service::create($formfields);
       return response(['success'=>true,'data'=>$service]);
    }

    public function update($id,Request $request){
        $service=Service::findOrFail($id);
        $formfields=$request->validate([
         'service_type' => 'required|in:transfer,payment,cash_out',
         'transfer_speed'=>'required|in:instant,same_day,1-3_days',
         'fee_percentage'=>'required|numeric',
       ]);
       $service->update($formfields);
       return response(['success'=>true,'data'=>$service]);
    }

    public function destroy($id){
        $service=Service::findOrFail($id);
        $service->delete();
        return response(['success'=>true], Response::HTTP_NO_CONTENT);
    }
}
