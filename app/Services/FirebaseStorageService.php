<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class FirebaseStorageService
{
    protected $storage;
    protected $bucket;

    public function __construct()
    {
        try {
            $credentialsPath = config('services.firebase.credentials');
            $storageBucket = config('services.firebase.storage_bucket');
            
            Log::info('Initializing Firebase Storage', [
                'credentials' => $credentialsPath,
                'bucket' => $storageBucket,
                'credentials_exists' => file_exists($credentialsPath)
            ]);

            $factory = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->withDefaultStorageBucket($storageBucket);

            $this->storage = $factory->createStorage();
            $this->bucket = $this->storage->getBucket();
            
            Log::info('Firebase Storage initialized successfully');
        } catch (Exception $e) {
            Log::error('Firebase Storage initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload an image to Firebase Storage
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string|null Returns the public URL of the uploaded image
     */
    public function uploadImage(UploadedFile $file, string $folder = 'products'): ?string
    {
        try {
            Log::info('Starting Firebase upload', [
                'folder' => $folder,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $folder . '/' . time() . '_' . Str::random(10) . '.' . $extension;

            Log::info('Generated filename: ' . $filename);

            // Upload file to Firebase Storage
            $object = $this->bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filename,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                    ]
                ]
            );

            Log::info('File uploaded to Firebase Storage');

            // Make the file publicly accessible
            $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);
            
            Log::info('Made file public');

            // Get the public URL
            $publicUrl = $this->getPublicUrl($filename);

            Log::info('Generated public URL: ' . $publicUrl);

            return $publicUrl;

        } catch (Exception $e) {
            Log::error('Firebase upload error: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get public URL for a file
     *
     * @param string $filePath
     * @return string
     */
    public function getPublicUrl(string $filePath): string
    {
        // Extract bucket name from gs:// URL
        $bucketName = str_replace('gs://', '', config('services.firebase.storage_bucket'));
        
        return sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
            $bucketName,
            urlencode($filePath)
        );
    }

    /**
     * Delete a file from Firebase Storage
     *
     * @param string $fileUrl
     * @return bool
     */
    public function deleteImage(string $fileUrl): bool
    {
        try {
            // Extract filename from URL
            $filename = $this->extractFilenameFromUrl($fileUrl);
            
            if (!$filename) {
                return false;
            }

            $object = $this->bucket->object($filename);
            
            if ($object->exists()) {
                $object->delete();
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Firebase delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract filename from Firebase Storage URL
     *
     * @param string $url
     * @return string|null
     */
    protected function extractFilenameFromUrl(string $url): ?string
    {
        // Match Firebase Storage URL pattern
        if (preg_match('/\/o\/(.+?)\?/', $url, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }

    /**
     * Upload multiple images
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    public function uploadMultipleImages(array $files, string $folder = 'products'): array
    {
        $urls = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $url = $this->uploadImage($file, $folder);
                if ($url) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }
}
