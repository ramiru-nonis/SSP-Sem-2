<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Category;

class ContactController extends Controller
{
    public function show()
    {
        $categories = Category::active()->take(6)->get();
        return view('contact', compact('categories'));
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Log the contact message
        Log::channel('single')->info('Contact Form Submission', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'timestamp' => now(),
        ]);

        return back()->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
