<?php

namespace Martinko366\LaravelDbChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $conversation = $request->route('conversation');

        if ($conversation && !$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'You are not a participant of this conversation.',
            ], 403);
        }

        return $next($request);
    }
}
