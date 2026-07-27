<?php
// app/Http/Controllers/Admin/ExpenseController.php

namespace App\Http\Controllers; // FIX: was App\Http\Controllers — didn't match the folder/file path,
                                       // which throws "Class not found" if routes reference Admin\ExpenseController

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use PDF;

class ExpenseController extends Controller
{
    // Display expense dashboard
    public function index()
    {
        $users = User::select('id', 'name')->orderBy('name')->get();
        $categories = $this->getCategories();
        $paymentMethods = $this->getPaymentMethods();
        $statuses = ['pending', 'approved', 'rejected'];

        return view('admin.expenses.index', compact('users', 'categories', 'paymentMethods', 'statuses'));
    }

    // Get expenses list with filters
    public function list(Request $request)
    {
        try {
            $query = Expense::with('user');

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            if ($request->filled('min_amount') && $request->filled('max_amount')) {
                $query->whereBetween('amount', [$request->min_amount, $request->max_amount]);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->paginate(12);

            return response()->json([
                'status' => true,
                'data' => $expenses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch expenses: ' . $e->getMessage()
            ], 500);
        }
    }

    // Store new expense
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'category' => 'required|string',
                'payment_method' => 'nullable|string',
                'status' => 'required|in:pending,approved,rejected',
                // FIX: "image" rule rejected PDFs even though mimes allowed them
                'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['user_id'] = auth()->id();

            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('receipts', $filename, 'public');
                $data['receipt'] = $path;
            }

            $expense = Expense::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get single expense
    public function show($id)
    {
        try {
            $expense = Expense::with('user')->findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Expense not found'
            ], 404);
        }
    }

    // Update expense
    public function update(Request $request, $id)
    {
        try {
            $expense = Expense::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'category' => 'required|string',
                'payment_method' => 'nullable|string',
                'status' => 'required|in:pending,approved,rejected',
                'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('receipt')) {
                if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                    Storage::disk('public')->delete($expense->receipt);
                }

                $file = $request->file('receipt');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('receipts', $filename, 'public');
                $data['receipt'] = $path;
            }

            $expense->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete expense
    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);

            if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                Storage::disk('public')->delete($expense->receipt);
            }

            $expense->delete();

            return response()->json([
                'status' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete expense'
            ], 500);
        }
    }

    // Get expense statistics
    public function statistics(Request $request)
    {
        try {
            $base = Expense::query();

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $base->dateRange($request->date_from, $request->date_to);
            }

            // FIX: the original code reused the SAME $query builder for every aggregate.
            // select()/groupBy() calls accumulate on a shared builder instance instead of
            // resetting, so monthly_breakdown ended up grouped by category AND year/month
            // together, corrupting the numbers. Clone the base query for each aggregate.
            $statistics = [
                'total_expenses' => (clone $base)->count(),
                'total_amount' => (clone $base)->sum('amount'),
                'average_amount' => (clone $base)->avg('amount') ?? 0,
                'category_breakdown' => (clone $base)->select('category')
                    ->selectRaw('COUNT(*) as count')
                    ->selectRaw('SUM(amount) as total')
                    ->groupBy('category')
                    ->get(),
                'monthly_breakdown' => (clone $base)->selectRaw('YEAR(expense_date) as year')
                    ->selectRaw('MONTH(expense_date) as month')
                    ->selectRaw('COUNT(*) as count')
                    ->selectRaw('SUM(amount) as total')
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->get(),
                'status_breakdown' => (clone $base)->select('status')
                    ->selectRaw('COUNT(*) as count')
                    ->selectRaw('SUM(amount) as total')
                    ->groupBy('status')
                    ->get()
            ];

            return response()->json([
                'status' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    // Export expenses
    public function export(Request $request)
    {
        try {
            $query = Expense::with('user');

            // FIX: export() was ignoring several filters that list() supports
            // (title, payment_method, user_id), so "export current view" silently
            // exported a different, broader dataset than what the user was looking at.
            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            $exportType = $request->input('type', 'excel');

            if ($exportType === 'pdf') {
                $pdf = PDF::loadView('admin.expenses.pdf', compact('expenses'));
                return $pdf->download('expenses_' . date('Y-m-d') . '.pdf');
            }

            return Excel::download(new ExpensesExport($expenses), 'expenses_' . date('Y-m-d') . '.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export expenses: ' . $e->getMessage());
        }
    }

    private function getCategories()
    {
        return [
            'food' => 'Food & Dining',
            'transport' => 'Transport',
            'utilities' => 'Utilities',
            'rent' => 'Rent',
            'shopping' => 'Shopping',
            'entertainment' => 'Entertainment',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'travel' => 'Travel',
            'other' => 'Other'
        ];
    }

    private function getPaymentMethods()
    {
        return [
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'other' => 'Other'
        ];
    }
}
