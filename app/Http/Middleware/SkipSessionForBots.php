<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SkipSessionForBots
{
    protected array $botSignatures = [
        'Googlebot',
        'Bingbot',
        'YandexBot',
        'DuckDuckBot',
        'Baiduspider',
        'facebookexternalhit',
        'Twitterbot',
        'Slurp',
        'AhrefsBot',
        'SemrushBot',
        'MJ12bot',
        'DotBot',
        'rogerbot',
        'LinkedInBot',
        'Applebot',
        'PetalBot',
        'GPTBot',
        'CCBot',
        'anthropic-ai',
        'ClaudeBot',
    ];

    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->header('User-Agent', '');

        foreach ($this->botSignatures as $signature) {
            if (stripos($userAgent, $signature) !== false) {
                config(['session.driver' => 'array']);
                break;
            }
        }

        return $next($request);
    }
}
