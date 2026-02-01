<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Category;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function profile()
    {
        return view('auth.profile');
    }

    public function orders()
    {
        $categories = Category::active()->take(6)->get();
        return view('orders', compact('categories'));
    }

    public function wishlist()
    {
        $categories = Category::active()->take(6)->get();
        return view('wishlist', compact('categories'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/')->with('success', 'Logged out successfully');
    }
}
