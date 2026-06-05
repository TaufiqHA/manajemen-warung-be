<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddTokenFromQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('*/products/export') && $request->has('token')) {
            $request->headers->set('Authorization', 'Bearer '.$request->query('token'));
        }

        return $next($request);
    }
}
