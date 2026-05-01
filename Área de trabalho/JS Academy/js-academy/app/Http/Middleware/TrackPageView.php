<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;

class TrackPageView
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Só rastreia GET de páginas principais (não ajax, não admin, não assets)
        if ($request->isMethod('GET')
            && !$request->is('admin*')
            && !$request->is('api*')
            && !$request->expectsJson()
            && $response->getStatusCode() === 200
        ) {
            PageView::create([
                'page'       => $request->path() === '/' ? 'home' : $request->path(),
                'user_id'    => auth()->id(),
                'ip'         => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'referer'    => substr($request->header('referer') ?? '', 0, 500),
            ]);
        }

        return $response;
    }
}
