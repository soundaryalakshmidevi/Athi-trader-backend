<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoanCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        ob_clean();
        $loan_category = LoanCategory::all();

        if($loan_category->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json(['message' => $loan_category], 200);
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
        $validateData = $request->validate([
            'category_id' => 'required|integer', // Allow only numbers for category_id
            'category_name' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    // Check for existing category name, case insensitive
                    if (LoanCategory::whereRaw('LOWER(category_name) = ?', [strtolower($value)])->exists()) {
                        $fail('The category name has already been taken.');
                    }
                },
            ],
            'category_type' => 'required|in:weekly,daily,monthly',
            'duration' => 'required|integer', // Ensure duration is an integer
            'interest_rate' => 'required|numeric', // Ensure interest_rate is numeric
            'status' => 'required|in:active,inactive',
        ]);

        $loan_category = LoanCategory::create([
            'category_id' => $validateData['category_id'],
            'category_name' => $validateData['category_name'],
            'category_type' => $validateData['category_type'],
            'duration' => $validateData['duration'],
            'interest_rate' => $validateData['interest_rate'],
            'status' => $validateData['status'],
        ]);

        return response()->json(['message' => 'Loan Category added successfully!'], 201);
    } catch (ValidationException $e) {
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Error creating Loan Category: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating Loan Category'], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        ob_clean();
        $loan_category = LoanCategory::find($id);

        if(!$loan_category) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json(['message' => $loan_category], 200);
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
public function update(Request $request, $category_id)
{
    ob_clean();
    try {
        // Find the LoanCategory by category_id
        $loan_category = LoanCategory::where('category_id', $category_id)->first();

        // Check if the loan category exists
        if (!$loan_category) {
            return response()->json(['message' => 'Loan Category not found.'], 404);
        }

        // Validate the incoming request data
        $validateData = $request->validate([
            'category_id' => 'required|integer', // Allow only numbers for category_id
            'category_name' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($loan_category) {
                    // Check for existing category name, case insensitive, but ignore the current record
                    if (LoanCategory::whereRaw('LOWER(category_name) = ?', [strtolower($value)])
                        ->where('category_id', '!=', $loan_category->category_id) // Compare against category_id
                        ->exists()) {
                        $fail('The category name has already been taken.');
                    }
                },
            ],
            'category_type' => 'required|in:weekly,daily,monthly',
            'duration' => 'required|integer', // Ensure duration is an integer
            'interest_rate' => 'required|numeric', // Ensure interest_rate is numeric
            'status' => 'required|in:active,inactive',
        ]);

        // Update the loan category
        $loan_category->update([
            'category_id' => $validateData['category_id'],
            'category_name' => $validateData['category_name'],
            'category_type' => $validateData['category_type'],
            'duration' => $validateData['duration'],
            'interest_rate' => $validateData['interest_rate'],
            'status' => $validateData['status'],
        ]);

        return response()->json(['message' => 'Loan Category updated successfully!'], 200);
    } catch (ValidationException $e) {
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Error updating Loan Category: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan Category'], 500);
    }
}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ob_clean();
        $loan_category = LoanCategory::find($id);

        if(!$loan_category) {
            return response()->json(['message' => 'No data found'], 404);
        }
        else{
        $loan_category->delete();
        return response()->json(['message' => 'Loan Category deleted successfully'], 200);
        }
    }
}
