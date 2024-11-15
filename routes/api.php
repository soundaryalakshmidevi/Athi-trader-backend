<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoanDueController;
use App\Http\Controllers\LoanCategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ForgotPasswordController;

// Forgot-Password
// Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/send-otp', [ForgotPasswordController::class, 'sendOTP']);

Route::post('/verify-account', [ForgotPasswordController::class, 'verifyOTP']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::post('/send-otp_with_phone', [ForgotPasswordController::class, 'sendOTPWithPhone']);
// User Login
Route::post('/login', [AuthController::class, 'login']);

// Secure routes using JWT middleware
Route::middleware(['auth.jwt'])->group(function () {
    
    Route::get('/employees', [AuthController::class, 'getEmployees']);
    Route::post('/employees_userid', [AuthController::class, 'getEmployees_userid']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/search', [AuthController::class, 'search']);
   
    Route::put('/user-update/{id}', [AuthController::class, 'update']);
    Route::get('/profile/{id}', [AuthController::class, 'profile']);
    Route::get('/all-users', [AuthController::class, 'allUsers']);
    Route::put('/employees/{id}', [AuthController::class, 'update']);
    Route::delete('/user/{id}', [AuthController::class, 'delete']);
    Route::get('/customer-count', [AuthController::class, 'getCustomersCount']);
    Route::get('/employee-count', [AuthController::class, 'getEmployeesCount']);
    
    Route::get('/customer', [AuthController::class, 'getcustomers']);
     Route::get('/customerindex', [AuthController::class, 'getCustomersindex']);
     Route::post('/customers_userid', [AuthController::class, 'getCustomersByUserId']);
     
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/test-token', [AuthController::class, 'testTokenGeneration']);
    
    Route::resource('/loan-due', LoanDueController::class);
    Route::post('/fetchcollection_By_Emp', [LoanDueController::class, 'fetchcollection_By_Emp']);
    Route::get('/totalloandue', [LoanDueController::class, 'TotalLoanDues']);
    Route::get('/fetch-loan-by-current-date', [LoanDueController::class,'fetchLoanByPaidDate']);
    Route::get('fetchLoanByEmpPaidDate/{user_id}', [LoanDueController::class, 'fetchLoanByEmpPaidDate']);
    Route::post('fetchLoanByEmpPaidDate', [LoanDueController::class, 'fetchLoanByEmpPaidDate']);
    
    Route::get('/any-loan/{loan_id}', [LoanDueController::class, 'getLoanByLoanid']);
    Route::get('fetchCitiesWithDueLoansArray', [LoanDueController::class, 'fetchCitiesWithDueLoansArray']);
    Route::get('fetchCitiesWithDueLoansJson', [LoanDueController::class, 'fetchCitiesWithDueLoansJson']);
    Route::get('fetchCitiesWithDueLoans/{city}', [LoanDueController::class, 'fetchCitiesWithDueLoansAndDetails']);
    Route::get('fetchCitiesWithDueLoans/{city}/{loan_id}', [LoanDueController::class, 'fetchCitiesWithDueLoansAndDetailsSingle']);
    Route::get('loans-date/{city}/{loan_id}', [LoanDueController::class, 'loansAndDetails']);
     Route::get('loans-date/{loan_id}', [LoanDueController::class, 'loansAndDetails_loanid']);
    Route::get('/loan-due', [LoanDueController::class, 'getLoanDueData']);
    Route::get('/loan-due-index', [LoanDueController::class, 'index']);
    Route::get('get-loan-due/{collection_by}', [LoanDueController::class, 'getLoanDueByCollection']);
    Route::put('/update-loan-pay/{loan_id}', [LoanDueController::class, 'updateCustLoanPayment']);
    
    Route::get('/alltodayloans', [LoanDueController::class, 'getAllLoans']);
    Route::get('/loan/{loan_id}/dues', [LoanDueController::class, 'fetchLoanById']);
    Route::put('/loan-due/{loanDue}', [LoanDueController::class, 'update']);
    Route::put('/loan_due/{loan_id}/{due_date}/{status}', [LoanDueController::class, 'updateID']);
    
    // Future routes
    Route::post('/loan-due/future-date', [LoanDueController::class, 'getLoanDueByFutureDate']);
    Route::post('/loan-due/date-city', [LoanDueController::class, 'fetchCitiesWithDueLoansFutureDate']);
    Route::post('/fetch-customers-future/{city}', [LoanDueController::class, 'fetchCitiesfutureDetails']);
    Route::post('/future-date/{city}/{loan_id}', [LoanDueController::class, 'fetchCityFutureDetailsSingle']);
    Route::put('/update-future-date/{loan_id}', [LoanDueController::class, 'updateFutureDetailsSingle']);
    Route::put('/update-future-date-future/{loan_id}', [LoanDueController::class, 'updateFutureDetailsSingles']);
    Route::put('/update-entry/{loan_id}', [LoanDueController::class, 'updateDueEntryDetails']);
    
    // Pending loans routes
    Route::post('/pending-loans-with-user-city', [LoanDueController::class, 'getPendingLoansWithUserAndCity']);
    Route::get('/pending-today', [LoanDueController::class, 'getPendingLoansToday']);
    Route::post('/getPendingLoansByDate', [LoanDueController::class, 'getPendingLoansByDate']);
    
    // Cities routes
    Route::get('/cities', [LoanDueController::class, 'getCityNames']);
    Route::post('/add-cities', [LoanDueController::class, 'cityAdd']);
    
    // Loan update routes
    Route::put('/update-loan', [LoanController::class, 'updateLoanDue']);
    
    Route::resource('/loan', LoanController::class);
    Route::get('/loandetails/{loan_id}', [LoanController::class, 'indexDetails']);

     Route::post('/loans', [LoanController::class, 'store']);
      Route::post('/storeimage',[LoanController::class,'storeimage']);
    Route::get('/indexweb', [LoanController::class, 'indexweb']);
    Route::get('/loans/count-pending-inprogress', [LoanController::class, 'countPendingAndInProgressLoans']);
    Route::get('/autoloanid', [LoanController::class, 'generateLoanId']);
   
    Route::put('/loan/{loan_id}/status', [LoanController::class, 'updateStatus']);
    Route::get('/loans/details', [LoanController::class, 'fetchLoansWithDetails']);
    
    Route::resource('/loan-category', LoanCategoryController::class);
    
    
    
//all history
Route::get('/alltransaction', [TransactionController::class, 'getAllTransactions']);

//current date history
Route::get('/alltransactionbycurrentdate', [TransactionController::class, 'getTransactionsByCurrentDate']);

//current date history for particular loan id
Route::get('/alltransactionbycurrentdate&loan_id', [TransactionController::class, 'getTransactionsByLoanIdAndToday']);

//history for particular loan date
Route::get('/alltransactionbyloandate', [TransactionController::class, 'getTransactionsByLoanDate']);

//history for particular loan date and loan_id
Route::get('/alltransactionbyloandateAndLoanid', [TransactionController::class, 'getTransactionsByLoanDate']);

//history for due_date
Route::get('/transactions/by-due-date', [YourController::class, 'getTransactionsByDueDate']);
});

