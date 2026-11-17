<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ForgotPasswordController;
use App\Http\Controllers\UnitAdmin\UnitAdminController;
use App\Http\Controllers\UnitAdmin\LoginController as UnitAdminLoginController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\UnitAdmin\UnitAdminReportController;
use App\Http\Controllers\Admin\RenewalController;
use App\Http\Controllers\UnitAdmin\RenewalController as UnitAdminRenewalController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminController;
use App\Http\Controllers\DistrictAdmin\LoginController as DistrictAdminLoginController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminContentController;
use App\Http\Controllers\DistrictAdmin\DistrictRenewalController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminReportController;

Route::get('/test-mail', function () {
    \Mail::raw('Test Mailtrap Working', function ($message) {
        $message->to('test@example.com')
                ->subject('Test');
    });

    return 'Mail Sent';
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

// Redirect root to admin login
Route::get('/', function () {
    return redirect()->route('admin.login');
});
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// ─────────────────────────────────
// ADMIN LOGIN (public)
// ─────────────────────────────────
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.post');

// ─────────────────────────────────
// UNIT ADMIN LOGIN (public)
// ─────────────────────────────────
Route::get('/unit-admin/login', [UnitAdminLoginController::class, 'showLogin'])->name('unit-admin.login');
Route::post('/unit-admin/login', [UnitAdminLoginController::class, 'login'])->name('unit-admin.login.post');


Route::get('/district-admin/login', [DistrictAdminLoginController::class, 'showLogin'])->name('district-admin.login');
Route::post('/district-admin/login', [DistrictAdminLoginController::class, 'login'])->name('district-admin.login.post');

// ─────────────────────────────────
// PASSWORD RESET (public) — MUST be before prefix groups
// ─────────────────────────────────
Route::get('/admin/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('admin.password.forgot');
Route::post('/admin/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('admin.password.forgot.post');
Route::get('/admin/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('admin.password.reset.form');
Route::post('/admin/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('admin.password.reset.post');

Route::get('/unit-admin/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('unit-admin.password.forgot');
Route::post('/unit-admin/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('unit-admin.password.forgot.post');
Route::get('/unit-admin/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('unit-admin.password.reset.form');
Route::post('/unit-admin/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('unit-admin.password.reset.post');

Route::get('/district-admin/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('district-admin.password.forgot');
Route::post('/district-admin/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('district-admin.password.forgot.post');
Route::get('/district-admin/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('district-admin.password.reset.form');
Route::post('/district-admin/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('district-admin.password.reset.post');  


// ── MEMBER PASSWORD RESET PAGE (for email link) ──
Route::get('/reset-password', function () {
    return view('member.reset-password', [
        'token' => request('token'),
        'email' => request('email'),
    ]);
})->name('member.password.reset');


// ─────────────────────────────────
// PROTECTED ADMIN ROUTES
// ─────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware('super_admin')
    ->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/download', [ReportController::class, 'download'])->name('reports.download');
        Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');

        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/states', [AdminController::class, 'states'])->name('states');
        Route::post('/states', [AdminController::class, 'storeState'])->name('states.store');
        Route::put('/states', [AdminController::class, 'updateState'])->name('states.update');
        Route::delete('/states', [AdminController::class, 'deleteState'])->name('states.delete');

        Route::get('/districts', [AdminController::class, 'districts'])->name('districts');
        Route::post('/districts', [AdminController::class, 'storeDistrict'])->name('districts.store');
        Route::put('/districts', [AdminController::class, 'updateDistrict'])->name('districts.update');
        Route::delete('/districts', [AdminController::class, 'deleteDistrict'])->name('districts.delete');
        Route::post('/districts/by-state', [AdminController::class, 'getDistrictsByState'])->name('districts.by-state');

        Route::get('/units', [AdminController::class, 'units'])->name('units');
        Route::post('/units', [AdminController::class, 'storeUnit'])->name('units.store');
        Route::put('/units', [AdminController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units', [AdminController::class, 'deleteUnit'])->name('units.delete');

        Route::get('/unit-admins', [AdminController::class, 'unitAdmins'])->name('unit-admins');
        Route::post('/unit-admins', [AdminController::class, 'storeUnitAdmin'])->name('unit-admins.store');
        Route::post('/unit-admins/update', [AdminController::class, 'updateUnitAdmin'])->name('unit-admins.update');
        Route::post('/unit-admins/reset-password', [AdminController::class, 'resetUnitAdminPassword'])->name('unit-admins.reset-password');
        Route::post('/unit-admins/toggle-status', [AdminController::class, 'toggleUnitAdminStatus'])->name('unit-admins.toggle-status');
        Route::delete('/unit-admins', [AdminController::class, 'deleteUnitAdmin'])->name('unit-admins.delete');


Route::get('/district-admins', [AdminController::class, 'districtAdmins'])->name('district-admins');
Route::post('/district-admins', [AdminController::class, 'storeDistrictAdmin'])->name('district-admins.store');
Route::post('/district-admins/update', [AdminController::class, 'updateDistrictAdmin'])->name('district-admins.update');
Route::post('/district-admins/reset-password', [AdminController::class, 'resetDistrictAdminPassword'])->name('district-admins.reset-password');
Route::post('/district-admins/toggle-status', [AdminController::class, 'toggleDistrictAdminStatus'])->name('district-admins.toggle-status');
Route::delete('/district-admins', [AdminController::class, 'deleteDistrictAdmin'])->name('district-admins.delete');

        Route::get('/members', [AdminController::class, 'members'])->name('members');
        Route::post('/members', [AdminController::class, 'storeMember'])->name('members.store');
        Route::get('/members/{id}/detail', [AdminController::class, 'memberDetail'])->name('members.detail');
        Route::post('/members/update', [AdminController::class, 'updateMember'])->name('members.update');
        Route::delete('/members', [AdminController::class, 'deleteMember'])->name('members.delete');
        Route::post('/members/approve', [AdminController::class, 'approveMember'])->name('members.approve');
        Route::post('/members/reject', [AdminController::class, 'rejectMember'])->name('members.reject');

        Route::get('/news', [AdminController::class, 'news'])->name('news');
        Route::post('/news', [AdminController::class, 'storeNews'])->name('news.store');
        Route::post('/news/update', [AdminController::class, 'updateNews'])->name('news.update');
        Route::post('/news/toggle-status', [AdminController::class, 'toggleNewsStatus'])->name('news.toggle-status');
        Route::delete('/news', [AdminController::class, 'deleteNews'])->name('news.delete');

        Route::get('/events', [AdminController::class, 'events'])->name('events');
        Route::post('/events', [AdminController::class, 'storeEvent'])->name('events.store');
        Route::post('/events/update', [AdminController::class, 'updateEvent'])->name('events.update');
        Route::post('/events/status', [AdminController::class, 'updateEventStatus'])->name('events.status');
        Route::delete('/events', [AdminController::class, 'deleteEvent'])->name('events.delete');
        Route::get('/events/{id}/detail', [AdminController::class, 'eventDetail'])->name('events.detail');
        Route::get('/events/{id}/registrations', [AdminController::class, 'eventRegistrations'])->name('events.registrations');

        Route::get('/change-password', [AdminController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [AdminController::class, 'changePassword'])->name('change-password.post');
        
        Route::get('/payments',[AdminController::class, 'payments'])->name('payments'); 
        
        Route::get('/fundraising', [AdminController::class, 'fundraising'])->name('fundraising');
        Route::post('/fundraising', [AdminController::class, 'storeFundraisingCampaign'])->name('fundraising.store');
        Route::post('/fundraising/update', [AdminController::class, 'updateFundraisingCampaign'])->name('fundraising.update');
        Route::post('/fundraising/status', [AdminController::class, 'updateFundraisingStatus'])->name('fundraising.status');
        Route::delete('/fundraising', [AdminController::class, 'deleteFundraisingCampaign'])->name('fundraising.delete');
        Route::get('/fundraising/{id}/detail', [AdminController::class, 'fundraisingCampaignDetail'])->name('fundraising.detail');
        
        Route::get('/other-fees', [AdminController::class, 'otherFees'])->name('other-fees');
        Route::post('/other-fees', [AdminController::class, 'storeOtherFee'])->name('other-fees.store');
        Route::put('/other-fees', [AdminController::class, 'updateOtherFee'])->name('other-fees.update');
        Route::put('/other-fees/toggle', [AdminController::class, 'toggleOtherFeeStatus'])->name('other-fees.toggle');
        Route::delete('/other-fees', [AdminController::class, 'deleteOtherFee'])->name('other-fees.delete');
        Route::get('/other-fees/{id}/detail', [AdminController::class, 'otherFeeDetail'])->name('other-fees.detail');
        Route::get('/other-fees/{id}/payers', [AdminController::class, 'otherFeePayers'])->name('other-fees.payers');
        
Route::get('/schemes',                [AdminController::class, 'schemes'])->name('schemes');
Route::post('/schemes/store',         [AdminController::class, 'storeScheme'])->name('schemes.store');
Route::post('/schemes/update',        [AdminController::class, 'updateScheme'])->name('schemes.update');
Route::post('/schemes/toggle-status', [AdminController::class, 'toggleSchemeStatus'])->name('schemes.toggle');
Route::post('/schemes/delete',        [AdminController::class, 'deleteScheme'])->name('schemes.delete');
Route::get('/schemes/{id}/detail',    [AdminController::class, 'schemeDetail'])->name('schemes.detail');
Route::get('/schemes/{id}/payers',    [AdminController::class, 'schemePayers'])->name('schemes.payers');


Route::get('/committees',                     [AdminController::class, 'committees'])->name('committees');
Route::post('/committees/store',              [AdminController::class, 'storeCommittee'])->name('committees.store');
Route::post('/committees/update',             [AdminController::class, 'updateCommittee'])->name('committees.update');
Route::post('/committees/toggle-status',      [AdminController::class, 'toggleCommitteeStatus'])->name('committees.toggle');
Route::post('/committees/delete',             [AdminController::class, 'deleteCommittee'])->name('committees.delete');
Route::get('/committees/{id}/detail',         [AdminController::class, 'committeeDetail'])->name('committees.detail');
Route::get('/committees/{id}/members',        [AdminController::class, 'committeeMembers'])->name('committees.members');
Route::post('/committees/members/add',        [AdminController::class, 'addCommitteeMember'])->name('committees.members.add');
Route::post('/committees/members/update-role',[AdminController::class, 'updateCommitteeMemberRole'])->name('committees.members.update-role');
Route::post('/committees/members/remove',     [AdminController::class, 'removeCommitteeMember'])->name('committees.members.remove');

Route::get('/gallery',           [AdminController::class, 'gallery'])->name('gallery');
Route::post('/gallery/store',    [AdminController::class, 'storeGalleryImages'])->name('gallery.store');
Route::put('/gallery/toggle',    [AdminController::class, 'toggleGalleryImageStatus'])->name('gallery.toggle');
Route::delete('/gallery',        [AdminController::class, 'deleteGalleryImage'])->name('gallery.delete');

Route::get('/renewals-manage',              [RenewalController::class, 'index'])->name('renewals-manage');
Route::post('/renewals-manage',             [RenewalController::class, 'store'])->name('renewals-manage.store');
Route::post('/renewals-manage/update',      [RenewalController::class, 'update'])->name('renewals-manage.update');
Route::delete('/renewals-manage',           [RenewalController::class, 'destroy'])->name('renewals-manage.delete');
Route::get('/renewals-manage/{id}/detail',  [RenewalController::class, 'detail'])->name('renewals-manage.detail');

Route::get('/monthly-fee', [AdminController::class, 'monthlyFee'])->name('monthly-fee');
Route::put('/monthly-fee', [AdminController::class, 'updateMonthlyFee'])->name('monthly-fee.update');

Route::get('/expenses', [AdminController::class, 'expenses'])->name('expenses');
Route::post('/expenses', [AdminController::class, 'storeExpense'])->name('expenses.store');
Route::post('/expenses/update', [AdminController::class, 'updateExpense'])->name('expenses.update');
Route::delete('/expenses', [AdminController::class, 'deleteExpense'])->name('expenses.delete');
Route::get('/expenses/{id}/detail', [AdminController::class, 'expenseDetail'])->name('expenses.detail');
        
        
        

        Route::get('/logout', [AdminController::class, 'logout'])->name('logout');

        // ── BULK IMPORT ──
        Route::get(
            '/bulk-import',
            [AdminController::class, 'bulkImport']
        )
            ->name('bulk-import');
        Route::get(
            '/bulk-import/template',
            [AdminController::class, 'downloadTemplate']
        )
            ->name('bulk-import.template');
        Route::post(
            '/bulk-import',
            [AdminController::class, 'processBulkImport']
        )
            ->name('bulk-import.process');
    });

Route::prefix('district-admin')
    ->name('district-admin.')
    ->middleware(['district_admin'])
    ->group(function () {
 
        Route::get('/dashboard', [DistrictAdminController::class, 'dashboard'])->name('dashboard');
 
        Route::get('/units', [DistrictAdminController::class, 'units'])->name('units');
        Route::post('/units', [DistrictAdminController::class, 'storeUnit'])->name('units.store');
        Route::put('/units', [DistrictAdminController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units', [DistrictAdminController::class, 'deleteUnit'])->name('units.delete');
 
        Route::get('/unit-admins', [DistrictAdminController::class, 'unitAdmins'])->name('unit-admins');
        Route::post('/unit-admins', [DistrictAdminController::class, 'storeUnitAdmin'])->name('unit-admins.store');
        Route::post('/unit-admins/update', [DistrictAdminController::class, 'updateUnitAdmin'])->name('unit-admins.update');
        Route::post('/unit-admins/reset-password', [DistrictAdminController::class, 'resetUnitAdminPassword'])->name('unit-admins.reset-password');
        Route::post('/unit-admins/toggle-status', [DistrictAdminController::class, 'toggleUnitAdminStatus'])->name('unit-admins.toggle-status');
        Route::delete('/unit-admins', [DistrictAdminController::class, 'deleteUnitAdmin'])->name('unit-admins.delete');
 
        Route::get('/members', [DistrictAdminController::class, 'members'])->name('members');
        Route::post('/members', [DistrictAdminController::class, 'storeMember'])->name('members.store');
        Route::get('/members/{id}/detail', [DistrictAdminController::class, 'memberDetail'])->name('members.detail');
        Route::post('/members/approve', [DistrictAdminController::class, 'approveMember'])->name('members.approve');
        Route::post('/members/reject', [DistrictAdminController::class, 'rejectMember'])->name('members.reject');
 
        Route::get('/bulk-import', [DistrictAdminController::class, 'bulkImport'])->name('bulk-import');
        Route::get('/bulk-import/template', [DistrictAdminController::class, 'downloadTemplate'])->name('bulk-import.template');
        Route::post('/bulk-import', [DistrictAdminController::class, 'processBulkImport'])->name('bulk-import.process');
        
                // ── NEWS ──
        Route::get('/news', [DistrictAdminContentController::class, 'news'])->name('news');
        Route::post('/news', [DistrictAdminContentController::class, 'storeNews'])->name('news.store');
        Route::post('/news/update', [DistrictAdminContentController::class, 'updateNews'])->name('news.update');
        Route::post('/news/toggle-status', [DistrictAdminContentController::class, 'toggleNewsStatus'])->name('news.toggle-status');
        Route::delete('/news', [DistrictAdminContentController::class, 'deleteNews'])->name('news.delete');

        // ── EVENTS ──
        Route::get('/events', [DistrictAdminContentController::class, 'events'])->name('events');
        Route::post('/events', [DistrictAdminContentController::class, 'storeEvent'])->name('events.store');
        Route::post('/events/update', [DistrictAdminContentController::class, 'updateEvent'])->name('events.update');
        Route::post('/events/status', [DistrictAdminContentController::class, 'updateEventStatus'])->name('events.status');
        Route::delete('/events', [DistrictAdminContentController::class, 'deleteEvent'])->name('events.delete');
        Route::get('/events/{id}/detail', [DistrictAdminContentController::class, 'eventDetail'])->name('events.detail');
        Route::get('/events/{id}/registrations', [DistrictAdminContentController::class, 'eventRegistrations'])->name('events.registrations');

        // ── FUNDRAISING ──
        Route::get('/fundraising', [DistrictAdminContentController::class, 'fundraising'])->name('fundraising');
        Route::post('/fundraising', [DistrictAdminContentController::class, 'storeFundraisingCampaign'])->name('fundraising.store');
        Route::post('/fundraising/update', [DistrictAdminContentController::class, 'updateFundraisingCampaign'])->name('fundraising.update');
        Route::post('/fundraising/status', [DistrictAdminContentController::class, 'updateFundraisingStatus'])->name('fundraising.status');
        Route::delete('/fundraising', [DistrictAdminContentController::class, 'deleteFundraisingCampaign'])->name('fundraising.delete');
        Route::get('/fundraising/{id}/detail', [DistrictAdminContentController::class, 'fundraisingCampaignDetail'])->name('fundraising.detail');

        // ── COMMITTEES ──
        Route::get('/committees', [DistrictAdminContentController::class, 'committees'])->name('committees');
        Route::post('/committees', [DistrictAdminContentController::class, 'storeCommittee'])->name('committees.store');
        Route::post('/committees/update', [DistrictAdminContentController::class, 'updateCommittee'])->name('committees.update');
        Route::post('/committees/toggle-status', [DistrictAdminContentController::class, 'toggleCommitteeStatus'])->name('committees.toggle');
        Route::delete('/committees', [DistrictAdminContentController::class, 'deleteCommittee'])->name('committees.delete');
        Route::get('/committees/{id}/detail', [DistrictAdminContentController::class, 'committeeDetail'])->name('committees.detail');
        Route::get('/committees/{id}/members', [DistrictAdminContentController::class, 'committeeMembers'])->name('committees.members');
        Route::post('/committees/members/add', [DistrictAdminContentController::class, 'addCommitteeMember'])->name('committees.members.add');
        Route::post('/committees/members/update-role', [DistrictAdminContentController::class, 'updateCommitteeMemberRole'])->name('committees.members.update-role');
        Route::post('/committees/members/remove', [DistrictAdminContentController::class, 'removeCommitteeMember'])->name('committees.members.remove');

        // ── SCHEMES ──
        Route::get('/schemes', [DistrictAdminContentController::class, 'schemes'])->name('schemes');
        Route::post('/schemes', [DistrictAdminContentController::class, 'storeScheme'])->name('schemes.store');
        Route::post('/schemes/update', [DistrictAdminContentController::class, 'updateScheme'])->name('schemes.update');
        Route::post('/schemes/toggle-status', [DistrictAdminContentController::class, 'toggleSchemeStatus'])->name('schemes.toggle');
        Route::delete('/schemes', [DistrictAdminContentController::class, 'deleteScheme'])->name('schemes.delete');
        Route::get('/schemes/{id}/detail', [DistrictAdminContentController::class, 'schemeDetail'])->name('schemes.detail');
        Route::get('/schemes/{id}/payers', [DistrictAdminContentController::class, 'schemePayers'])->name('schemes.payers');

        // ── OTHER FEES ──
        Route::get('/other-fees', [DistrictAdminContentController::class, 'otherFees'])->name('other-fees');
        Route::post('/other-fees', [DistrictAdminContentController::class, 'storeOtherFee'])->name('other-fees.store');
        Route::post('/other-fees/update', [DistrictAdminContentController::class, 'updateOtherFee'])->name('other-fees.update');
        Route::post('/other-fees/toggle-status', [DistrictAdminContentController::class, 'toggleOtherFeeStatus'])->name('other-fees.toggle');
        Route::delete('/other-fees', [DistrictAdminContentController::class, 'deleteOtherFee'])->name('other-fees.delete');
        Route::get('/other-fees/{id}/detail', [DistrictAdminContentController::class, 'otherFeeDetail'])->name('other-fees.detail');
        Route::get('/other-fees/{id}/payers', [DistrictAdminContentController::class, 'otherFeePayers'])->name('other-fees.payers');

        // ── GALLERY ──
        Route::get('/gallery', [DistrictAdminContentController::class, 'gallery'])->name('gallery');
        Route::post('/gallery', [DistrictAdminContentController::class, 'storeGalleryImages'])->name('gallery.store');
        Route::post('/gallery/toggle', [DistrictAdminContentController::class, 'toggleGalleryImageStatus'])->name('gallery.toggle');
        Route::delete('/gallery', [DistrictAdminContentController::class, 'deleteGalleryImage'])->name('gallery.delete');

        // ── EXPENSES ──
        Route::get('/expenses', [DistrictAdminContentController::class, 'expenses'])->name('expenses');
        Route::post('/expenses', [DistrictAdminContentController::class, 'storeExpense'])->name('expenses.store');
        Route::post('/expenses/update', [DistrictAdminContentController::class, 'updateExpense'])->name('expenses.update');
        Route::delete('/expenses', [DistrictAdminContentController::class, 'deleteExpense'])->name('expenses.delete');
        Route::get('/expenses/{id}/detail', [DistrictAdminContentController::class, 'expenseDetail'])->name('expenses.detail');
        
                // ── RENEWALS ──
        Route::get('/renewals-manage',             [DistrictRenewalController::class, 'index'])->name('renewals-manage');
        Route::post('/renewals-manage',            [DistrictRenewalController::class, 'store'])->name('renewals-manage.store');
        Route::post('/renewals-manage/update',     [DistrictRenewalController::class, 'update'])->name('renewals-manage.update');
        Route::delete('/renewals-manage',          [DistrictRenewalController::class, 'destroy'])->name('renewals-manage.delete');
        Route::get('/renewals-manage/{id}/detail', [DistrictRenewalController::class, 'detail'])->name('renewals-manage.detail');

        // ── REPORTS ──
        Route::get('/reports',          [DistrictAdminReportController::class, 'index'])->name('reports');
        Route::get('/reports/preview',  [DistrictAdminReportController::class, 'preview'])->name('reports.preview');
        Route::get('/reports/download', [DistrictAdminReportController::class, 'download'])->name('reports.download');
 
        Route::get('/payments', [DistrictAdminController::class, 'payments'])->name('payments');
 
        Route::get('/change-password', [DistrictAdminController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [DistrictAdminController::class, 'changePassword'])->name('change-password.post');
        
        Route::delete('/members/delete', [DistrictAdminController::class, 'deleteMember'])->name('members.delete');
 
        Route::get('/logout', [DistrictAdminController::class, 'logout'])->name('logout');
    });
    
// ─────────────────────────────────
// PROTECTED UNIT ADMIN ROUTES
// ─────────────────────────────────
Route::prefix('unit-admin')
    ->name('unit-admin.')
    ->middleware(['unit_admin'])
    ->group(function () {
        Route::get('/reports', [UnitAdminReportController::class, 'index'])->name('reports');
        Route::get('/reports/preview', [UnitAdminReportController::class, 'preview'])->name('reports.preview');
        Route::get('/reports/download', [UnitAdminReportController::class, 'download'])->name('reports.download');


        Route::get('/dashboard', [UnitAdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/members', [UnitAdminController::class, 'members'])->name('members');
        Route::post('/members', [UnitAdminController::class, 'storeMember'])->name('members.store');
        Route::get('/members/{id}/detail', [UnitAdminController::class, 'memberDetail'])->name('members.detail');
        Route::post('/members/approve', [UnitAdminController::class, 'approveMember'])->name('members.approve');
        Route::post('/members/reject', [UnitAdminController::class, 'rejectMember'])->name('members.reject');
        Route::post('/members/import-csv', [UnitAdminController::class, 'importMembersCsv'])->name('members.import-csv');
        Route::get('/members/csv-template', [UnitAdminController::class, 'downloadCsvTemplate'])->name('members.csv-template');

        // ── NEWS (unit admin can create/manage) ──
        Route::get('/news', [UnitAdminController::class, 'news'])->name('news');
        Route::post('/news', [UnitAdminController::class, 'storeNews'])->name('news.store');
        Route::post('/news/update', [UnitAdminController::class, 'updateNews'])->name('news.update');
        Route::post('/news/toggle-status', [UnitAdminController::class, 'toggleNewsStatus'])->name('news.toggle-status');
        Route::delete('/news', [UnitAdminController::class, 'deleteNews'])->name('news.delete');

        // ── EVENTS (unit admin can create/manage) ──
        Route::get('/events', [UnitAdminController::class, 'events'])->name('events');
        Route::post('/events', [UnitAdminController::class, 'storeEvent'])->name('events.store');
        Route::post('/events/update', [UnitAdminController::class, 'updateEvent'])->name('events.update');
        Route::post('/events/status', [UnitAdminController::class, 'updateEventStatus'])->name('events.status');
        Route::delete('/events', [UnitAdminController::class, 'deleteEvent'])->name('events.delete');
        Route::get('/events/{id}/detail', [UnitAdminController::class, 'eventDetail'])->name('events.detail');
        Route::get('/events/{id}/registrations', [UnitAdminController::class, 'eventRegistrations'])->name('events.registrations');


        Route::get('/change-password', [UnitAdminController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [UnitAdminController::class, 'changePassword'])->name('change-password.post');
        
        Route::get('/payments', [UnitAdminController::class, 'payments'])->name('payments');

        Route::get('/logout', [UnitAdminController::class, 'logout'])->name('logout');

        // ── BULK IMPORT ──
        Route::get(
            '/bulk-import',
            [UnitAdminController::class, 'bulkImport']
        )
            ->name('bulk-import');
        Route::get(
            '/bulk-import/template',
            [UnitAdminController::class, 'downloadTemplate']
        )
            ->name('bulk-import.template');
        Route::post(
            '/bulk-import',
            [UnitAdminController::class, 'processBulkImport']
        )
            ->name('bulk-import.process');
            
            
            
            Route::get('/fundraising', [UnitAdminController::class, 'fundraising'])->name('fundraising');
Route::post('/fundraising/store', [UnitAdminController::class, 'storeFundraisingCampaign'])->name('fundraising.store');
Route::post('/fundraising/update', [UnitAdminController::class, 'updateFundraisingCampaign'])->name('fundraising.update');
Route::post('/fundraising/status', [UnitAdminController::class, 'updateFundraisingStatus'])->name('fundraising.status');
Route::delete('/fundraising/delete', [UnitAdminController::class, 'deleteFundraisingCampaign'])->name('fundraising.delete');
Route::get('/fundraising/{id}/detail', [UnitAdminController::class, 'fundraisingCampaignDetail'])->name('fundraising.detail');


        Route::get('/committees',                     [UnitAdminController::class, 'committees'])->name('committees');
        Route::post('/committees/store',               [UnitAdminController::class, 'storeCommittee'])->name('committees.store');
        Route::post('/committees/update',              [UnitAdminController::class, 'updateCommittee'])->name('committees.update');
        Route::post('/committees/toggle-status',       [UnitAdminController::class, 'toggleCommitteeStatus'])->name('committees.toggle');
        Route::post('/committees/delete',              [UnitAdminController::class, 'deleteCommittee'])->name('committees.delete');
        Route::get('/committees/{id}/detail',          [UnitAdminController::class, 'committeeDetail'])->name('committees.detail');
        Route::get('/committees/{id}/members',         [UnitAdminController::class, 'committeeMembers'])->name('committees.members');
        Route::post('/committees/members/add',         [UnitAdminController::class, 'addCommitteeMember'])->name('committees.members.add');
        Route::post('/committees/members/update-role', [UnitAdminController::class, 'updateCommitteeMemberRole'])->name('committees.members.update-role');
        Route::post('/committees/members/remove',      [UnitAdminController::class, 'removeCommitteeMember'])->name('committees.members.remove');
        
        Route::get('/gallery',        [UnitAdminController::class, 'gallery'])->name('gallery');
        Route::post('/gallery/store', [UnitAdminController::class, 'storeGalleryImages'])->name('gallery.store');
        Route::put('/gallery/toggle', [UnitAdminController::class, 'toggleGalleryImageStatus'])->name('gallery.toggle');
        Route::delete('/gallery',     [UnitAdminController::class, 'deleteGalleryImage'])->name('gallery.delete');
        
        Route::get('/other-fees', [UnitAdminController::class, 'otherFees'])->name('other-fees');
Route::post('/other-fees', [UnitAdminController::class, 'storeOtherFee'])->name('other-fees.store');
Route::put('/other-fees', [UnitAdminController::class, 'updateOtherFee'])->name('other-fees.update');
Route::put('/other-fees/toggle', [UnitAdminController::class, 'toggleOtherFeeStatus'])->name('other-fees.toggle');
Route::delete('/other-fees', [UnitAdminController::class, 'deleteOtherFee'])->name('other-fees.delete');
Route::get('/other-fees/{id}/detail', [UnitAdminController::class, 'otherFeeDetail'])->name('other-fees.detail');
Route::get('/other-fees/{id}/payers', [UnitAdminController::class, 'otherFeePayers'])->name('other-fees.payers');

Route::get('/schemes', [UnitAdminController::class, 'schemes'])->name('schemes');
Route::post('/schemes/store', [UnitAdminController::class, 'storeScheme'])->name('schemes.store');
Route::put('/schemes/update', [UnitAdminController::class, 'updateScheme'])->name('schemes.update');
Route::get('/schemes/{id}/detail', [UnitAdminController::class, 'schemeDetail']);
Route::put('/schemes/toggle', [UnitAdminController::class, 'toggleSchemeStatus'])->name('schemes.toggle');
Route::delete('/schemes/delete', [UnitAdminController::class, 'deleteScheme'])->name('schemes.delete');
Route::get('/schemes/{id}/payers', [UnitAdminController::class, 'schemePayers'])->name('schemes.payers');

Route::get('/renewals-manage',              [UnitAdminRenewalController::class, 'index'])->name('renewals-manage');
Route::post('/renewals-manage',             [UnitAdminRenewalController::class, 'store'])->name('renewals-manage.store');
Route::post('/renewals-manage/update',      [UnitAdminRenewalController::class, 'update'])->name('renewals-manage.update');
Route::delete('/renewals-manage',           [UnitAdminRenewalController::class, 'destroy'])->name('renewals-manage.delete');
Route::get('/renewals-manage/{id}/detail',  [UnitAdminRenewalController::class, 'detail'])->name('renewals-manage.detail');


Route::get('/reports', [UnitAdminReportController::class, 'index'])->name('reports');
Route::get('/reports/preview', [UnitAdminReportController::class, 'preview']);
Route::get('/reports/download', [UnitAdminReportController::class, 'download'])->name('reports.download');

Route::get('/expenses',        [UnitAdminController::class, 'expenses'])->name('expenses');
Route::post('/expenses/store', [UnitAdminController::class, 'storeExpense'])->name('expenses.store');
Route::post('/expenses/update',[UnitAdminController::class, 'updateExpense'])->name('expenses.update');
Route::delete('/expenses/delete',[UnitAdminController::class, 'deleteExpense'])->name('expenses.delete');
Route::get('/expenses/{id}/detail', [UnitAdminController::class, 'expenseDetail'])->name('expenses.detail');

Route::delete('/members/delete', [UnitAdminController::class, 'deleteMember'])->name('members.delete');
        
        
    });