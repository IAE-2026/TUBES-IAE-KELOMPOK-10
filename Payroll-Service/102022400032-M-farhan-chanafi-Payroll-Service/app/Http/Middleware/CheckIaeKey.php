<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIaeKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $iaeKey = $request->header('X-IAE-KEY');

        if ($iaeKey !== env('IAE_KEY')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'errors' => null
            ], 401);
        }

        return $next($request);
    }
}