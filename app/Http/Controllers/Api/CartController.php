<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get authenticated user if token is provided
     */
    protected function getAuthenticatedUser(Request $request)
    {
        // Try to authenticate via Sanctum token
        if ($request->bearerToken()) {
            return auth('sanctum')->user();
        }
        return null;
    }

    /**
     * Get cart items
     */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUser($request)?->id;
        
        // Ensure session is started
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session')->driver());
        }
        $sessionId = $request->session()->getId();

        $query = CartItem::with('product');

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cartItems = $query->get();

        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'subtotal' => $subtotal,
                'count' => $cartItems->sum('quantity')
            ]
        ]);
    }

    /**
     * Add item to cart
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);

        // Check stock availability
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        $userId = $this->getAuthenticatedUser($request)?->id;
        
        // Ensure session is started
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session')->driver());
        }
        $sessionId = $request->session()->getId();

        // Check if item already exists
        $existingItem = CartItem::where('product_id', $request->product_id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $request->quantity;
            
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }

            $existingItem->update(['quantity' => $newQuantity]);
            $cartItem = $existingItem;
        } else {
            $cartItem = CartItem::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data' => $cartItem->load('product')
        ], 201);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        // Check stock availability
        if ($cartItem->product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'data' => $cartItem->load('product')
        ]);
    }

    /**
     * Remove item from cart
     */
    public function destroy($id)
    {
        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $query = CartItem::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }

    /**
     * Get cart count
     */
    public function count(Request $request)
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $query = CartItem::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $count = $query->sum('quantity');

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}
