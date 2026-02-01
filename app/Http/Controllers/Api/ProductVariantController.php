<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Get variants for a product
     */
    public function index($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('color_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }

    /**
     * Get a specific variant
     */
    public function show($id)
    {
        $variant = ProductVariant::with('product')->find($id);

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $variant
        ]);
    }
}
