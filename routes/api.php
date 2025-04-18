<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\AccountSettingController;
use App\Http\Controllers\Api\LeadPreferenceController;
use App\Http\Controllers\Api\Customer\MyRequestController;
use App\Http\Controllers\Api\SuggestedQuestionController;
use App\Http\Controllers\Api\RecommendedLeadsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::get('/check_api', function () {
    return "check api";
});


Route::get('test_lead',[ApiController::class,'getLeadByPrefer']);




Route::prefix('notification')->group(function () {
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::post('add-update-notification-settings',[NotificationController::class,'addUpdateNotificationSettings']);
        Route::post('get-notification-settings',[NotificationController::class,'getNotificationSettings']);
    });
    
});

Route::prefix('customer')->group(function () {
    Route::get('test',[CustomerController::class,'test']);
    Route::post('my-request/check-paragraph-quality',[MyRequestController::class,'checkParagraphQuality']);
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::prefix('my-request')->group(function () {
            Route::get('get-submitted-request-list',[MyRequestController::class,'getSubmittedRequestList']);
            Route::get('get-submitted-request-info',[MyRequestController::class,'getSubmittedRequestInfo']);
            Route::post('create-new-request',[MyRequestController::class,'createNewRequest']);
            Route::post('add-image-to-submitted-request',[MyRequestController::class,'addImageToSubmittedRequest']);
            Route::post('add-details-to-request',[MyRequestController::class,'addDetailsToRequest']);           
        });

        Route::prefix('setting')->group(function () {
            Route::get('get-profile-info',[AccountSettingController::class,'getProfileInfo']);
            Route::post('update-profile-image',[AccountSettingController::class,'updateProfileImage']);
            Route::post('update-profile-info',[AccountSettingController::class,'updateProfileInfo']);
            Route::post('change-password',[AccountSettingController::class,'changePassword']);
        });
    });

});

Route::prefix('users')->group(function () {
    //Route::get('/', [UserController::class, 'index']);
    Route::post('/questions-answer', [LeadPreferenceController::class, 'questionAnswer']);
    Route::get('/popular-services', [ApiController::class, 'popularServices']);
    Route::post('/search-services', [ApiController::class, 'searchServices']);
    Route::get('/get-categories', [ApiController::class, 'getCategories']);
    Route::post('/registration', [UserController::class, 'registration']);
    Route::get('/all-services', [ApiController::class, 'allServices']);
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
    
        Route::post('/get-service-wise-location', [LeadPreferenceController::class, 'getServiceWiseLocation']);
        Route::post('/get-lead-preferences', [LeadPreferenceController::class, 'getleadpreferences']);
        Route::post('/get_user_locations', [LeadPreferenceController::class, 'getUserLocations']);
        Route::post('/get_user_services', [LeadPreferenceController::class, 'getUserServices']);
        Route::post('/lead-preferences', [LeadPreferenceController::class, 'leadpreferences']);
        Route::post('/get-lead-request', [LeadPreferenceController::class, 'getLeadRequest']);
        
        Route::post('/remove-location', [LeadPreferenceController::class, 'removeLocation']);
        Route::post('/edit-location', [LeadPreferenceController::class, 'editUserLocation']);
        Route::post('/remove-service', [LeadPreferenceController::class, 'removeService']);
        Route::post('/add_location', [LeadPreferenceController::class, 'addUserLocation']);
        Route::post('/pending-leads', [LeadPreferenceController::class, 'pendingLeads']);
        Route::post('/add_service', [LeadPreferenceController::class, 'addUserService']);
        Route::post('/get-services', [LeadPreferenceController::class, 'getservices']);
        Route::get('/get-credit-list', [LeadPreferenceController::class, 'getCreditList']);
        // Route::post('/leads-by-filter', [LeadPreferenceController::class, 'leadsByFilter']);
        
        Route::post('/seller-billing-details', [SettingController::class, 'sellerBillingDetails']);
        Route::post('/seller-card-details', [SettingController::class, 'sellerCardDetails']);
        Route::post('/seller-myprofile-qa', [SettingController::class, 'sellerMyprofileqa']);
        Route::get('/seller-profile-ques', [SettingController::class, 'sellerProfileQues']);
        Route::post('/seller-myprofile', [SettingController::class, 'sellerMyprofile']);

        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/edit-profile', [UserController::class, 'editProfile']);
        Route::post('/switch_user', [UserController::class, 'switchUser']);
        Route::post('/logout', [UserController::class, 'logout']);

        Route::post('/switch-autobid', [RecommendedLeadsController::class, 'switchRecommendedLeads']);
        Route::post('/autobid-list', [RecommendedLeadsController::class, 'getRecommendedLeads']);
        Route::post('/manual-leads', [RecommendedLeadsController::class, 'getManualLeads']);
        Route::post('/add-manual-bid', [RecommendedLeadsController::class, 'addManualBid']);
        Route::post('/autobid', [RecommendedLeadsController::class, 'addRecommendedLeads']);

        Route::post('/buy-credits', [CreditController::class, 'buyCredits']);
        Route::post('/add-coupon', [CreditController::class, 'addCoupon']);
        Route::get('/get-plans', [CreditController::class, 'getPlans']);

        Route::post('/add-suggested-que', [SuggestedQuestionController::class, 'addSuggestedQue']);


    });
    // Route::get('/{id}', [UserController::class, 'show']);
    // Route::put('/{id}', [UserController::class, 'update']);
    //Route::delete('/{id}', [UserController::class, 'destroy']);

    // add services 

    // Route::post('/add_service', [UserController::class, 'addUserService']);
    // Route::post('/add_location', [UserController::class, 'addUserLocation']);
    // Route::post('/get_user_services', [UserController::class, 'getUserServices']);
    // Route::post('/get_user_locations', [UserController::class, 'getUserLocations']);
    // Route::post('/switch_user', [UserController::class, 'switchUser']);
    // Route::get('/get-categories', [UserController::class, 'getCategories']);
});
