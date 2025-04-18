<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\CreditList;
use App\Models\Category;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;

class LeadPreferenceController extends Controller
{
    public function getservices(Request $request){
        $user_id = $request->user_id; 
        $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceId)->get();
        foreach ($categories as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
            $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();
        }
        return $this->sendResponse(__('Service Data'), $categories);
    }

    public function questionAnswer(Request $request)
    {
        $service_id = $request->service_id; 
    
        if (empty($service_id)) {
            return $this->sendResponse(__('Category Data'), []);
        }
    
        // Fetch all records where 'category' matches the given service_id
        $categories = ServiceQuestion::where('category', $service_id)
                                 ->where('status', 1)
                                 ->orderBy('id', 'DESC')
                                 ->get();
    
        return $this->sendResponse(__('Category Data'), $categories);
    }

    public function getServiceWiseLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        // Get all locations for the user
        $aRows = UserServiceLocation::where('user_id', $userId)
                                    ->whereIn('service_id', [$aVals['service_id']])
                                    ->get();


        return $this->sendResponse(__('User Service Data'), $aRows);
    }

    public function getleadpreferences(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $service_id = $request->service_id; 
        $leadPreference = ServiceQuestion::where('category', $service_id)->get();
        if(count($leadPreference)>0){
            $questions = [];
            foreach($leadPreference as $value){
                $value['answers'] = LeadPrefrence::where('question_id', $value->id)
                                                    ->where('user_id', $user_id)
                                                    ->pluck('answers')
                                                    ->first();
            }
            $leadPreferences = $leadPreference;
        }else{
            $leadPreferences = ServiceQuestion::where('category', $service_id)->get();
            
        }                          
        return $this->sendResponse(__('Lead Preferences Data'), $leadPreferences);                              
    }

    public function leadpreferences(Request $request): JsonResponse
    {
        $request->validate([
            'service_id'   => 'required',
            'user_id'      => 'required|integer',
            'question_id'  => 'required|array', // Expecting multiple question IDs
            'answers'      => 'required|array', // Expecting multiple answers
        ]);

        $insertedOrUpdatedData = [];

        foreach ($request->question_id as $index => $questionId) {
            $answers = $request->answers[$index] ?? '';

            // Clean and format answers (comma-separated)
            $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $answers);
            $cleanedAnswer = rtrim($cleanedAnswer, ','); // Remove trailing comma

            // Check if an entry exists
            $leadPreference = LeadPrefrence::where('service_id', $request->service_id)
                ->where('user_id', $request->user_id)
                ->where('question_id', $questionId)
                ->first();

            if ($leadPreference) {
                // Update existing record
                $leadPreference->update(['answers' => $cleanedAnswer]);
            } else {
                // Create a new record
                $leadPreference = LeadPrefrence::create([
                    'service_id'  => $request->service_id,
                    'question_id' => $questionId,
                    'user_id'     => $request->user_id,
                    'answers'     => $cleanedAnswer,
                ]);
            }

            $insertedOrUpdatedData[] = $leadPreference;
        }

     
        return $this->sendResponse(__('Data processed successfully'), $insertedOrUpdatedData);   
    }

    public function removeService(Request $request){
        $user_id = $request->user_id; 
        $serviceid = $request->service_id; 
        UserService::where('user_id',$user_id)->where('service_id',$serviceid)->delete();
        return $this->sendResponse(__('Service deleted Sucessfully')); 
    }

    public function getLeadRequest(Request $request)
    {
        $aVals = $request->all();
        // $user_id = 207;
        $user_id = $request->user_id;
        $searchName = $aVals['name'] ?? null;
        $leadSubmitted = $aVals['lead_time'] ?? null;
         // Extract miles and postcode if provided
        $distanceFilter = $aVals['distance_filter'] ?? null;
        $requestMiles = null;
        $requestPostcode = null;

        if ($distanceFilter && preg_match('/(\d+)\s*miles\s*from\s*(\w+)/i', $distanceFilter, $matches)) {
            $requestMiles = (int)$matches[1];       // e.g., 10
            $requestPostcode = strtoupper($matches[2]); // e.g., SS21
        }

         // Handle credit filter input
        $creditFilter = $aVals['credits'] ?? null;
        $creditValues = [];

        if (!empty($creditFilter)) {
            // Split multiple filters by comma
            $creditParts = array_map('trim', explode(',', $creditFilter));
            foreach ($creditParts as $part) {
                if (preg_match('/(\d+)\s*-\s*(\d+)\s*Credits/', $part, $matches)) {
                    $min = (int) $matches[1];
                    $max = (int) $matches[2];
                    $creditRanges[] = [$min, $max];
                }
            }
        }
       
          // Parse Lead Spotlights filter input (Urgent, Updated, Additional Details)
        $spotlightFilter = $aVals['lead_spotlights'] ?? null;
        $spotlightConditions = [];
        if (!empty($spotlightFilter)) {
            // Example format: "Urgent requests, Updated requests, Has additional details"
            $spotlightConditions = array_map('trim', explode(',', $spotlightFilter));
        }
    
        $baseQuery = self::basequery($user_id, $requestPostcode, $requestMiles);

        // Fix: use $aVals, not $aValues
        $serviceIds = [];
        if (!empty($aVals['service_id'])) {
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        }

         // Apply service_id filter if provided
        if (!empty($serviceIds)) {
            $baseQuery = $baseQuery->whereIn('service_id', $serviceIds);
        }

        // Apply credit range filters if any
        if (!empty($creditRanges)) {
            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereBetween('credit_score', $range);
                }
            });
        }
         // Apply lead spotlight filters if provided
        if (!empty($spotlightConditions)) {
            foreach ($spotlightConditions as $condition) {
                switch (strtolower($condition)) {
                    case 'urgent requests':
                        $baseQuery = $baseQuery->where('is_urgent', 1);
                        break;

                    case 'updated requests':
                        $baseQuery = $baseQuery->where('is_updated', 1);
                        break;

                    case 'has additional details':
                        $baseQuery = $baseQuery->where('has_additional_details', 1);
                        break;
                }
            }
        }
    
        // If name is provided, search based on user name first
        if ($searchName) {
            $namedLeadRequest = (clone $baseQuery)
                ->whereHas('customer', function ($query) use ($searchName) {
                    $query->where('name', 'LIKE', '%' . $searchName . '%');
                })
                ->orderBy('id', 'DESC')
                ->get();
    
            // If matching data found by name, return it
            if ($namedLeadRequest->isNotEmpty()) {
                return $this->sendResponse(__('Lead Request Data (Filtered by Name)'), $namedLeadRequest);
            }
        }

         // Apply lead_time filter if provided
        if ($leadSubmitted && $leadSubmitted != 'Any time') {
            $baseQuery = $baseQuery->where(function ($query) use ($leadSubmitted) {
                $now = Carbon::now();
                switch ($leadSubmitted) {
                    case 'Today':
                        $query->whereDate('created_at', $now->toDateString());
                        break;

                    case 'Yesterday':
                        $query->whereDate('created_at', $now->subDay()->toDateString());
                        break;

                    case 'Last 2-3 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(3));
                        break;

                    case 'Last 7 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
                        break;

                    case 'Last 14+ days':
                        $query->whereDate('created_at', '<', Carbon::now()->subDays(14));
                        break;
                }
            });
        }

    
        // If no matching data found by name or name not given, return all
        $leadrequest = $baseQuery->orderBy('id', 'DESC')->get();
    
        return $this->sendResponse(__('Lead Request Data'), $leadrequest);
    }

    public function basequery($user_id, $requestPostcode = null, $requestMiles = null){
        $userServices = DB::table('user_services')
            ->where('user_id', $user_id)
            ->pluck('service_id')
            ->toArray();
    
        $searchTerms = DB::table('lead_prefrences')
            ->where('user_id', $user_id)
            ->pluck('answers')
            ->toArray();
    
        // Base leadrequest query
        $baseQuery = LeadRequest::with(['customer', 'category'])
                                ->where('customer_id', '!=', $user_id)
                                ->whereIn('service_id', $userServices)
                                ->where(function ($query) use ($searchTerms) {
                                    foreach ($searchTerms as $term) {
                                        $query->orWhereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
                                    }
                                });
        if ($requestPostcode && $requestMiles) {
            $leadIdsWithinDistance = [];
            $leads = LeadRequest::select('id', 'postcode')
                ->where('customer_id', '!=', $user_id)
                ->get();
        
            foreach ($leads as $lead) {
                if ($lead->postcode) {
                    $distance = $this->getDistance($requestPostcode, $lead->postcode); // returns in km
                    if ($distance && ($distance <= ($requestMiles * 1.60934))) {
                        $leadIdsWithinDistance[] = $lead->id;
                    }
                }
            }
        
            $baseQuery->whereIn('id', $leadIdsWithinDistance);
        }                        
        return $baseQuery;                        
    }

    public function getDistance($postcode1, $postcode2)
    {
        $encodedPostcode1 = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            $distanceText = $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
            return floatval(str_replace(['km', ','], '', $distanceText)); // return distance as float (km)
        } else {
            return null;
        }
    }

    

    public function getLeadRequest1(Request $request)
    {
        $user_id = $request->user_id;
        $userServices = DB::table('user_services')
            ->where('user_id', $user_id)
            ->pluck('service_id')
            ->toArray();
        
        $searchTerms = DB::table('lead_prefrences')
            ->where('user_id', $user_id)
            ->pluck('answers')
            ->toArray();
            
        
        $leadrequest = LeadRequest::with(['customer', 'category'])
        ->where('customer_id','!=',$user_id)
        ->whereIn('service_id', $userServices)
        
        ->where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->orWhereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
            }
        })
        ->orderBy('id', 'DESC')
        ->get();
            
            return $this->sendResponse(__('Lead Request Data'), $leadrequest);

    }

    public function pendingLeads(Request $request)
    {
        $aValues = $request->all();
        $serviceIds = is_array($aValues['service_id']) ? $aValues['service_id'] : explode(',', $aValues['service_id']);
        $leadcount = LeadRequest::whereIn('service_id', $serviceIds)
                            ->get()->count();
        return $this->sendResponse('Pending Leads', $leadcount);
    }

    public function addUserService(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $request->user_id;
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('user_services', 'service_id')->where(function ($query) use ($userId ) {
                    return $query->where('user_id', $userId );
                })
            ],
            'user_id' => 'required|exists:users,id',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
            'service_id.unique' => 'You have already added this service to your profile.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $service = UserService::createUserService($aVals['user_id'],$aVals['service_id'],0);
        return $this->sendResponse(__('this service added to your profile successfully'));
    }

    public function getUserServices(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $aRows = UserService::where('user_id',$userId)
        ->join('categories', 'categories.id', '=', 'user_services.service_id')
        ->select('user_services.*', 'categories.name')
        ->get();
        return $this->sendResponse(__('User Service Data'),$aRows);

    }

    public function addUserLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $userlocations = UserServiceLocation::where('user_id',$userId)->where('postcode',$aVals['postcode'])->first();
        
        if(isset($userlocations) && $userlocations !=''){
            return $this->sendError('Postcode with the same user already exists');
        }
        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        if ($serviceIds) {
            foreach ($serviceIds as $serviceId) {
                 $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

                    if (!$userService) {
                        continue; // skip if user_service does not exist
                    }
        
                    $userServiceId = $userService->id;
                    $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
                    $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
                    $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

           
                $aLocations['service_id'] = $serviceId;
                $aLocations['user_service_id'] = $userServiceId;
                $aLocations['user_id'] = $aVals['user_id'];
                $aLocations['postcode'] =$postcode;
                $aLocations['miles'] = $miles;
                $aLocations['nation_wide'] = $nationWide;
                UserServiceLocation::createUserServiceLocation($aLocations);
            }
            return $this->sendResponse(__('Location updated successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
    }

    public function getUserLocations(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        // Get all locations for the user
        $aRows = UserServiceLocation::where('user_id', $userId)
            ->orderBy('postcode')
            ->get();

        // Group by postcode to remove duplicates (only first entry per postcode)
        $uniqueRows = $aRows->unique('postcode')->values();

        // Add total services per postcode
        foreach ($uniqueRows as $value) {
            $value['total_services'] = $aRows->where('postcode', $value->postcode)->count();
            $value['leadcount'] =  LeadRequest::where('postcode', $value->postcode)->count();
        }

        return $this->sendResponse(__('User Service Data'), $uniqueRows);
    }

    public function editUserLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $validator = Validator::make($aVals, [
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
        ],
        [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        
        
        // Step 1: Remove entries not in the new list
        UserServiceLocation::where('user_id', $userId)
            ->whereIn('postcode', [$aVals['postcode_old']])
            ->delete();
        
        // Step 2: Update or create the rest
        foreach ($serviceIds as $serviceId) {
            $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

            if (!$userService) {
                continue; // skip if user_service does not exist
            }
            
            $userServiceId = $userService->id;
            $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
            $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
            $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;
           
            $aLocations['service_id'] = $serviceId;
            $aLocations['user_service_id'] = $userServiceId;
            $aLocations['user_id'] = $aVals['user_id'];
            $aLocations['postcode'] =$postcode;
            $aLocations['miles'] = $miles;
            $aLocations['nation_wide'] = $nationWide;
            UserServiceLocation::createUserServiceLocation($aLocations);
        }

        return $this->sendResponse(__('Location updated successfully'));
    }

    public function removeLocation(Request $request)
    {
        $aValues = $request->all();
        UserServiceLocation::whereIn('postcode', [$aValues['postcode']])
                            ->where('user_id', $aValues['user_id'])
                            ->delete();
        return $this->sendResponse('Location deleted sucessfully', []);
    }

    public function getCreditList(): JsonResponse
    {
        $aRows = CreditList::get();
        foreach ($aRows as $key => $value) {
            // Extract min and max from the credit label like "1-5 Credits"
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $value->credits, $matches)) {
                $min = (int)$matches[1];
                $max = (int)$matches[2];
    
                // Count leads in leadrequest table where credit_score falls in the range
                $leadCount = LeadRequest::whereBetween('credit_score', [$min, $max])->count();
                $value['leadcount'] = $leadCount;
            } else {
                $value['leadcount'] = 0; // Default if no valid range
            }
        }
        return $this->sendResponse(__('Credit Data'), $aRows);
    }

    // public function leadsByFilter(Request $request){
    //     $aVals = $request->all();
    //     $datas = [];
    //     if(!empty($aVals['name'])){
    //         $datas = User::where('name', 'like', '%' . $aVals['name'] . '%')->get();
    //     }
    //     return $this->sendResponse(__('Filter Data'),$datas);
    // }

    
}
