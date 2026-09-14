<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        // 1. Obtener usuario autenticado por JWT
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Evaluamos si coincide el rol directo O si pasa la Gate
        $hasRole = $user->role === $ability;
        $hasGate = $user->can($ability);

        if (! $hasRole && ! $hasGate) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient privileges.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}