<?php

namespace App\Http\Controllers;

use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Example controller showing Firebase Storage usage
 * This is a demo - you can delete this file if not needed
 */
class FirebaseUploadTestController extends Controller
{
    protected $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    /**
     * Show upload form
     */
    public function showForm()
    {
        return view('firebase-test');
    }

    /**
     * Handle single image upload
     */
    public function uploadSingle(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            if ($request->hasFile('image')) {
                
                
                $imageUrl = $this->firebaseStorage->uploadImage(
                    $request->file('image'), 
                    'test-uploads'
                );

                if ($imageUrl) {
                   
                    
                    return redirect('/test-firebase')
                        ->with('success', true)
                        ->with('url', $imageUrl);
                }
            }

            return redirect('/test-firebase')
                ->with('error', 'Image upload failed. Check logs for details.');
                
        } catch (\Exception $e) {
            
            
            return redirect('/test-firebase')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle multiple image uploads
     */
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|max:10240',
        ]);

        if ($request->hasFile('images')) {
            $urls = $this->firebaseStorage->uploadMultipleImages(
                $request->file('images'),
                'test-uploads'
            );

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'urls' => $urls,
                'count' => count($urls)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No images uploaded'
        ], 400);
    }

    /**
     * Delete an image
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $deleted = $this->firebaseStorage->deleteImage($request->url);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image deletion failed'
        ], 500);
    }
}
