<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\EventController;

use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\APISuperAdmin\SuperAdminApiController;
use App\Http\Controllers\APIUnitAdmin\UnitAdminApiController;
use App\Http\Controllers\FundraisingController;
use App\Http\Controllers\SchemeFeeController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RenewalApiController;
use App\Http\Controllers\APIDistrictAdmin\DistrictAdminApiController;

// ═════════════════════════════════
// PUBLIC ROUTES
// No token required
// ═════════════════════════════════

// ── AUTH ──
Route::post('/login',
    [AuthController::class, 'login']);
    
Route::post('/v2/login',
    [AuthController::class, 'loginV2'])->middleware('throttle:5,1'); 
    
Route::post('/register',
    [MemberController::class, 'register']);
Route::post('/forgot-password',
    [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',
    [AuthController::class, 'resetPassword']);
// ── HIDE SIGNUP (public) ──
Route::get('/signup-status', function () {
    return \App\Helpers\ApiResponse::success([
        'hide_signup' => '1',
    ], 'Signup status fetched successfully.');
});


// ── LOCATION ──
Route::get('/states',
    [LocationController::class, 'getStates']);
Route::get('/districts',
    [LocationController::class, 'getDistricts']);   // GET, no body (Kerala fixed)
Route::post('/units',
    [LocationController::class, 'getUnits']);       // POST, body: { "district_id": 1 }




// ── EVENTS ──
Route::post('/events',
    [EventController::class, 'getEvents']);
Route::post('/events/detail',
    [EventController::class, 'getEventDetail']);
    

    


// ═════════════════════════════════
// MEMBER PROTECTED ROUTES
// Requires: Authorization: Bearer {token}
// ═════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/news', [NewsController::class, 'getPublishedNews']);
    Route::post('/news/detail', [NewsController::class, 'getNewsDetail']);
    
    Route::post('/news/read',     [NewsController::class, 'markRead']);
    Route::post('/gallery/seen',  [GalleryController::class, 'seen']);
    
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    Route::get('/payments/overview', [FeePaymentController::class, 'overview']);
    Route::get('/fees/other', [FeePaymentController::class, 'otherFees']);
    Route::get('/dues', [FeePaymentController::class, 'dues']);
    Route::post('/payments/confirm', [FeePaymentController::class, 'confirm']);
    Route::post('/payments/history', [FeePaymentController::class, 'history']);
    Route::get('/fundraising/campaigns',[FundraisingController::class, 'getCampaigns']);
    
    Route::get('/schemes/my-schemes',        [SchemeFeeController::class, 'mySchemes']);
    Route::post('/schemes/overview',         [SchemeFeeController::class, 'overview']);
    Route::get('/schemes/dues',              [SchemeFeeController::class, 'dues']);
    Route::get('/schemes/payments/history',  [SchemeFeeController::class, 'paymentsHistory']);
    
    Route::get('/committees/my-committees', [CommitteeController::class, 'myCommittees']);
    Route::get('/gallery', [GalleryController::class, 'index']);
    
    Route::get('/renewals/status', [RenewalApiController::class, 'status']);

    // ── AUTH ──
    Route::post('/logout',
        [AuthController::class, 'logout']);
    Route::get('/me',
        [AuthController::class, 'me']);
    Route::post('/change-password',
        [AuthController::class, 'changePassword']);
        

// ── UNIT MEMBERS (view only) ──
Route::post('/member/unit-members',
    [MemberController::class, 'getMyUnitMembers']);

    // ── MEMBER PROFILE ──
    Route::get('/member/profile',
        [MemberController::class, 'getProfile']);
    Route::post('/member/profile/update',
        [MemberController::class, 'updateProfile']);

    // ── MEMBER STATUS (quick check) ──
    Route::get('/member/status',
        [MemberController::class, 'getMemberStatus']);



Route::get('/member/renewal-status', [MemberController::class, 'renewalStatus']);
Route::post('/member/renew',         [MemberController::class, 'renew']);

Route::post('/expenses/overview',        [ExpenseController::class, 'overview']);
Route::post('/expenses/daily',           [ExpenseController::class, 'daily']);
Route::post('/expenses/monthly-summary', [ExpenseController::class, 'monthlySummary']);
Route::post('/expenses/daily-breakdown', [ExpenseController::class, 'dailyBreakdown']);
Route::post('/expenses/yearly-summary',  [ExpenseController::class, 'yearlySummary']);
Route::post('/expenses/month-wise-list', [ExpenseController::class, 'monthWiseList']);
Route::get('/expenses/year-range',       [ExpenseController::class, 'yearRange']);

Route::post('/events/register',
    [EventController::class, 'registerForEvent']);
Route::get('/events/my-registrations',
    [EventController::class, 'myRegistrations']);
Route::post('/events/cancel',
    [EventController::class, 'cancelRegistration']);


    
    Route::delete('/member/account/delete', [MemberController::class, 'deleteAccount']);
    
    Route::get('/member/certificate', [MemberController::class, 'certificateInfo']);

});



Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // ── LOCATION MANAGEMENT ──
    
    Route::post('/locations/district/add', [SuperAdminApiController::class, 'addDistrict']);
    Route::post('/locations/unit/add',     [SuperAdminApiController::class, 'addUnit']);

    // ── LOCATION DROPDOWNS ──
    Route::get('/locations/states',
        [LocationController::class, 'getUnassignedStates']);
    Route::get('/locations/districts',
    [LocationController::class, 'getUnassignedDistricts']);
    Route::post('/locations/units',
        [LocationController::class, 'getUnassignedUnits']);


    Route::get('/district-admins/locations/districts',
    [LocationController::class, 'getDistrictsForDistrictAdmin']);

// ── STATS (Kerala fixed) ──
Route::get('/stats/districts', [LocationController::class, 'getDistrictStats']);  // GET, no body
Route::post('/stats/units',    [LocationController::class, 'getUnitStats']);      // POST { "district_id": 1 }

    // ── MEMBERS ──
    Route::post('/members',          [SuperAdminApiController::class, 'members']);
    Route::get('/members/all',       [SuperAdminApiController::class, 'getAllMembers']);
    Route::get('/members/pending',   [SuperAdminApiController::class, 'pendingMembers']);
    Route::get('/members/approved',  [SuperAdminApiController::class, 'approvedMembers']);
    Route::get('/members/rejected',  [SuperAdminApiController::class, 'rejectedMembers']);
    Route::post('/members/search',   [SuperAdminApiController::class, 'searchMembers']);
    Route::post('/members/detail',   [SuperAdminApiController::class, 'memberDetail']);
    Route::post('/members/approve',  [SuperAdminApiController::class, 'approveMember']);
    Route::post('/members/reject',   [SuperAdminApiController::class, 'rejectMember']);
    Route::post('/members/delete',   [SuperAdminApiController::class, 'deleteMember']);
    Route::post('/members/register', [SuperAdminApiController::class, 'registerMember']);

    // ── UNIT ADMINS ──
    Route::post('/unit-admins',               [SuperAdminApiController::class, 'unitAdmins']);
    Route::get('/unit-admins/all',            [SuperAdminApiController::class, 'getAllUnitAdmins']);
    Route::post('/unit-admins/search',        [SuperAdminApiController::class, 'searchUnitAdmins']);
    Route::post('/unit-admins/register',      [SuperAdminApiController::class, 'registerUnitAdmin']);
    Route::post('/unit-admins/detail',        [SuperAdminApiController::class, 'unitAdminDetail']);
    Route::post('/unit-admins/toggle-status', [SuperAdminApiController::class, 'toggleUnitAdminStatus']);
    Route::post('/unit-admins/delete',        [SuperAdminApiController::class, 'deleteUnitAdmin']);

    // ── DISTRICT ADMINS ──
    Route::get('/district-admins/all',       [SuperAdminApiController::class, 'getAllDistrictAdmins']);
    Route::post('/district-admins/register', [SuperAdminApiController::class, 'registerDistrictAdmin']);
    Route::post('/district-admins/search',   [SuperAdminApiController::class, 'searchDistrictAdmins']);
    Route::post('/district-admins/delete',   [SuperAdminApiController::class, 'deleteDistrictAdmin']);
});



Route::middleware('auth:sanctum')->prefix('unit')->group(function () {

    // ── UNIT INFO & STATS ──
    Route::get('/info',              [UnitAdminApiController::class, 'myUnitInfo']);

    // ── MEMBERS ──
    Route::post('/members',          [UnitAdminApiController::class, 'members']);
    Route::get('/members/pending',   [UnitAdminApiController::class, 'pendingMembers']);
    Route::get('/members/approved',  [UnitAdminApiController::class, 'approvedMembers']);
    Route::get('/members/rejected',  [UnitAdminApiController::class, 'rejectedMembers']);
    Route::post('/members/search',   [UnitAdminApiController::class, 'searchMembers']);
    Route::post('/members/detail',   [UnitAdminApiController::class, 'memberDetail']);
    Route::post('/members/approve',  [UnitAdminApiController::class, 'approveMember']);
    Route::post('/members/reject',   [UnitAdminApiController::class, 'rejectMember']);
    Route::post('/members/register',      [UnitAdminApiController::class, 'registerMember']);
    Route::post('/members/delete',   [UnitAdminApiController::class, 'deleteMember']);  

});

Route::middleware('auth:sanctum')->prefix('district')->group(function () {

    // ── DISTRICT INFO & UNITS ──
    Route::get('/info',              [DistrictAdminApiController::class, 'myDistrictInfo']);
    Route::get('/units',             [DistrictAdminApiController::class, 'units']);

    // ── MEMBERS ──
    Route::post('/members',          [DistrictAdminApiController::class, 'members']);
    Route::get('/members/pending',   [DistrictAdminApiController::class, 'pendingMembers']);
    Route::get('/members/approved',  [DistrictAdminApiController::class, 'approvedMembers']);
    Route::get('/members/rejected',  [DistrictAdminApiController::class, 'rejectedMembers']);
    Route::post('/members/search',   [DistrictAdminApiController::class, 'searchMembers']);
    Route::post('/members/detail',   [DistrictAdminApiController::class, 'memberDetail']);
    Route::post('/members/approve',  [DistrictAdminApiController::class, 'approveMember']);
    Route::post('/members/reject',   [DistrictAdminApiController::class, 'rejectMember']);
    Route::post('/members/register', [DistrictAdminApiController::class, 'registerMember']);
    Route::post('/members/delete',   [DistrictAdminApiController::class, 'deleteMember']);
    Route::post('/unit-admins', [DistrictAdminApiController::class, 'unitAdmins']);
    Route::post('/units/add',            [DistrictAdminApiController::class, 'addUnit']);
    Route::post('/unit-admins/register', [DistrictAdminApiController::class, 'registerUnitAdmin']);
    Route::post('/unit-admins/delete', [DistrictAdminApiController::class, 'deleteUnitAdmin']);
    Route::get('/unit-admins/all', [DistrictAdminApiController::class, 'getAllUnitAdmins']);
});