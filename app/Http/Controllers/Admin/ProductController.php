<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    public function index()
    {
        $products = Product::with(['category', 'brand'])
            ->latest()
            ->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            'is_featured' => 'boolean',
            'status' => 'in:Active,Inactive,Draft,Out of Stock',
            // Variants
            'variants' => 'nullable|array',
            'variants.*.color_name' => 'required|string',
            'variants.*.color_code' => 'nullable|string',
            'variants.*.image' => 'nullable|image|max:10240',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
        ]);

        // Default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'Active';
        }

        if ($request->hasFile('image')) {
            try {
                Log::info('Uploading image to Firebase...', [
                    'filename' => $request->file('image')->getClientOriginalName(),
                    'size' => $request->file('image')->getSize()
                ]);
                
                $imageUrl = $this->firebaseStorage->uploadImage($request->file('image'), 'products');
                
                if ($imageUrl) {
                    $validated['image_url'] = $imageUrl;
                    Log::info('Image uploaded successfully', ['url' => $imageUrl]);
                } else {
                    Log::error('Firebase upload returned null');
                }
            } catch (\Exception $e) {
                Log::error('Firebase upload error: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
            }
        }

        $product = Product::create($validated);
        
        // Create variants if provided
        if ($request->has('variants') && is_array($request->variants)) {
            foreach ($request->variants as $index => $variantData) {
                $variant = [
                    'color_name' => $variantData['color_name'],
                    'color_code' => $variantData['color_code'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'sku' => $product->sku . '-' . strtoupper(substr($variantData['color_name'], 0, 3)) . '-' . ($index + 1),
                ];

                // Upload variant image if provided
                if ($request->hasFile("variants.{$index}.image")) {
                    try {
                        $variantImageUrl = $this->firebaseStorage->uploadImage(
                            $request->file("variants.{$index}.image"), 
                            'products/variants'
                        );
                        if ($variantImageUrl) {
                            $variant['image_url'] = $variantImageUrl;
                        }
                    } catch (\Exception $e) {
                        Log::error('Variant image upload error: ' . $e->getMessage());
                    }
                }

                $product->variants()->create($variant);
            }
        }
        
        Log::info('Product created', [
            'id' => $product->id,
            'image_url' => $product->image_url
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully');
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $product->load('variants');

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            'is_featured' => 'boolean',
            'status' => 'in:Active,Inactive,Draft,Out of Stock',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image from Firebase if exists
            if ($product->image_url) {
                $this->firebaseStorage->deleteImage($product->image_url);
            }
            
            $imageUrl = $this->firebaseStorage->uploadImage($request->file('image'), 'products');
            
            if ($imageUrl) {
                $validated['image_url'] = $imageUrl;
            }
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        // Delete image from Firebase if exists
        if ($product->image_url) {
            $this->firebaseStorage->deleteImage($product->image_url);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully');
    }
}
