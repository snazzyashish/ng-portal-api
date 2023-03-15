<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DashboardController; 
use App\Http\Controllers\FileUploadController; 
use App\Http\Controllers\FileController; 
use App\Http\Controllers\StoreController; 
use App\Http\Controllers\StaffController; 
use App\Http\Controllers\StoreCredentialController; 
use App\Http\Controllers\WalletController; 
use App\Http\Controllers\FbCredentialController; 
use App\Http\Controllers\GmailCredentialController; 
use App\Http\Controllers\VpnCredentialController; 
use App\Http\Controllers\ReportController; 
use App\Http\Controllers\StoreBalanceController; 
use App\Http\Controllers\AnnouncementController; 
use App\Http\Controllers\GamePointsController; 
use App\Http\Controllers\WalletAuditController; 

 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);
Route::post('/get-user', [UserController::class, 'getUser']);
Route::post('/save-user-profile', [UserController::class, 'saveUserProfile']);
Route::post('/save-user-security', [UserController::class, 'saveUserSecurity']);
Route::post('/upload-file', [UserController::class, 'uploadFile']);
Route::get('/user/list', [UserController::class, 'list']);
Route::post('/user/save', [UserController::class, 'save']);
Route::post('/user/check-user', [UserController::class, 'checkUser']);
Route::post('/user/reset-password', [UserController::class, 'resetPassword']);
Route::post('/user/delete', [UserController::class, 'delete']);



Route::post('/dashboard/get-dashboard-data', [DashboardController::class, 'list']);
Route::post('/dashboard/get-labels', [DashboardController::class, 'getLabels']);

Route::get('/group/list', [GroupController::class, 'list']);
Route::post('/group/save', [GroupController::class, 'save']);
Route::post('/group/delete', [GroupController::class, 'delete']);


Route::get('/transaction/list', [TransactionController::class, 'list']);
Route::post('/transaction/save', [TransactionController::class, 'save']);
Route::post('/transaction/players', [TransactionController::class, 'getPlayerNames']);
Route::post('/transaction/delete', [TransactionController::class, 'delete']);
Route::post('/transaction/recover', [TransactionController::class, 'recover']);
Route::post('/transaction/permanentDelete', [TransactionController::class, 'permanentDelete']);
Route::post('/transaction/group-summary', [TransactionController::class, 'getGroupTransactionSummary']);

Route::get('/schedule/list', [ScheduleController::class, 'list']);
Route::post('/schedule/save', [ScheduleController::class, 'save']);
Route::post('/schedule/delete', [ScheduleController::class, 'delete']);

Route::get('/file/list', [FileController::class, 'list']);
Route::post('/file/upload', [FileController::class, 'uploadFile']);
Route::post('/file/delete', [FileController::class, 'delete']);



Route::get('/store/list', [StoreController::class, 'list']);
Route::post('/store/save', [StoreController::class, 'save']);
Route::post('/store/delete', [StoreController::class, 'delete']);

Route::get('/staff/list', [StaffController::class, 'list']);
Route::post('/staff/save', [StaffController::class, 'save']);
Route::post('/staff/delete', [StaffController::class, 'delete']);

Route::get('/store-credentials/list', [StoreCredentialController::class, 'list']);
Route::post('/store-credentials/save', [StoreCredentialController::class, 'save']);
Route::post('/store-credentials/delete', [StoreCredentialController::class, 'delete']);

Route::get('/wallet/list', [WalletController::class, 'list']);
Route::post('/wallet/save', [WalletController::class, 'save']);
Route::post('/wallet/delete', [WalletController::class, 'delete']);

Route::get('/fb-cred/list', [FbCredentialController::class, 'list']);
Route::post('/fb-cred/save', [FbCredentialController::class, 'save']);
Route::post('/fb-cred/delete', [FbCredentialController::class, 'delete']);

Route::get('/gmail-cred/list', [GmailCredentialController::class, 'list']);
Route::post('/gmail-cred/save', [GmailCredentialController::class, 'save']);
Route::post('/gmail-cred/delete', [GmailCredentialController::class, 'delete']);

Route::get('/vpn-cred/list', [VpnCredentialController::class, 'list']);
Route::post('/vpn-cred/save', [VpnCredentialController::class, 'save']);
Route::post('/vpn-cred/delete', [VpnCredentialController::class, 'delete']);

Route::get('/report/list', [ReportController::class, 'list']);

Route::get('/store-recharge/list', [StoreBalanceController::class, 'list']);
Route::post('/store-recharge/save', [StoreBalanceController::class, 'save']);
Route::post('/store-recharge/delete', [StoreBalanceController::class, 'delete']);

Route::get('/announcement/list', [AnnouncementController::class, 'list']);
Route::post('/announcement/save', [AnnouncementController::class, 'save']);
Route::post('/announcement/delete', [AnnouncementController::class, 'delete']);
Route::post('/announcement/count', [AnnouncementController::class, 'count']);
Route::post('/announcement/update-seen', [AnnouncementController::class, 'updateSeen']);

Route::get('/game-point/list', [GamePointsController::class, 'list']);
Route::post('/game-point/save', [GamePointsController::class, 'save']);
Route::post('/game-point/delete', [GamePointsController::class, 'delete']);

Route::get('/wallet-audit/list', [WalletAuditController::class, 'list']);
Route::post('/wallet-audit/save', [WalletAuditController::class, 'save']);
Route::post('/wallet-audit/delete', [WalletAuditController::class, 'delete']);



Route::get('/session/set', [UserController::class, 'setSession']);
Route::get('/session/get', [UserController::class, 'getSession']);







