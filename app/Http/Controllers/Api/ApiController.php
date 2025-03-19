<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};

class ApiController extends Controller
{
    public function getCategories()
    {
        $aRows = Category::where('status',1)->get();
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function popularServices()
    {
        $aRows = Category::where('is_home',1)->where('status',1)->get();
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function searchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request
    
        // Check if search keyword is provided; otherwise, return empty
        if (empty($search)) {
            $categories = [];
            return $this->sendResponse(__('Category Data'), $categories);
        }
    
        $categories = Category::where('status', 1)
                              ->where(function ($query) use ($search) {
                                  $query->where('name', 'LIKE', "%{$search}%")
                                        ->orWhere('description', 'LIKE', "%{$search}%");
                              })
                              ->get();
    
        return $this->sendResponse(__('Category Data'), $categories);
    }

    public function questionAnswer(Request $request)
    {
        $service_id = $request->service_id; 
    
        if (empty($service_id)) {
            return $this->sendResponse(__('Category Data'), []);
        }
    
        // Fetch all records where 'category' matches the given service_id
        $categories = LeadRequest::where('category', $service_id)
                                 ->where('status', 1)
                                 ->orderBy('id', 'DESC')
                                 ->get();
    
        return $this->sendResponse(__('Category Data'), $categories);
    }
}
