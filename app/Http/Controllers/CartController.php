<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Category;

class CartController extends Controller
{
    public function index()
    {
        $categories = Category::active()->take(6)->get();
        return view('cart', compact('categories'));
    }

    public function checkout()
    {
        $categories = Category::active()->take(6)->get();
        return view('checkout', compact('categories'));
    }
}
