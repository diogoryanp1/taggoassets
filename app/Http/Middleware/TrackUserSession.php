<?php

namespace App\Http\Middleware;

use App\Domain\Identity\SessionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        app(SessionManager::class)->track($request);

        return $response;
    }
}
