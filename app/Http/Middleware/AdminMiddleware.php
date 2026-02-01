<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login.'
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // Check if user is admin
        if (auth()->user()->user_role !== 'Admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access only.'
                ], 403);
            }
            abort(403, 'Unauthorized. Admin access only.');
        }

        return $next($request);
    }
}
