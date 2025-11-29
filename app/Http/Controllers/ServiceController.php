<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
public function index(Request $request){
    $query = Service::query();
    
    // Apply filters manually if they exist
    if ($request->has('service_type')) {
        $query->where('service_type', $request->service_type);
    }
    
    if ($request->has('transfer_speed')) {
        $query->where('transfer_speed', $request->transfer_speed);
    }
    
    $services = QueryBuilder::for($query)
        ->allowedFilters([
            AllowedFilter::exact('service_type'),
            AllowedFilter::exact('transfer_speed'),
        ])
        ->allowedSorts(['service_id', 'service_type', 'transfer_speed', 'base_fee', 'fee_percentage', 'created_at'])
        ->paginate($request->get('perPage', 10))
        ->appends($request->query());

    return response(['success' => true, 'data' => $services]);
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

   public function destroy($id)
{
    try {
        $service = Service::findOrFail($id);
        
        // Check if service has any transactions
        if ($service->transactions()->exists()) {
            $transactionCount = $service->transactions()->count();
            
            return response()->json([
                'success' => false,
                'message' => "Cannot delete service because it has $transactionCount associated transaction(s). Please remove or reassign the transactions first."
            ], 422);
        }
        
        $service->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully'
        ], 200); // Changed from 204 to 200 to include message
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Service not found'
        ], 404);
        
    } catch (\Exception $e) {
        Log::error('Delete service error: ' . $e->getMessage());
        
        // Check if it's a foreign key constraint error
        if (str_contains($e->getMessage(), 'foreign key constraint')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete service because it has associated transactions. Please remove the transactions first.'
            ], 422);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete service'
        ], 500);
    }
}
}
