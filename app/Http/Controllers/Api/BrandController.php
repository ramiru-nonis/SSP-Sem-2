<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    /**
     * Display all brands
     */
    public function index()
    {
        $brands = Brand::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $brands
        ]);
    }

    /**
     * Display the specified brand
     */
    public function show($id)
    {
        $brand = Brand::with('products')->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
    }

    /**
     * Store a new brand (Admin only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'website_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand = Brand::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully',
            'data' => $brand
        ], 201);
    }

    /**
     * Update brand (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $id,
            'description' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'website_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully',
            'data' => $brand
        ]);
    }

    /**
     * Delete brand (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ], 404);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }
}
