<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Get authenticated user from bearer token
     */
    private function getAuthenticatedUser(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            return auth('sanctum')->user();
        }
        return null;
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Build query with user relationship for admin view
        $query = Order::with(['items.product', 'user:id,name,email']);

        // Customers only see their own orders, admins see all
        if ($user->user_role !== 'Admin') {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:Credit Card,PayPal,Bank Transfer,Cash on Delivery',
            'shipping_method' => 'required|string',
            'billing_first_name' => 'required|string',
            'billing_last_name' => 'required|string',
            'billing_address_1' => 'required|string',
            'billing_city' => 'required|string',
            'billing_state' => 'required|string',
            'billing_postal_code' => 'required|string',
            'billing_country' => 'required|string',
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string',
            'shipping_first_name' => 'required|string',
            'shipping_last_name' => 'required|string',
            'shipping_address_1' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_state' => 'required|string',
            'shipping_postal_code' => 'required|string',
            'shipping_country' => 'required|string',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Initialize session if needed for API requests
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }

        $user = $this->getAuthenticatedUser($request);
        $userId = $user?->id;
        $sessionId = $request->session()->getId();

        // Get cart items
        $cartItems = CartItem::with('product')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = $cartItems->sum(function ($item) {
                return $item->quantity * $item->product->price;
            });

            $taxAmount = $subtotal * 0.1; // 10% tax
            $shippingAmount = 10.00; // Fixed shipping
            $discountAmount = 0;

            // Apply coupon if provided
            if ($request->coupon_code) {
                // Coupon logic here
            }

            $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $userId,
                'status' => 'Pending',
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'payment_status' => 'Pending',
                'shipping_method' => $request->shipping_method,
                'billing_first_name' => $request->billing_first_name,
                'billing_last_name' => $request->billing_last_name,
                'billing_company' => $request->billing_company,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_city' => $request->billing_city,
                'billing_state' => $request->billing_state,
                'billing_postal_code' => $request->billing_postal_code,
                'billing_country' => $request->billing_country,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'shipping_first_name' => $request->shipping_first_name,
                'shipping_last_name' => $request->shipping_last_name,
                'shipping_company' => $request->shipping_company,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_country' => $request->shipping_country,
                'notes' => $request->notes,
            ]);

            // Create order items and update stock
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                // Check stock
                if ($product->stock_quantity < $cartItem->quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                    'total' => $cartItem->quantity * $product->price,
                ]);

                // Update product stock
                $product->decrement('stock_quantity', $cartItem->quantity);
            }

            // Clear cart
            CartItem::where($userId ? 'user_id' : 'session_id', $userId ?? $sessionId)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load('items.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $query = Order::with(['items.product']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $order = $query->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status (Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,Processing,Shipped,Delivered,Cancelled,Refunded',
            'tracking_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $data = ['status' => $request->status];

        if ($request->status === 'Shipped') {
            $data['shipped_date'] = now();
            if ($request->tracking_number) {
                $data['tracking_number'] = $request->tracking_number;
            }
        }

        if ($request->status === 'Delivered') {
            $data['delivered_date'] = now();
        }

        $order->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order
        ]);
    }
}
