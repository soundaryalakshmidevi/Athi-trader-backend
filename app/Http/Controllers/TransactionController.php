<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; 


class TransactionController extends Controller
{
     public function getAllTransactions()
    {
        ob_clean();
        try {
            // Retrieve all transactions
            $transactions = Transaction::all();

            // Return the transactions in JSON format
            return response()->json([
                'success' => true,
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    //search by current date
public function getTransactionsByCurrentDate(Request $request)
{
    ob_clean();
    try {
        // If a date is provided, validate it; otherwise, default to the current date
        $date = $request->input('date', now()->toDateString());

        // Ensure the provided date is a valid date format if it's not the current date
        if ($request->has('date')) {
            $request->validate([
                'date' => 'date',
            ]);
        }

        // Fetch transactions with the specified or current loan_date
        $transactions = Transaction::whereDate('loan_date', $date)->get();

        // Return the filtered transactions in JSON format
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve transactions',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getTransactionsByLoanIdAndToday(Request $request)
{
    ob_clean();
    try {
        // Validate that the 'loan_id' parameter is provided
        $request->validate([
            'loan_id' => 'required|integer',
        ]);

        // Retrieve the loan_id from the request
        $loanId = $request->input('loan_id');

        // Get the current date
        $today = now()->toDateString();

        // Fetch transactions with the specified loan_id and loan_date as today
        $transactions = Transaction::where('loan_id', $loanId)
            ->whereDate('loan_date', $today)
            ->get();

        // Return the filtered transactions in JSON format
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve transactions',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    
    //search by loan date
    public function getTransactionsByLoanDate(Request $request)
{
    ob_clean();
    try {
        // Validate that the 'date' parameter is provided and is a valid date format
        $request->validate([
            'date' => 'required|date',
        ]);

        // Retrieve the date from the request
        $date = $request->input('date');

        // Fetch transactions with the specified loan_date
        $transactions = Transaction::whereDate('loan_date', $date)->get();

        // Return the filtered transactions in JSON format
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve transactions',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function getTransactionsByLoanDateAndLoanid(Request $request)
{
    ob_clean();
    try {
        // Validate that both 'date' and 'loan_id' parameters are provided
        $request->validate([
            'date' => 'required|date',
            'loan_id' => 'required|integer',
        ]);

        // Retrieve the date and loan_id from the request
        $date = $request->input('date');
        $loanId = $request->input('loan_id');

        // Fetch transactions with the specified loan_date and loan_id
        $transactions = Transaction::whereDate('loan_date', $date)
                                    ->where('loan_id', $loanId)
                                    ->get();

        // Return the filtered transactions in JSON format
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve transactions',
            'error' => $e->getMessage(),
        ], 500);
    }
}



// Search by due date
public function getTransactionsByDueDate(Request $request)
{
    ob_clean();
    try {
        // Validate that the 'due_date' parameter is provided and is a valid date format
        $request->validate([
            'due_date' => 'required|date',
        ]);

        // Retrieve the due_date from the request
        $dueDate = $request->input('due_date');

        // Fetch transactions with the specified due_date
        $transactions = Transaction::whereDate('due_date', $dueDate)->get();

        // Return the filtered transactions in JSON format
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve transactions',
            'error' => $e->getMessage(),
        ], 500);
    }
}






}