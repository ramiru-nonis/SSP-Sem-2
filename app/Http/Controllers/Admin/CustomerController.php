<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('user_role', 'Customer')
            ->withCount('orders')
            ->with(['orders' => function($q) {
                $q->latest()->limit(5);
            }]);

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $customer->load(['orders' => function($query) {
            $query->with(['items.product'])->latest();
        }]);
        
        return view('admin.customers.show', compact('customer'));
    }

    public function toggleBlock(User $customer)
    {
        $newStatus = $customer->status === 'Active' ? 'Blocked' : 'Active';
        $customer->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 
            $newStatus === 'Blocked' ? 'Customer has been blocked successfully.' : 'Customer has been unblocked successfully.'
        );
    }
}
