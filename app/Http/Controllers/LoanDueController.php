<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanDue;
use App\Models\Loan;
use App\Models\City;
use App\Models\User;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; 


class LoanDueController extends Controller
{
    
//fetch collection amount, collected amount, visited customers for particular employee
public function fetchcollection_By_Emp(Request $request)
{
    ob_clean();
    
    // Log the entire request payload for debugging
    Log::info("Request payload:", $request->all());

    // Retrieve user_id from request body
    $user_id = $request->input('user_id');  // Correctly accessing POST data
    Log::info("User ID: " . $user_id);  // Log to verify

    if (!$user_id) {
        return response()->json(['message' => 'User ID is required.'], 400);  // Respond with error if not found
    }

    // Proceed with your logic
    $currentDate = now()->format('Y-m-d');
    
    // Fetch loan dues where 'paid_on' matches current date and 'user_id' matches
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)
                        ->where('collection_by', $user_id) // Now filtering by user_id
                        ->get();

    if ($loan_dues->isEmpty()) {
        return response()->json(['message' => 'No loan dues found for user ' . $user_id], 200);
    }

    // Calculate total income and unique customers
    $total_due_amount = $loan_dues->sum('due_amount');
     $total_collected_amount = $loan_dues->sum('paid_amount');
    $total_customers = $loan_dues->unique('user_id')->count(); // Count unique customers based on user_id

    // Fetch distinct customers visited by the specified user_id
    $visited_customers = LoanDue::where('user_id', $user_id)
        ->distinct('user_id') // Get unique user_ids
        ->count('user_id'); // Count distinct user_ids

    return response()->json([
        'message' => 'Total income calculated',
        'total_due_amount' =>  $total_due_amount,
        'total_collected_amount' =>  $total_collected_amount,
        'total_customers' => $total_customers,
        'visited_customers' => $visited_customers, // New field for distinct customer visits
        'loan_dues' => $loan_dues,
    ], 200);
}


public function index()
{
    ob_clean();
    
    // Step 1: Join the 'users' table to fetch the 'user_name' based on 'user_id'
    $loan_due_entries = LoanDue::join('users', 'loan_due.user_id', '=', 'users.user_id')
                                ->select('loan_due.*', 'users.user_name')  // Select fields from both tables
                                ->orderBy('loan_due.id')              // Sort by loan_id
                                ->paginate(100);                           // Pagination, 100 entries per page

    // Step 2: Return paginated LoanDue entries with 'user_name' included
    return response()->json($loan_due_entries, 200);
}


public function getAllLoans()
{
    ob_clean();
    // Step 1: Fetch all loans from the loan table
    $loans = Loan::all(); // Get all loans from the Loan model

    // Step 2: Get the current date
    $currentDate = now()->toDateString();

    // Step 3: Initialize an array to hold the loan IDs and their category details
    $matchedLoans = [];

    // Step 4: Iterate over the loans to calculate due dates
    foreach ($loans as $loan) {
        // Step 4.1: Fetch category_id from the loan object
        $categoryId = $loan->category_id; // Assuming category_id is directly available on the loan object
        
        // Step 4.2: Fetch user details from the user table
        $userid = $loan->user_id;
        // Log::info('User ID: ' . $userid);
      
        
        if ($userid) {
            // Use where method to find the user
            $user = User::where('user_id', $userid)->first(); // Fetch user details by user_id
            if ($user) {
                $userid = $user->user_id;
                $username = $user->user_name;
                $usercity= $user->city;
                $useraddress= $user->address;
                
            }
        }

        // Step 4.3: Fetch category details from loan_category table
        $category = LoanCategory::find($categoryId); // Fetching category details using category_id

        // Step 4.4: Check if the category exists
        if ($category) {
            // Fetch category details into separate variables
            $categoryType = $category->category_type; // Assign category_type to a variable
            $duration = $category->duration; // Assign duration to a variable
            $interest_rate = $category->interest_rate;

            // Step 4.5: Generate due dates for the specified duration (duration * 7 days)
            $dueDates = [];
            for ($i = 1; $i <= $duration; $i++) {
                $dueDate = Carbon::parse($loan->loan_date)->addDays(7 * $i)->toDateString();
                $dueDates[] = $dueDate;
            }

            // Step 4.6: Check if the current date is in the array of due dates
            if (in_array($currentDate, $dueDates)) {
                // Step 4.7: Store the loan_id, user details, and category details if the current date matches
                $matchedLoans[] = [
                    'loan_id' => $loan->loan_id,
                    'userid'=>$userid,
                    'user_name' => $username,
                    'user_city'=>$usercity,
                    'user_address'=>$useraddress,
                    'category_details' => [
                        'category_type' => $categoryType, // Using the separate variable for category_type
                        'duration' => $duration, // Using the separate variable for duration
                        'interest_rate' => $interest_rate
                    ]
                ];
            }
        }
    }

    // Step 5: Return the matched loan IDs, user details, and category details as a JSON response
    return response()->json(['matched_loans' => $matchedLoans], 200); // Returning matched loans as JSON
}
public function fetchLoanById($loan_id)
{
    ob_clean();
    try {
        // Log the incoming request
        Log::info('Fetching loan dues for loan_id: ' . $loan_id);

        // Fetch all loan dues by loan_id
        $loan_dues = LoanDue::where('loan_id', $loan_id)->get();

        // Check if any loan dues exist
        if ($loan_dues->isEmpty()) {
            Log::warning('No loan dues found for loan_id: ' . $loan_id);
            return response()->json(['message' => 'No loan dues found for the provided loan ID'], 404);
        }

        // Log the found loan dues
        Log::info('Loan dues retrieved successfully: ', ['loan_dues' => $loan_dues]);

        // Return the list of loan dues
        return response()->json(['loan_dues' => $loan_dues], 200);

    } catch (\Exception $e) {
        // Log the error message
        Log::error('Error fetching loan dues: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while fetching loan dues.'], 500);
    }
}

public function fetchLoanByPaidDate()
{
    ob_clean();
    // Get the current date in Y-m-d format
    $currentDate = now()->format('Y-m-d');

    // Fetch all loan dues where the paid_on date matches the current date
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)->get();
 $total_income=0;
    // Check if any loan dues exist for the current date
    if ($loan_dues->isEmpty()) {
        
        return response()->json(['loan_dues' => '0',
        'total_income' => $total_income,]);
    }

    // Sum the paid_amount fields (total income)
    $total_income = $loan_dues->sum('paid_amount');

    // Sum the due_amount fields (total due amount)
    // $total_due_amount = $loan_dues->sum('due_amount');

    // Return the total income and total due amount along with the loan dues data
    return response()->json([
        'message' => 'Total income and due amount calculated',
        'total_income' => $total_income,
        
        
    ], 200);
}

public function TotalLoanDues()
{
    ob_clean();
    // Get the current date in Y-m-d format
    $currentDate = now()->format('Y-m-d');

    // Fetch all loan dues where the paid_on date matches the current date
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)->get();
    
     
$total_due_amount =0;
    // Check if any loan dues exist for the current date
    if ($loan_dues->isEmpty()) {
        return response()->json(['loan_dues' => '0',
         'total_due_amount' => $total_due_amount,]);
    }

 $total_due_amount = $loan_dues->sum('due_amount');
 
    // Return the loan dues data
    return response()->json([
        'message' => 'Loan dues fetched successfully',
        'loan_dues' => $loan_dues,
        'total_due_amount' => $total_due_amount,
    ], 200);
}


//Emp Loan_due Paid Amount 
public function fetchLoanByEmpPaidDate(Request $request)
{
    ob_clean();
    // Log the entire request payload for debugging
    Log::info("Request payload:", $request->all());

    // Retrieve collection_by from request body
    $collection_by = $request->input('collection_by');  // Correctly accessing POST data
    Log::info("Collection_by user ID: " . $collection_by);  // Log to verify

    if (!$collection_by) {
        return response()->json(['message' => 'User ID is required.'], 400);  // Respond with error if not found
    }

    // Proceed with your logic
    $currentDate = now()->format('Y-m-d');
    
    // Fetch loan dues where 'paid_on' matches current date and 'collection_by' matches
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)
                        ->where('collection_by', $collection_by)
                        ->get();

    if ($loan_dues->isEmpty()) {
        return response()->json(['message' => 'Loan dues achievable amount for user ' . $collection_by . ' is 0'], 200);
    }

    $total_income = $loan_dues->sum('due_amount');
    $total_customers = $loan_dues->unique('user_id')->count();

    return response()->json([
        'message' => 'Emp Total income calculated',
        'total_income' => $total_income,
        'total_customers' => $total_customers,
        'loan_dues' => $loan_dues,
    ], 200);
}

//Loan_id Pass Status[pending,unpaid] top 1
public function getLoanByLoanid($loan_id)
    {
        ob_clean();
        // Fetch the loan records with the given loan_id and status of 'pending' or 'unpaid'
        $loanRecords = LoanDue::where('loan_id', $loan_id)
            ->whereIn('status', ['pending', 'unpaid']) // Use whereIn for multiple statuses
            ->first(); // Use first() to get a single record or get() for multiple records

        // Check if records found
        if (!$loanRecords) {
            return response()->json(['message' => 'No loan records found for the given loan ID and status.'], 404);
        }

        // Return the records in JSON format
        return response()->json($loanRecords, 200);
    }

//Current_date Show All loan_due list Array Formet 
public function fetchCitiesWithDueLoansArray()
{
    ob_clean();
    try {
        // Get the current date and day
        $currentDate = now()->format('Y-m-d');
        $currentDay = now()->format('l');  // Day of the week (e.g., 'Monday')

        // Log the current date and day for debugging
        \Log::info('Current date: ' . $currentDate . ', Day: ' . $currentDay);

        // Fetch cities with users who have unpaid loans due today
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $currentDate)
            // ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the current date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Return the cities, total count, current day, and current date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,  // Total number of cities
            'currentDay' => $currentDay,    // Current day of the week
            'currentDate' => $currentDate,  // Current date
            'cities' => $cities             // List of cities
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}

//Current_date Show All loan_due list JSON Formet
public function fetchCitiesWithDueLoansJson()
{
    ob_clean();
    try {
        // Get the current date and day
        $currentDate = now()->format('Y-m-d');
        $currentDay = now()->format('l');  // Day of the week (e.g., 'Monday')

        // Log the current date and day for debugging
        \Log::info('Current date: ' . $currentDate . ', Day: ' . $currentDay);

        // Fetch cities with users who have unpaid loans due today
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $currentDate)
            ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the current date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Dynamically assign city keys (city1, city2, etc.)
        $formattedCities = [];
        foreach ($cities as $index => $city) {
            $formattedCities['city' . ($index + 1)] = $city;
        }

        // Return the cities, total count, current day, and current date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,  // Total number of cities
            'currentDay' => $currentDay,    // Current day of the week
            'currentDate' => $currentDate,  // Current date
            'cities' => $formattedCities    // List of cities in 'cityN' format
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}

// //Current_date & city  Show All loan_due & Customer list



//  public function fetchCitiesWithDueLoansAndDetails($city)
//     {
//         ob_clean();
//         try {
//             // Get the current date in Y-m-d format
//             $currentDate = now()->format('Y-m-d');

//             // Perform the JOIN query between loan_due, users, and loan tables, filtering by city
//             $loanDetails = DB::table('loan_due as ld')
//                 ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
//                 ->select(
//                     'u.user_id',
//                     'u.user_name',      // Ensure this is the correct column name for user names
//                     'u.address',
//                     'ld.loan_id',
//                     'ld.due_amount',
//                     'ld.paid_amount',
//                     'ld.paid_on',
//                     'ld.collection_by',
//                     'ld.due_date',
//                     'ld.status'
//                 )
//                 ->whereDate('ld.due_date', '=', $currentDate)
//                 //   ->whereIn('ld.status', ['unpaid', 'pending'])// Filter by current date
//                 // ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                
//                 ->where('u.city', '=', $city)                  // Filter by the passed city parameter
//                 ->get();

//             // Check if any loan details are found
//             if ($loanDetails->isEmpty()) {
//                 return response()->json(['message' => 'No users or loans found for due loans in the specified city on the current date'], 404);
//             }

//             // Prepare the response data
//             $responseData = [
//                 'message' => 'Users and loan details fetched successfully',
//                 'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
//                 'customers' => $loanDetails // Each customer details as an array
//             ];

//             // Return the fetched data in the response
//             return response()->json($responseData, 200);

//         } catch (\Exception $e) {
//             // Log the exception message for debugging
//             \Log::error('Error fetching loan details: ' . $e->getMessage());
//             return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
//         }
//     }

public function fetchCitiesWithDueLoansAndDetails($city)
{
    ob_clean();
    try {
        // Get the current date in Y-m-d format
        $currentDate = now()->format('Y-m-d');

        // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and calculating the total amount per loan_id and the due count
        $loanDetails = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
            ->select(
                'u.user_id',
                'u.user_name',       // Ensure this is the correct column name for user names
                'u.address',
                'ld.loan_id',
                'ld.due_amount',
                'ld.paid_amount',
                'ld.paid_on',
                'ld.collection_by',
                'ld.due_date',
                'ld.status',
                DB::raw('SUM(ld.due_amount) OVER(PARTITION BY ld.loan_id) as total_loan_due'), // Calculate the total due per loan_id
                DB::raw('ROW_NUMBER() OVER(PARTITION BY ld.loan_id ORDER BY ld.due_date) as due_number') // Add a sequential due count per loan_id
            )
            ->whereDate('ld.due_date', '=', $currentDate)          // Filter by current date
            ->where('u.city', '=', $city)                         // Filter by the passed city parameter
            ->get();

        // Check if any loan details are found
        if ($loanDetails->isEmpty()) {
            return response()->json(['message' => 'No users or loans found for due loans in the specified city on the current date'], 404);
        }

        // Prepare the response data
        $responseData = [
            'message' => 'Users and loan details fetched successfully',
            'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
            'customers' => $loanDetails // Each customer details as an array
        ];

        // Return the fetched data in the response
        return response()->json($responseData, 200);

    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error fetching loan details: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
    }
}


    
//Current_date & city  Show Particular loan_due Customer 
public function fetchCitiesWithDueLoansAndDetailsSingle($city, $loan_id)
    {
        ob_clean();
        try {
            // Get the current date in Y-m-d format
            $currentDate = now()->format('Y-m-d');

            // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->join('loan as l', 'l.loan_id', '=', 'ld.loan_id')    // Join with the loan table
                ->select(
                    'u.user_id',
                    'u.user_name', 
                    'u.address', 
                    'u.mobile_number',
                    'u.profile_photo',
                    'u.sign_photo',
                    'u.ref_name',
                    'u.ref_user_id',
                    'u.nominee_photo',
                    'u.nominee_sign',
                    'ld.due_amount', 
                    'ld.paid_amount', 
                    'ld.status', 
                    'l.image'          // Include the image field from the loan table
                )
                ->whereDate('ld.due_date', '=', $currentDate)  // Filter by current date
                // ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->where('ld.loan_id', '=', $loan_id)           // Filter by the passed loan_id parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'Users and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
   
public function loansAndDetails_loanid($loan_id, Request $request)
{
    ob_clean();
    try {
        // Validate the incoming date (optional field)
        $request->validate([
            'due_date' => 'nullable|date',  // Ensure the date is valid if provided
        ]);

        // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
        $loanDetails = DB::table('loan as l')
            ->join('users as u', 'u.user_id', '=', 'l.user_id')  // Join with the users table
            ->select(
                'u.user_id',
                'u.user_name', 
                'u.address', 
                 'u.city', 
                  'u.district', 
                'u.mobile_number',
                'u.profile_photo',
                'u.sign_photo',
                'u.ref_name',
                'u.ref_user_id',
                'u.nominee_photo',
                'u.nominee_sign',
                'l.image'         // Include the image field from the loan table
            )
            ->where('l.loan_id', '=', $loan_id)            // Filter by the passed loan_id parameter
            ->get();

        // Check if any loan details are found
        if ($loanDetails->isEmpty()) {
            return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
        }

        // Loop through each loan detail and convert images to Base64 if they exist
        foreach ($loanDetails as $loan) {
            // Convert loan image to Base64 if exists
            if ($loan->image) {
                $imagePath = public_path($loan->image); // Get full image path
                $loan->image = file_exists($imagePath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($imagePath)) : null;
            }

            // Convert profile photo to Base64 if exists
            if ($loan->profile_photo) {
                $profilePhotoPath = public_path($loan->profile_photo); // Get full image path
                $loan->profile_photo = file_exists($profilePhotoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($profilePhotoPath)) : null;
            }

            // Convert sign photo to Base64 if exists
            if ($loan->sign_photo) {
                $signPhotoPath = public_path($loan->sign_photo); // Get full image path
                $loan->sign_photo = file_exists($signPhotoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($signPhotoPath)) : null;
            }

            // Convert nominee photo to Base64 if exists
            if ($loan->nominee_photo) {
                $nomineePhotoPath = public_path($loan->nominee_photo); // Get full image path
                $loan->nominee_photo = file_exists($nomineePhotoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($nomineePhotoPath)) : null;
            }

            // Convert nominee sign to Base64 if exists
            if ($loan->nominee_sign) {
                $nomineeSignPath = public_path($loan->nominee_sign); // Get full image path
                $loan->nominee_sign = file_exists($nomineeSignPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($nomineeSignPath)) : null;
            }
        }

        // Prepare the response data
        $responseData = [
            'message' => 'Users and loan details fetched successfully',
            'customers' => $loanDetails // Each customer details as an array
        ];

        // Return the fetched data in the response
        return response()->json($responseData, 200);

    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error fetching loan details: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
    }
}


 
//Select any Date   Show Particular loan_due Customer 
public function loansAndDetails($city, $loan_id, Request $request)
{
    ob_clean();
    try {
        // Validate the incoming date (optional field)
        $request->validate([
            'due_date' => 'nullable|date',  // Ensure the date is valid if provided
        ]);

        // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
        $loanDetails = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
            ->join('loan as l', 'l.loan_id', '=', 'ld.loan_id')   // Join with the loan table
            ->select(
                'u.user_id',
                'u.user_name', 
                'u.address', 
                'u.mobile_number',
                'u.profile_photo',
                'u.sign_photo',
                'u.ref_name',
                'u.ref_user_id',
                'u.nominee_photo',
                'u.nominee_sign',
                'ld.due_amount', 
                'ld.paid_amount', 
                'ld.status', 
                'l.image'         // Include the image field from the loan table
            )
            ->where('u.city', '=', $city)                   // Filter by the passed city parameter
            ->where('ld.loan_id', '=', $loan_id)            // Filter by the passed loan_id parameter
            ->get();

        // Check if any loan details are found
        if ($loanDetails->isEmpty()) {
            return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
        }

        // Prepare the response data
        $responseData = [
            'message' => 'Users and loan details fetched successfully',
            'customers' => $loanDetails // Each customer details as an array
        ];

        // Return the fetched data in the response
        return response()->json($responseData, 200);

    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error fetching loan details: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
    }
}


// Update Current_date & city  Show Particular loan_due Customer 
public function updateEntryLoanDue(Request $request, $city, $loan_id)
{
    ob_clean();
    try {
        // Validate the incoming request data
        $request->validate([
            'paid_amount' => 'required|numeric',  // Ensure paid_amount is provided and is numeric
            'status' => 'required|string'          // Ensure status is provided and is a string
        ]);

        // Get the current date for the 'paid_on' field
        $currentDate = now()->format('Y-m-d');

        // Get the user_id from the session (assuming Auth is set up correctly)
        // Uncomment the line below if you're using authentication
        // $collection_by = Auth::user()->user_id;

        // Update the loan_due records where the city and loan_id match
        $updated = DB::table('loan_due')
            ->where('loan_id', '=', $loan_id)   // Filter by loan_id
            ->whereExists(function ($query) use ($city) {
                $query->select(DB::raw(1))
                      ->from('users as u')
                      ->whereRaw('u.user_id = loan_due.user_id')
                      ->where('u.city', '=', $city); // Ensure the user belongs to the specified city
            })
            ->update([
                'paid_amount' => $request->input('paid_amount'), // Update the paid_amount
                'paid_on' => $currentDate,                        // Set the current date for paid_on
                // 'collection_by' => $collection_by,             // Uncomment if you want to set collection_by
                'status' => $request->input('status')             // Update the status
            ]);

        // Check if any rows were updated
        if ($updated) {
            return response()->json([
                'message' => 'Loan due entry updated successfully',
                'city' => $city,
                'loan_id' => $loan_id
            ], 200);
        } else {
            return response()->json([
                'message' => 'No matching loan entries found for the specified city and loan ID',
                'city' => $city,
                'loan_id' => $loan_id
            ], 404);
        }
    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error updating loan due entry: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while updating loan due entry'], 500);
    }
}

//Total Emp getLoanDueData Details
public function getLoanDueData()
    {
        ob_clean();
        // Get current date
        $currentDate = date('Y-m-d');

        // Fetch total employee count
        $totalEmployees = DB::table('users')->where('user_type', 'employee')->count();

        // Fetch loan details
        $loanDetails = DB::select("
            SELECT 
                collection_by, 
                COUNT(*) AS loan_count, 
                SUM(due_amount) AS total_due_amount, 
                SUM(paid_amount) AS total_paid_amount
            FROM 
                loan_due 
            WHERE 
                due_date = DATE(CONVERT_TZ(NOW(), '+00:00', '+05:30')) 
            GROUP BY 
                collection_by
        ");

        // Initialize totals
        $totalDueAmount = 0;
        $totalPaidAmount = 0;
        $totalCustomers = 0; // Initialize total customers

        // Process loan details and calculate pending amounts
        $updatedLoanDetails = array_map(function ($loan) use (&$totalDueAmount, &$totalPaidAmount) {
            // Convert amounts to float for accurate calculations
            $loan->total_due_amount = (float)$loan->total_due_amount;
            $loan->total_paid_amount = (float)$loan->total_paid_amount;

            // Calculate total pending due amount
            $totalPendingDueAmount = $loan->total_due_amount - $loan->total_paid_amount;

            // Add to overall totals
            $totalDueAmount += $loan->total_due_amount;
            $totalPaidAmount += $loan->total_paid_amount;

            // Determine status
            $status = $totalPendingDueAmount > 0 ? "pending" : "paid";
            
            $username = DB::table('users')->where('user_id', $loan->collection_by)->value('user_name');

            return [
                'collection_by' => $loan->collection_by,
             'username'=>$username,

                'loan_count' => $loan->loan_count,
                'total_due_amount' => number_format($loan->total_due_amount, 2, '.', ''),
                'total_paid_amount' => number_format($loan->total_paid_amount, 2, '.', ''),
                'total_pending_due_amount' => number_format($totalPendingDueAmount, 2, '.', ''),
                'status' => $status,
            ];
        }, $loanDetails);

        // Calculate total pending due amount for the response
        $totalPendingDueAmountOverall = $totalDueAmount - $totalPaidAmount;

        // Calculate total customers as the sum of loan_count from loan details
        $totalCustomers = array_reduce($updatedLoanDetails, function ($carry, $loan) {
            return $carry + $loan['loan_count'];
        }, 0);

        // Prepare the final response
        $response = [
            'current_date' => $currentDate,
            'total_employees' => $totalEmployees,
            'total_customers' => $totalCustomers,
            'total_due_amount' => number_format($totalDueAmount, 2, '.', ''),
            'total_paid_amount' => number_format($totalPaidAmount, 2, '.', ''),
            'total_pending_due_amount' => number_format($totalPendingDueAmountOverall, 2, '.', ''),
            'loan_details' => $updatedLoanDetails,
        ];

        return response()->json($response);
    }
//Each Single Emp getLoanDueData Details    
public function getLoanDueByCollection($collection_by)
    {
        ob_clean();
        // Fetch loan details based on collection_by
        $loanDetails = LoanDue::where('collection_by', $collection_by)
            ->where('due_date', date('Y-m-d')) // Check for today's date
            ->get();

        // Prepare the response
        $response = [
            'collection_by' => $collection_by,
            'loan_details' => []
        ];

        foreach ($loanDetails as $loan) {
            // Calculate total pending due amount
            $totalPendingDueAmount = $loan->due_amount - $loan->paid_amount;

            // Get next due details if the status is unpaid
            if ($totalPendingDueAmount > 0) {
                $nextLoan = LoanDue::where('user_id', $loan->user_id)
                    ->where('status', 'unpaid')
                    ->orderBy('due_date', 'ASC')
                    ->first();

                $nextDueDate = $nextLoan ? $nextLoan->due_date : null;
                $nextDueAmount = $nextLoan ? ($nextLoan->due_amount + $totalPendingDueAmount) : 0;
            } else {
                $nextDueDate = null;
                $nextDueAmount = 0;
            }

            // Push loan details into response
            $response['loan_details'][] = [
                'loan_id' => $loan->loan_id,
                'user_id' => $loan->user_id,
                'due_date' => $loan->due_date,
                'due_amount' => $loan->due_amount,
                'paid_amount' => $loan->paid_amount,
                'total_pending_due_amount' => $totalPendingDueAmount,
                'status' => $totalPendingDueAmount === 0 ? 'paid' : 'pending',
                // 'next_due_date' => $nextDueDate,
                'next_due_amount' => $nextDueAmount
            ];
        }

        return response()->json($response);
    }



public function updateCustLoanPayment(Request $request, $loan_id)
{
    $request->validate([
        'due_date' => 'required|date',
        'collection_by' => 'required|string',
        'paid_amount' => 'required|numeric|min:0',
    ]);

    $due_date = $request->due_date;
    $collection_by = $request->collection_by;
    $paid_amount = $request->paid_amount;

    // Fetch the current loan due for the given due_date
    $currentLoan = LoanDue::where('loan_id', $loan_id)
        ->where('due_date', $due_date)
        ->first();

    if (!$currentLoan) {
        return response()->json(['message' => 'No loan due found for the given due date.'], 404);
    }

    $due_amount = $currentLoan->due_amount;

    // Check if the payment fully settles the due amount
    if ($paid_amount >= $due_amount) {
        // Full payment or overpayment, mark as paid
        $currentLoan->paid_amount = $due_amount;  // Fully paid
        $currentLoan->pending_amount = 0;         // No pending amount
        $currentLoan->status = 'paid';            // Mark as paid
        $balance_amount = $paid_amount - $due_amount; // Calculate any overpayment (if any)
    } else {
        // Partial payment, calculate pending amount
        $currentLoan->paid_amount = $paid_amount;
        $currentLoan->pending_amount = $due_amount - $paid_amount;
        $currentLoan->status = 'pending';         // Mark as pending
        $balance_amount = 0;                      // No balance, as it's a partial payment
    }

    $currentLoan->collection_by = $collection_by;
    $currentLoan->save();

    // Now, handle the next loan's next_amount if there's an overpayment
    if ($balance_amount > 0) {
        $nextLoan = LoanDue::where('loan_id', $loan_id)
            ->where('status', 'unpaid')
            ->where('due_date', '>', $due_date) // Find the next unpaid loan due after the current due date
            ->orderBy('due_date', 'asc')
            ->first();

        if ($nextLoan) {
            // Update next amount of the upcoming unpaid loan if balance exists
            $nextLoan->next_amount += $balance_amount; // Add the overpaid amount to the next loan
            $nextLoan->save();
        }
    }

    return response()->json(['message' => 'Payment processed successfully.']);
}



//**  Future
//Future date from the request Loan_due All Details
public function getLoanDueByFutureDate(Request $request)
    {
        ob_clean();
        // Validate that 'future_date' is required and is a valid date
        $request->validate([
            'future_date' => 'required|date|after:today', // Ensures the date is in the future
        ]);

        // Get the future date from the request
        $future_date = $request->input('future_date');

        // Fetch loan_due records for the given future date
        $loanDues = LoanDue::where('due_date', $future_date)->get();

        // Check if any loan due records are found
        if ($loanDues->isEmpty()) {
            return response()->json(['message' => 'No loan dues found for the given future date.'], 404);
        }

        // Return the loan due records in the response
        return response()->json([
            'message' => 'Loan dues for the future date retrieved successfully.',
            'loan_dues' => $loanDues
        ], 200);
    }
    
//Future date Only Cities List
// public function fetchCitiesWithDueLoansFutureDate(Request $request)
// {
//     ob_clean();
//     try {
//         // Validate the future date provided by the user
//         $request->validate([
//             'future_date' => 'required|date|after_or_equal:today'
//         ]);

//         // Get the user-provided future date
//         $futureDate = $request->input('future_date');
//         $futureDay = Carbon::parse($futureDate)->format('l');  // Day of the week (e.g., 'Monday')

//         // Log the future date and day for debugging
//         \Log::info('Future date: ' . $futureDate . ', Day: ' . $futureDay);

//         // Fetch cities with users who have unpaid loans due on the selected date
//         $cities = DB::table('loan_due as ld')
//             ->join('users as u', 'u.user_id', '=', 'ld.user_id')
//             ->whereDate('ld.due_date', '=', $futureDate)
//             ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
//             ->distinct()
//             ->pluck('u.city');  // Fetch distinct city names

//         // Check if any cities are found
//         if ($cities->isEmpty()) {
//             return response()->json(['message' => 'No cities found for users with due loans on the selected date'], 404);
//         }

//         // Count the total number of distinct cities
//         $totalCities = $cities->count();

//         // Dynamically assign city keys (city1, city2, etc.)
//         $formattedCities = [];
//         foreach ($cities as $index => $city) {
//             $formattedCities['city' . ($index + 1)] = $city;
//         }

//         // Return the cities, total count, future day, and future date
//         return response()->json([
//             'message' => 'Cities fetched successfully',
//             'totalCities' => $totalCities,   // Total number of cities
//             'futureDay' => $futureDay,       // Day of the week for the selected date
//             'futureDate' => $futureDate,     // Selected future date
//             'cities' => $formattedCities     // List of cities in 'cityN' format
//         ], 200);

//     } catch (\Exception $e) {
//         // Log the exception for debugging
//         \Log::error('Error fetching cities: ' . $e->getMessage());

//         return response()->json([
//             'message' => 'An error occurred while fetching cities'
//         ], 500);
//     }
// }
public function fetchCitiesWithDueLoansFutureDate(Request $request)
{
    ob_clean();
    try {
        // Validate the future date provided by the user (no 'after_or_equal:today' logic)
        $request->validate([
            'future_date' => 'required|date'
        ]);

        // Get the user-provided future date
        $futureDate = $request->input('future_date');
        $futureDay = Carbon::parse($futureDate)->format('l');  // Day of the week (e.g., 'Monday')

        // Log the future date and day for debugging
        \Log::info('Future date: ' . $futureDate . ', Day: ' . $futureDay);

        // Fetch cities with users who have unpaid loans due on the selected date
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $futureDate)
            ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the selected date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Dynamically assign city keys (city1, city2, etc.)
        $formattedCities = [];
        foreach ($cities as $index => $city) {
            $formattedCities['city' . ($index + 1)] = $city;
        }

        // Return the cities, total count, future day, and future date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,   // Total number of cities
            'futureDay' => $futureDay,       // Day of the week for the selected date
            'futureDate' => $futureDate,     // Selected future date
            'cities' => $formattedCities     // List of cities in 'cityN' format
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}


//Future date Only Cities use Customer List
public function fetchCitiesfutureDetails(Request $request, $city)
    {
        ob_clean();
        try {
            // Validate the future date provided by the user
            $request->validate([
                'future_date' => 'required|date|after_or_equal:today',
            ]);

            // Get the user-provided future date
            $futureDate = $request->input('future_date');

            // Log the future date and city for debugging
            \Log::info('Future date: ' . $futureDate . ', City: ' . $city);

            // Perform the JOIN query between loan_due and users tables, filtering by city and future date
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->select(
                    'u.user_id',
                    'u.user_name',      // Ensure this is the correct column name for user names
                    'u.address',
                    'ld.loan_id',
                    'ld.due_amount',
                    'ld.due_date',
                    'ld.status'
                )
                ->whereDate('ld.due_date', '=', $futureDate)  // Filter by future date
                ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for due loans in the specified city on the selected date'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'Users and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
    
//Future date Only Cities use Single Customer Details  
public function fetchCityFutureDetailsSingle(Request $request, $city, $loan_id)
    {
        ob_clean();
        try {
            // Validate the future date provided by the user
            $request->validate([
                'future_date' => 'required|date|after_or_equal:today',
            ]);

            // Get the user-provided future date
            $futureDate = $request->input('future_date');

            // Log the future date, city, and loan ID for debugging
            \Log::info('Future date: ' . $futureDate . ', City: ' . $city . ', Loan ID: ' . $loan_id);

            // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->join('loan as l', 'l.loan_id', '=', 'ld.loan_id')    // Join with the loan table
                ->select(
                    'u.user_id',
                    'u.user_name', 
                    'u.address', 
                    'u.mobile_number',
                    'u.profile_photo',
                    'u.sign_photo',
                    'u.ref_name',
                    'u.ref_user_id',
                    'u.nominee_photo',
                    'u.nominee_sign',
                    'ld.due_amount', 
                    'ld.paid_amount', 
                    'ld.status', 
                    'l.image'  // Include the image field from the loan table
                )
                ->whereDate('ld.due_date', '=', $futureDate)  // Filter by future date
                ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->where('ld.loan_id', '=', $loan_id)           // Filter by the passed loan_id parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'User and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
//Update Future Due Loan_id

// oct20function begin
public function updateFutureDetailsSingle(Request $request, $loan_id)
{
ob_clean();
    
    // Log request details
    Log::info("Processing loan payment for loan_id: $loan_id", ['request' => $request->all()]);

    // Validate the request
    $request->validate([
        'paid_on' => 'required|date',
        'paid_amount' => 'required|numeric|min:0',
        'due_date' => 'required|date',
        'collection_by' => 'required',
    ]);

    // Retrieve request data
    $paid_amount = $request->paid_amount;
    $due_date = $request->due_date;
    $paid_on = $request->paid_on;
    $collection_by = $request->collection_by;
    
$transaction = Transaction::insert([
    'loan_id' => $loan_id,
    'paid_amount' => $paid_amount, // Use variable directly, without quotes
    'due_date' => $due_date, 
    'collection_by' => $collection_by,
    'paid_on' => $paid_on, 
    'created_at' => now(),
    'updated_at' => now()
]);


    // Fetch the current loan record
    $currentLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('due_date', $due_date)
                          ->where('status', "unpaid")
                          ->first(); // Get the first record

    // Check if the loan record exists
    if (!$currentLoan) {
        $pendingLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('due_date', $due_date)
                          ->where('status', "pending")
                          ->first(); 
                          if(!$pendingLoan)
                          {
                               return response()->json(['message' => 'Loan already paid.'], 404);
                          }
                            $pendingLoan->paid_amount +=$paid_amount;
                            
                          if($pendingLoan->next_amount<=$paid_amount)
                          {
                               $pendingLoan->pending_amount =0;
                              $pendingLoan->status='paid';
                          }
                            else 
                                {
                                     $pendingLoan->pending_amount -= $paid_amount;
                                       $pendingLoan->next_amount = $pendingLoan->pending_amount;
                                } 
                                
                                $pendingLoan->save();
                                  return response()->json(['message' => 'Loan  Updated Successfully.'], 200);
                         
       
    }

 
                          
    // Update the loan record fields
    $currentLoan->paid_on = $paid_on;
    // $currentLoan->paid_amount = $paid_amount; 
    $currentLoan->collection_by = $collection_by;
    // $currentLoan->status = 'paid'; 
    $currentLoan->save();
        
        $sevenDaysBefore = Carbon::parse($due_date)->subDays(7);
        
         Log::info('Loan due records: ', ['$sevenDaysBefore' => $sevenDaysBefore]); 
         
        $currentLoanbeforeprocess = LoanDue::where('loan_id', $loan_id)
                      ->where('due_date', $sevenDaysBefore)
                      ->where('status',"unpaid")
                         ->get();
                          Log::info('Loan due days: ', ['$currentLoanbeforeprocess' => $currentLoanbeforeprocess->toArray()]);
                         
if(!$currentLoanbeforeprocess->isEmpty())
    {
        $currentLoanbefore = LoanDue::where('loan_id', $loan_id)
                          ->whereBetween('due_date', [$sevenDaysBefore, $due_date])
                          ->whereIn('status', ['paid', 'pending'])
                          ->get();
            
            Log::info('Loan due records: ', ['records' => $currentLoanbefore->toArray()]);
                      // Check if the result is empty
            if ($currentLoanbefore->isEmpty()) {
               return response()->json(['message' => "First work on unpaid due"], 404);
                
               
            } else {
                 $currentLoan = LoanDue::where('loan_id', $loan_id)
                              ->where('status', ['unpaid'])
                          ->first();
                          $currentLoan->paid_amount+=$paid_amount;
                          $currentLoan->save();
                // Process the loan if records are found
                $this->processCurrentLoan($currentLoan, $paid_amount, $loan_id);
                return;
                
            }
       
    }
else
{
   
    // Fetch current loan record by due_date
    $currentLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('due_date', $due_date)
                          ->first();

    if (!$currentLoan) {
        return response()->json(['message' => "No loan record found for loan_id: $loan_id and due_date: $due_date"], 404);
    }

    // Update paid amount in the current loan record
    $currentLoan->paid_amount += $paid_amount;
    $currentLoan->save();
    // Fetch record where status is pending
    $pendingLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('status', 'pending')
                          ->first();

    if ($pendingLoan) {
        // Handle logic for pending loan
        return $this->processPendingLoan($paid_amount, $loan_id);
    }
    else{
        return $this->processCurrentLoan($currentLoan,$currentLoan->paid_amount,$loan_id);
        
    }

     return response()->json(['message' => 'Loan payment processed successfully', 'loan' => $currentLoan]);
}
}

protected function processPendingLoan($paid_amount, $loan_id)
{
    
     $pendingLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('status', 'pending')
                          ->first();
               
                          
$extra_amount = 0;

if ($pendingLoan) {
    // Assign current variables
    $pending_due_amount = $pendingLoan->due_amount;
    $pending_paid_amount = $pendingLoan->paid_amount;
    $pending_next_amount = $pendingLoan->next_amount;
    $pending_pending_amount = $pendingLoan->pending_amount;

    Log::info("Pending amount for loan_id: $loan_id", ['pending_pending_amount' => $pending_pending_amount]);

    // Scenario 1: Paid amount == Next amount
    if ($pending_pending_amount == $paid_amount) {
        $pendingLoan->status = 'paid';
        $pendingLoan->save();
        Log::info("Loan fully paid for loan_id: $loan_id");

        // Fetch record where status is unpaid
        $unpaidLoan = LoanDue::where('loan_id', $loan_id)
                              ->where('status', 'unpaid')
                              ->first();

        if ($unpaidLoan) {
            // Update pending amount and status
            $unpaidLoan->pending_amount = $unpaidLoan->next_amount - $paid_amount;
            $unpaidLoan->status = 'pending';
            $unpaidLoan->save();

            Log::info("Updated unpaid loan", ['loan' => $unpaidLoan]);

            // Fetch record where status is unpaid again
            $unpaidLoannew = LoanDue::where('loan_id', $loan_id)
                                     ->where('status', 'unpaid')
                                     ->first();

            if ($unpaidLoannew) {
                Log::info("Calculating next amount", [
                    'pending_amount' => $unpaidLoan->pending_amount,
                    'current_next_amount' => $unpaidLoannew->due_amount,
                ]);

                // Update next amount
                $unpaidLoannew->next_amount = $unpaidLoan->pending_amount + $unpaidLoannew->due_amount;
                $unpaidLoannew->save();
                Log::info("Updated next amount for unpaid loan", ['next_amount' => $unpaidLoannew->next_amount]);
            }
        }
    }

    // Scenario 2: Next amount > Paid amount
    elseif ($pending_pending_amount > $paid_amount) {
        $pendingLoan->pending_amount = $pending_pending_amount - $paid_amount;
        $pendingLoan->status = 'pending';
        $pendingLoan->save();

        Log::info('Pending loan calculation', [
            'pending_pending_amount' => $pending_pending_amount,
            'paid_amount' => $paid_amount,
        ]);

        $this->processNextUnpaidLoan1($loan_id, $pendingLoan->pending_amount,$paid_amount);
    }

    // Scenario 3: Next amount < Paid amount (overpayment)
    elseif ($pending_pending_amount < $paid_amount) {
        $extra_amount = $paid_amount - $pending_pending_amount;
        $pendingLoan->pending_amount = 0;
        $pendingLoan->status = 'paid';
        $pendingLoan->save();

        Log::info("Loan overpaid, extra amount available", [
            'extra_amount' => $extra_amount,
            'loan_id' => $loan_id,
        ]);

        if ($extra_amount > 0) {
           
            $this->processPendingLoan($extra_amount, $loan_id);
              // Return extra amount in case of overpayment
        }
    }
}

else{
     $currentLoan = LoanDue::where('loan_id', $loan_id)
                          ->where('status', 'unpaid')
                          ->first();
                          
                     // Log the paid_amount
Log::info("processNextUnpaidLoan2 paid_amount", ['paid_amount' => $paid_amount]);

// Call the processNextUnpaidLoan2 method with the correct parameters
$this->processNextUnpaidLoan2($loan_id, $paid_amount);

    
}

return $extra_amount;

// Log:: info("$extra_amount",$extra_amount);
 
//   $pendingLoan = LoanDue::where('loan_id', $loan_id)
//                           ->where('status', 'unpaid')
//                           ->first();
           
//             Log::info("Calling processPendingLoan with extra amount", [
//                 'extra_amount' => $extra_amount,
//                 'loan_id' => $loan_id
//             ]);
        
//              $this->processNextUnpaidLoan2($loan_id, $extra_amount);
        
       

}

protected function processCurrentLoan($currentLoan, $paid_amount, $loan_id)
{
    // Similar to processPendingLoan but processing the current due record directly
    $current_next_amount = $currentLoan->next_amount;
    $current_paid_amount = $currentLoan->paid_amount;
    
Log::info("Current Loan Next Amount: {$currentLoan->next_amount}", ['next_amount' => $currentLoan->next_amount]);
Log::info("Current Loan Paid Amount: {$currentLoan->paid_amount}", ['paid_amount' => $currentLoan->paid_amount]);


    if ($current_next_amount == $current_paid_amount) {
        $currentLoan->pending_amount = 0;
        $currentLoan->status = 'paid';
        $currentLoan->save();

        Log::info("Loan fully paid for loan_id: $loan_id");
         $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();

    if ($nextLoan) {
        Log::info("Next loan found", ['next_loan_id' => $nextLoan->id]);  
        Log::info("Current due_amount", ['due_amount' => $nextLoan->due_amount]);

        // Ensure due_amount is treated as a float
        $dueAmount = (float) $nextLoan->due_amount;
       // Ensure amount_change is also a float

        // Update next_amount
        $nextLoan->next_amount = $dueAmount ;
            $nextLoan->save();
    }
    else{
        log:info("Process for last loan");
    }
    } elseif ($current_next_amount > $current_paid_amount) {
        $currentLoan->pending_amount = $current_next_amount - $current_paid_amount;
        $currentLoan->status = 'pending';
        $currentLoan->save();
         $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();
             if($nextLoan)
        $this->processNextUnpaidLoan1($loan_id, $currentLoan->pending_amount,$paid_amount);
        else
        {
                  $currentLoan->next_amount = $currentLoan->pending_amount;
                        $currentLoan->save();
                        return response()->json([
                'message' => 'The last due is still pending. The row has been updated.',
            ]);
        }
         
    } else {
        $extra_amount = $current_paid_amount - $current_next_amount;
        $currentLoan->pending_amount = 0;
        $currentLoan->status = 'paid';
        $currentLoan->save();

        $this->processNextUnpaidLoan($loan_id, -$extra_amount,$paid_amount);
    }
}
protected function processNextUnpaidLoan($loan_id, $amount_change,$paid_amount)
{
    Log::info("Processing Next Unpaid Loan for loan_id: $loan_id", ['amount_change' => $amount_change]);

    // Fetch the next unpaid loan
    $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();

    if ($nextLoan) {
        Log::info("Next loan found", ['next_loan_id' => $nextLoan->id]);  
        Log::info("Current due_amount", ['due_amount' => $nextLoan->due_amount]);

        // Ensure due_amount is treated as a float
        $dueAmount = (float) $nextLoan->due_amount;
        $amountChange = (float) $amount_change; // Ensure amount_change is also a float

        // Update next_amount
        $nextLoan->next_amount = $dueAmount + $amountChange;

        // Log before saving
        Log::info("Calculating next_amount", [
            'due_amount' => $dueAmount,
            'amount_change' => $amountChange,
            'new_next_amount' => $nextLoan->next_amount,
        ]);

        // Save changes and log success or failure
        if ($nextLoan->save()) {
            Log::info("Next amount updated successfully", ['new_next_amount' => $nextLoan->next_amount]);
        } else {
            Log::error("Failed to update next amount for loan_id: $loan_id", ['next_loan_id' => $nextLoan->id]);
        }
    } else {
        
        Log::info("No unpaid loan found for processNextUnpaidLoan loan_id: $loan_id");
            //   Log::info("subramanithis is work on last due with paid_amount: " . $paid_amount);
$currentPendingLoan=  LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'pending')
                        ->first();
       if ($currentPendingLoan) {
        $currentPendingLoan->next_amount -= $extra_amount; // Subtract the paid amount
        $currentPendingLoan->save(); // Save the updated record

        Log::warning("No unpaid loan processNextUnpaidLoan2 found for loan_id: $loan_id");

        // Check if the status is pending
        if ($currentPendingLoan->status === 'pending') {
            // Send a warning or message in response
            return response()->json([
                'message' => 'The last due is still pending. The row has been updated.',
                'status' => 'warning' // You can include a status key for frontend handling
            ]);
        }
    } else {
        return response()->json([
            'message' => 'No pending loans found.',
            'status' => 'error'
        ]);
    }
    }
}
protected function processNextUnpaidLoan1($loan_id, $amount_change,$paid_amount)
{
    Log::info("Processing Next Unpaid Loan for loan_id in processNextUnpaidLoan1: $loan_id", ['amount_change' => $amount_change]);

    // Fetch the next unpaid loan
    $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();

    if ($nextLoan) {
        Log::info("Next loan found", ['next_loan_id' => $nextLoan->id]);  
        Log::info("Current due_amount", ['due_amount' => $nextLoan->due_amount]);

        // Ensure due_amount is treated as a float
        $dueAmount = (float) $nextLoan->due_amount;
        $amountChange = (float) $amount_change; // Ensure amount_change is also a float

        // Update next_amount
        // $nextLoan->next_amount = $dueAmount + $amountChange;
        $nextLoan->next_amount=(float) $nextLoan->next_amount-$nextLoan->paid_amount;
        $nextLoan->save();
$nextLoan->pending_amount= $nextLoan->due_amount;

$nextLoan->save();

        // Log before saving
        Log::info("Calculating next_amount", [
            'due_amount' => $dueAmount,
            'amount_change' => $amountChange,
            'pending_amount' => $nextLoan->pending_amount,
        ]);

if($nextLoan->pending_amount>0)
{
Log::info('Next Loan Pending Amount: ' . $nextLoan->pending_amount);

    $nextLoan->status='pending';
    $nextLoan->save();
    
     $unpaid = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();
    if($unpaid)
    {
        Log::info("unpaid is here");
        
       $pendingRecords = LoanDue::where('loan_id', $loan_id)
                         ->where('status', 'pending')
                         ->get();  // Get all pending records

$totalPendingAmount = 0;  // Initialize a variable to store the total amount

// Loop through each pending record and sum the pending_amount
foreach ($pendingRecords as $pendingRecord) {
    $totalPendingAmount += $pendingRecord->pending_amount;
}

                        
    $unpaid->next_amount=$unpaid->due_amount+ $totalPendingAmount;
    Log::info('unpaid next amount: ' . $totalPendingAmount);
    $unpaid->save();
    }
}
else
{
  $nextLoan->status='paid';
  $nextLoan->save();
}
        // Save changes and log success or failure
        if ($nextLoan->save()) {
            Log::info("Next amount updated successfully", ['new_next_amount' => $nextLoan->next_amount]);
        } else {
            Log::error("Failed to update next amount for loan_id: $loan_id", ['next_loan_id' => $nextLoan->id]);
        }
    } else {
        Log::info("this is work on last due with paid_amount".$paid_amount);
        $currentPendingLoan=  LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'pending')
                        ->first();
                        
                      if ($currentPendingLoan) {
        $currentPendingLoan->next_amount -= $paid_amount; // Subtract the paid amount
        $currentPendingLoan->save(); // Save the updated record

        Log::warning("No unpaid loan processNextUnpaidLoan1 found for loan_id: $loan_id");

       if ($currentPendingLoan) {
            $currentPendingLoan->next_amount -= $paid_amount;
           

            Log::warning("No unpaid loan found for loan_id: $loan_id");

           if ($currentPendingLoan->save() & $currentPendingLoan->status === 'pending') {
            // Send a warning or message in response
            return response()->json([
                'message' => 'The last due is still pending. The row has been updated.',
                'status' => 'warning' // You can include a status key for frontend handling
            ]);
        }
    } else {
        return response()->json([
            'message' => 'No pending loans found.',
            'status' => 'error'
        ]);
    }
                      }
        Log::info("No unpaid  processNextUnpaidLoan1 loan found for loan_id: $loan_id");
    }
}

protected function processNextUnpaidLoan2($loan_id, $extra_amount)
{
    Log::info("Processing Next Unpaid Loan for loan_id in processNextUnpaidLoan2: $loan_id", ['extra_amount' => $extra_amount]);

    // Fetch the next unpaid loan
    $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();

    if ($nextLoan) {
        // Ensure due_amount is treated as a float
        $dueAmount = (float) $nextLoan->due_amount;
        $amountChange = (float) $extra_amount; // Ensure amount_change is also a float

        if ($dueAmount == $extra_amount) {
            $nextLoan->pending_amount = 0; 
            $nextLoan->status = 'paid';
             $nextLoan->save();
               $nextrecord = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();
                        
                   $nextrecord->next_amount = $nextrecord->due_amount;
                    $nextrecord->save();
            
        } elseif ($dueAmount > $extra_amount) {
            $nextLoan->pending_amount = $dueAmount - $extra_amount;
            $nextLoan->status = 'pending';
            $nextLoan->save();
               $nextrecord = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();
                        
                   $nextrecord->next_amount = $nextrecord->due_amount+ $nextLoan->pending_amount;
                    $nextrecord->save();
        } elseif ($dueAmount < $extra_amount) {
            $nextLoan->pending_amount = 0;
            $extra_amount_new = $extra_amount - $dueAmount;
            $nextLoan->status = 'paid';
            
               while ($extra_amount_new==0)
               {
                    $nextLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'unpaid')
                        ->first();
                        
                    $nextLoan->next_amount= $nextLoan->due_amount- $extra_amount_new;     
                   
               }
               
        }

        // Save changes and log success or failure
        if ($nextLoan->save()) {
            Log::info("Next amount updated successfully", ['new_next_amount' => $nextLoan->next_amount]);
        } else {
            Log::error("Failed to update next amount for loan_id: $loan_id", ['next_loan_id' => $nextLoan->id]);
        }
    } else {
    $currentPendingLoan = LoanDue::where('loan_id', $loan_id)
                        ->where('status', 'pending')
                        ->first();

    if ($currentPendingLoan) {
        $currentPendingLoan->next_amount -= $extra_amount; // Subtract the paid amount
        $currentPendingLoan->save(); // Save the updated record

        Log::warning("No unpaid loan processNextUnpaidLoan2 found for loan_id: $loan_id");

        // Check if the status is pending
        if ($currentPendingLoan->status === 'pending') {
            // Send a warning or message in response
            return response()->json([
                'message' => 'The last due is still pending. The row has been updated.',
                'status' => 'warning' // You can include a status key for frontend handling
            ]);
        }
    } else {
        return response()->json([
            'message' => 'No pending loans found.',
            'status' => 'error'
        ]);
    }
}

}

// oct20 function end




public function updateDueEntryDetails(Request $request, $loan_id)
{
    ob_clean();
    Log::info("Received request to update loan details for loan_id: $loan_id", [
        'request' => $request->all()
    ]);

    // Validate the incoming request
    $request->validate([
        'due_date' => 'required|date',
        'collection_by' => 'required|string',
        'paid_amount' => 'required|numeric|min:0'
    ]);

    // Assign request variables
    $due_date = $request->due_date;
    $collection_by = $request->collection_by;
    $paid_amount = $request->paid_amount;

    // 1) Update paid_amount where due_date = $due_date
    $currentLoan = LoanDue::where('loan_id', $loan_id)
        ->where('due_date', $due_date)
        ->first();

    if (!$currentLoan) {
        Log::error("No loan record found for loan_id: $loan_id and due_date: $due_date");
        return response()->json(['message' => "No loan record found for due date $due_date."], 404);
    }

    // Update the paid amount for the current due date
    $currentLoan->paid_amount = $paid_amount;
    $currentLoan->paid_on = now();
    $currentLoan->save();

    // 2) Check for pending records
    $pendingLoan = LoanDue::where('loan_id', $loan_id)
        ->where('status', 'pending')
        ->first();

    if ($pendingLoan) {
        // Calculate pending amount and update status based on the balance
        $pending_amount = $pendingLoan->next_amount - $paid_amount;
        $pendingLoan->pending_amount = $pending_amount;

        if ($pending_amount > 0) {
            // Partially paid, set status to 'pending'
            $pendingLoan->status = 'pending';
        } else {
            // Fully paid, mark as 'paid'
            $pendingLoan->status = 'paid';
        }
        $pendingLoan->save();
    } else {
         $pending_amount = $currentLoan->next_amount - $paid_amount;
         $currentLoan->pending_amount = $pending_amount;
    }

    // 3) Update next unpaid loan record if any
    $nextLoan = LoanDue::where('loan_id', $loan_id)
        ->where('status', 'unpaid')
        ->orderBy('due_date', 'ASC')
        ->first();

    if ($nextLoan) {
        if (isset($pending_amount)) {
            $nextLoan->next_amount = $pending_amount + $nextLoan->due_amount;
        } else {
            $nextLoan->next_amount = $nextLoan->due_amount;
        }
        $nextLoan->save();
    }

    // Next-step logic: Handling overpayments or partial payments
    if ($pendingLoan) {
        if ($pending_amount == $paid_amount || $pending_amount < $paid_amount) {
            // Full payment case: Calculate balance and update status
            $balance_amount = $paid_amount - $pending_amount;
            $pendingLoan->status = 'paid';
            $pendingLoan->save();

            // If there is any balance, apply it to the next unpaid record
            if ($balance_amount != 0) {
                $nextLoan = LoanDue::where('loan_id', $loan_id)
                    ->where('status', 'unpaid')
                    ->orderBy('due_date', 'ASC')
                    ->first();

                if ($nextLoan) {
                    $nextLoan->next_amount -= $balance_amount;
                    $nextLoan->pending_amount = $nextLoan->due_amount - $balance_amount;
                    $nextLoan->status = 'pending';
                    $nextLoan->save();
                } else {
                    $nextLoan->next_amount -= $balance_amount;
                    $nextLoan->pending_amount = $nextLoan->due_amount - $balance_amount;
                    $nextLoan->status = 'paid';
                    $nextLoan->save();
            }
        } elseif ($pending_amount > $paid_amount) {
            // Partial payment case: Update pending amount
            $balance_amount = $pending_amount - $paid_amount;
            $pendingLoan->pending_amount = $balance_amount;
            $pendingLoan->save();

            // Apply remaining balance to the next unpaid record
            $nextLoan = LoanDue::where('loan_id', $loan_id)
                ->where('status', 'unpaid')
                ->orderBy('due_date', 'ASC')
                ->first();

            if ($nextLoan) {
                $nextLoan->next_amount -= $paid_amount;
                $nextLoan->pending_amount = $nextLoan->next_amount + $nextLoan->due_amount;
                $nextLoan->status = 'pending';
                $nextLoan->save();
            }
        }
    }

    // Return response with updated loan details
    return response()->json([
        'message' => "Payment details updated successfully.",
        'loan' => [
            'id' => $currentLoan->id,
            'collection_by' => $collection_by,
            'due_amount' => $currentLoan->due_amount,
            'paid_amount' => $paid_amount,
            'paid_on' => $currentLoan->paid_on,
            'pending_due_amount' => $pendingLoan ? $pendingLoan->pending_amount : 0
        ]
    ]);
}
}


//From & To date between Pending Record List With City
// public function getPendingLoansWithUserAndCity(Request $request)
// {
//      ob_clean();
        
//         // Step 1: Validate the incoming request (if applicable)
//         $validatedData = $request->validate([
//             'from_date' => 'required|date',
//             'to_date' => 'required|date|after_or_equal:from_date',
//         ]);

//         // Extract from_date and to_date from the request
//         $fromDate = $validatedData['from_date'];
//         $toDate = $validatedData['to_date'];

//         // Step 2: Fetch pending loan dues with joins
//         $pendingLoans = LoanDue::select('loan_due.*', 'users.user_name', 'city.city_name')
//             ->join('users', 'loan_due.user_id', '=', 'users.user_id')
//             ->join('city', 'users.city', '=', 'city.city_name')
//             ->where('loan_due.status', 'pending')
//             ->whereBetween('loan_due.due_date', [$fromDate, $toDate])
//             ->get();

//         // Check if there are any pending loans
//         if ($pendingLoans->isEmpty()) {
//             return response()->json([
//                 'total_Date' => 0,
//                 'total_pending' => 0,
//                 'total_pending_city' => 0,
//                 'total_pending_amount' => 0,
//                 'data' => []
//             ], 200);
//         }

//         // Step 3: Aggregate data
//         $totalPending = $pendingLoans->count();
//         $totalPendingAmount = $pendingLoans->sum('due_amount');
//         $totalPendingCities = $pendingLoans->unique('city_name')->count();

//         // Group data by city
//         $groupedLoans = $pendingLoans->groupBy('city_name');

//         // Format the response data
//         $response = [
//             'total_Date' => $pendingLoans->count(),
//             'total_pending' => $totalPending,
//             'total_pending_city' => $totalPendingCities,
//             'total_pending_amount' => $totalPendingAmount,
//             'data' => []
//         ];

//         // Iterate through each city and prepare the data
//         foreach ($groupedLoans as $city => $loans) {
//             // Create a new entry for each city
//             $response['data'][] = [
//                 'city' => $city, // Use 'city' key for the city name
//                 'loans' => $loans->map(function ($loan) {
//                     return [
//                         'loan_id' => $loan->loan_id,
//                         'user_id' => $loan->user_id,
//                         'next_amount' => $loan->next_amount,
//                         'pending_amount' => $loan->pending_amount,
//                         'due_amount' => $loan->due_amount,
//                         'paid_amount' => $loan->paid_amount,
//                         'due_date' => $loan->due_date,
//                         'paid_on' => $loan->paid_on,
//                         'collection_by' => $loan->collection_by,
//                         'status' => $loan->status,
//                         'created_at' => $loan->created_at,
//                         'updated_at' => $loan->updated_at,
//                         'user_name' => $loan->user_name
//                     ];
//                 })
//             ];
//         }

//         // Step 4: Return the response
//         return response()->json($response, 200);
// }
public function getPendingLoansWithUserAndCity(Request $request)
{
    ob_clean();
        
    // Step 1: Validate the incoming request (without after_or_equal:from_date)
    $validatedData = $request->validate([
        'from_date' => 'required|date',
        'to_date' => 'required|date',
    ]);

    // Extract from_date and to_date from the request
    $fromDate = $validatedData['from_date'];
    $toDate = $validatedData['to_date'];

    // Step 2: Fetch pending loan dues with joins
    $pendingLoans = LoanDue::select('loan_due.*', 'users.user_name', 'city.city_name')
        ->join('users', 'loan_due.user_id', '=', 'users.user_id')
        ->join('city', 'users.city', '=', 'city.city_name')
        ->where('loan_due.status', 'pending')
        ->whereBetween('loan_due.due_date', [$fromDate, $toDate])
        ->get();

    // Check if there are any pending loans
    if ($pendingLoans->isEmpty()) {
        return response()->json([
            'total_Date' => 0,
            'total_pending' => 0,
            'total_pending_city' => 0,
            'total_pending_amount' => 0,
            'data' => []
        ], 200);
    }

    // Step 3: Aggregate data
    $totalPending = $pendingLoans->count();
    $totalPendingAmount = $pendingLoans->sum('due_amount');
    $totalPendingCities = $pendingLoans->unique('city_name')->count();

    // Group data by city
    $groupedLoans = $pendingLoans->groupBy('city_name');

    // Format the response data
    $response = [
        'total_Date' => $pendingLoans->count(),
        'total_pending' => $totalPending,
        'total_pending_city' => $totalPendingCities,
        'total_pending_amount' => $totalPendingAmount,
        'data' => []
    ];

    // Iterate through each city and prepare the data
    foreach ($groupedLoans as $city => $loans) {
        // Create a new entry for each city
        $response['data'][] = [
            'city' => $city, // Use 'city' key for the city name
            'loans' => $loans->map(function ($loan) {
                return [
                    'loan_id' => $loan->loan_id,
                    'user_id' => $loan->user_id,
                    'next_amount' => $loan->next_amount,
                    'pending_amount' => $loan->pending_amount,
                    'due_amount' => $loan->due_amount,
                    'paid_amount' => $loan->paid_amount,
                    'due_date' => $loan->due_date,
                    'paid_on' => $loan->paid_on,
                    'collection_by' => $loan->collection_by,
                    'status' => $loan->status,
                    'created_at' => $loan->created_at,
                    'updated_at' => $loan->updated_at,
                    'user_name' => $loan->user_name
                ];
            })
        ];
    }

    // Step 4: Return the response
    return response()->json($response, 200);
}


//Today Pending Record List
public function getPendingLoansToday()
    {
        ob_clean();
        try {
            // Fetch records where status is 'pending' and due date is today
            $pendingLoans = LoanDue::where('status', 'pending')
                                    ->where('due_date', Carbon::today())
                                    ->get();

            // Check if loans exist
            if ($pendingLoans->isEmpty()) {
                return response()->json([
                    'message' => 'Today No Loan_Due Available'
                ], 404);
            }

            // Return a JSON response with the loan details
            return response()->json($pendingLoans, 200);
        } catch (Exception $e) {
            // Handle any errors that occur during the query
            return response()->json([
                'error' => 'An error occurred while fetching pending loans.',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    
// Fetch city names from the city table
public function getCityNames(): JsonResponse
{
    ob_clean();
    // Retrieve city names and pin codes from the `city` table
    $cities = City::select('city_name', 'pincode')->get();

    // Transform the collection to an array with the desired format
    $formattedCities = $cities->map(function($city) {
        return [
            'city_name' => $city->city_name,
            'pincode' => $city->pincode,
        ];
    });

    // Return the city names and pin codes as a JSON response
    return response()->json($formattedCities);
}
    
// Method to store new city data
public function cityAdd(Request $request): JsonResponse
    {
        ob_clean();
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'city_name' => 'required|string|max:255',
            'pincode'   => 'required|numeric',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 400); // Bad request
        }

        // Create a new city record
        $city = City::create([
            'city_name' => $request->city_name,
            'pincode'   => $request->pincode,
        ]);

        // Return success response
        return response()->json([
            'message' => 'City added successfully!',
            'city'    => $city
        ], 201); // Created
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */



public function store(Request $request)
{
    ob_clean();
    try {
        // Validate the incoming request
        $validated = $request->validate([
            'loan_id' => 'required|string',
            'user_id' => 'required|string|max:255',
            'due_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_date' => 'required|date',
            'paid_on' => 'nullable|date',
            'collection_by' => 'required|integer',
            'status' => 'nullable|string|in:paid,unpaid,pending', // Added status validation with default option
        ]);

        // Retrieve collector by collection_by
        $collector = User::where('user_id', $validated['collection_by'])->first();

        // Check if collector exists
        if (!$collector) {
            return response()->json(['message' => 'Collector not found.'], 404);
        }

        // Determine the status, defaulting to 'unpaid'
        $status = $validated['status'] ?? 'unpaid';

        // Insert the new loan_due record
        $loan_due = LoanDue::create([
            'loan_id' => $validated['loan_id'], // use loan_id from validated data
            'user_id' => $validated['user_id'], 
            'due_amount' => $validated['due_amount'],
            'paid_amount' => $validated['paid_amount'],
            'due_date' => $validated['due_date'],
            'paid_on' => $validated['paid_on'] ?? null, // Set paid_on to null if not provided
            'collection_by' => $collector->id, // Use the valid collector ID
            'status' => $status, // Save the status field
            // Do not set next_amount here
            'created_at' => now(),
            'updated_at' => now(),
        ]);
if($loan_due)
{
        // Retrieve the first record from loan_due
        $firstLoanDue = LoanDue::first();

        // Update the next_amount field of the first record with the due_amount of the newly created record
        if ($firstLoanDue) {
            $firstLoanDue->update(['next_amount' => $validated['due_amount']]);
        }
}

        // Return a success response
        return response()->json(['message' => 'Loan Due added successfully!', 'status' => $status], 201);
        
    } catch (ValidationException $e) {
        // Handle validation errors
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Handle any other errors
        Log::error('Error adding Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error adding Loan Due'], 500);
    }
}



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        ob_clean();
        $loan_due = LoanDue::find($id);

        if (!$loan_due) {
            return response()->json(['message' => 'Loan due not found'], 404);
        }

        return response()->json(['message' => $loan_due], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $loan_id)
{
    ob_clean();
    try {
        // Validate the incoming request
        $validated = $request->validate([
            'paid_amount' => 'required|numeric',
            'paid_on' => 'nullable|date',
            'status' => 'nullable|string|in:paid,unpaid,pending', // Optional status
        ]);

        // Retrieve the first loan_due record with the specified loan_id
        $loan_due = LoanDue::where('loan_id', $loan_id)->orderBy('due_date')->first();

        // Check if the loan_due record exists
        if (!$loan_due) {
            return response()->json(['message' => 'Loan Due record not found.'], 404);
        }

        // Fetch collection_by from the session
        $collection_by = session('user_id'); // Assuming the user_id is stored in the session as 'user_id'

        // Check if collection_by exists
        if (!$collection_by) {
            return response()->json(['message' => 'Collector not found in session.'], 404);
        }

        // Update the loan_due record with the new validated data
        $loan_due->paid_amount = $validated['paid_amount'];
        $loan_due->paid_on = $validated['paid_on'] ?? null; // Keep it null if not provided
        $loan_due->collection_by = $collection_by; // Use the user_id from session
        $loan_due->status = $validated['status'] ?? 'unpaid'; // Default to unpaid if status not provided
        $loan_due->updated_at = now();

        // Save the changes
        $loan_due->save();

        // Return a success response
        return response()->json(['message' => 'Loan Due updated successfully!', 'status' => $loan_due->status], 200);

    } catch (ValidationException $e) {
        // Handle validation errors
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Handle any other errors
        Log::error('Error updating Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan Due'], 500);
    }
}

public function updateID(Request $request, $loan_id, $due_date, $status)
{
    ob_clean();
    Log::info('Incoming request data', [
        'loan_id' => $loan_id,
        'due_date' => $due_date,
        'status' => $status,
        'request_data' => $request->all(),
    ]);

    try {
        // Validate the incoming request for paid_amount
        $validated = $request->validate([
            'paid_amount' => 'required|numeric',
        ]);

        // Retrieve the specific loan_due record based on loan_id and due_date
        $loan_due = LoanDue::where('loan_id', $loan_id)
                           ->where('due_date', $due_date)
                           ->first();

        // Check if the loan_due record exists
        if (!$loan_due) {
            Log::warning('Loan Due record not found', [
                'loan_id' => $loan_id,
                'due_date' => $due_date,
            ]);
            return response()->json(['message' => 'Loan Due record not found.'], 404);
        }

        // Fetch collection_by from the session
        $collection_by = session('user_id');

        // Check if collection_by exists
        if (!$collection_by) {
            return response()->json(['message' => 'Collector not found in session.'], 404);
        }

        // Update the loan_due record with the new validated data
        $loan_due->paid_amount = $validated['paid_amount'];
        $loan_due->collection_by = $collection_by; // Use the user_id from session
        $loan_due->status = $status; // Update to the provided status
        $loan_due->updated_at = now();

        // Save the changes
        $loan_due->save();

        // Return a success response
        return response()->json(['message' => 'Loan Due updated successfully!', 'status' => $loan_due->status], 200);

    } catch (ValidationException $e) {
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Error updating Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan Due'], 500);
    }
}

public function updateLoanDue(Request $request)
{
    ob_clean();
    // Validate the request only for loan_id and due_date
    $validatedData = $request->validate([
        'loan_id' => 'required|string',
        'due_date' => 'required|date',
    ]);

    // Find the loan due record by loan_id and due_date
    $loanDue = LoanDue::where('loan_id', $validatedData['loan_id'])
                      ->where('due_date', $validatedData['due_date'])
                      ->first();

    if (!$loanDue) {
        return response()->json(['message' => 'Loan due record not found'], 404);
    }

    // Only update fields that are present in the request
    if ($request->has('paid_amount')) {
        $loanDue->paid_amount = $request->input('paid_amount');
    }
    if ($request->has('paid_on')) {
        $loanDue->paid_on = $request->input('paid_on');
    }
    if ($request->has('collection_by')) {
        $loanDue->collection_by = $request->input('collection_by');
    }
    if ($request->has('status')) {
        $loanDue->status = $request->input('status');
    }

    // Save the updated loan due record
    $loanDue->save();

    return response()->json(['message' => 'Loan due updated successfully', 'loan_due' => $loanDue], 200);
}

    /**
     * Remove the specified resource from storage.
     */
public function destroy(string $loan_id)
{
    ob_clean();
    // Find the loan due record by loan_id
    $loan_due = LoanDue::where('loan_id', $loan_id)->first();

    // Check if the loan due record exists
    if (!$loan_due) {
        // Return a 404 response if the loan due is not found
        return response()->json([
            'message' => 'Loan Due not found'
        ], 404);
    }

    try {
        // Attempt to delete the loan due record
        $loan_due->delete();

        // Return a success response after deletion
        return response()->json([
            'message' => 'Loan Due deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        // Return an error response if an exception occurs during deletion
        return response()->json([
            'message' => 'Failed to delete Loan Due',
            'error' => $e->getMessage()
        ], 500);
    }
}


}


