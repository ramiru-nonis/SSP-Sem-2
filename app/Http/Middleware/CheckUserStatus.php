<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->status === 'Blocked') {
            auth()->logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked. Please contact support.'
                ], 403);
            }
            
            return redirect()->route('login')
                ->with('error', 'Your account has been blocked. Please contact support.');
        }

        return $next($request);
    }
}
