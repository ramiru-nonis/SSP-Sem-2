<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('user_role', 'Customer')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'Delivered')->sum('total_amount'),
            'pending_orders' => Order::where('status', 'Pending')->count(),
        ];

        $recent_orders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        $low_stock = Product::where('stock_quantity', '<', 10)
            ->where('stock_quantity', '>', 0)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_orders', 'low_stock'));
    }
}
