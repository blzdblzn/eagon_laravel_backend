<?php
use App\Http\Controllers;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\EmailverificationController;
use App\Http\Request\Auth\EmailverificationRequest;
use App\Http\Request\Auth\EmployeeRequest;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
   
    return $request->user();

});
Route::get('/test', function(){
    return response([
        'message'=>'Api is working'
    ],200);
});
Route::post('register', [AuthenticationController::class, 'register']);

Route::post('login', [AuthenticationController::class, 'login']);

 Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('email-verification', [EmailverificationController::class, 'email_verification']);
    Route::post('profile-route', [ProfileController::class, 'profile_method']);
    Route::post('get-location', [AttendanceController::class, 'getUserIP']);
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::post('profile', [EmployeeController::class, 'profile']);
    Route::post('tester', [AttendanceController::class, 'tester']);
    Route::post('/employees/{employee_id}', [EmployeeController::class, 'profile']);
    Route::get('/attendance', [AttendanceController::class, 'delete']);
    Route::post('register', [AttendanceController::class, 'create']);
    });


Route::post('/attendance/location', [AttendanceController::class, 'location']);
Route::get('/attendance', [AttendanceController::class, 'index']);

Route::put('/attendance/update/{attendance_id}', [AttendanceController::class, 'update']);



    
