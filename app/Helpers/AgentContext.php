<?php

namespace App\Helpers;

use App\Models\Agent;

/**
 * AgentContext — lightweight singleton for the resolved agent on the current request.
 *
 * Usage:
 *   AgentContext::set($agent);        // called by ResolveAgent middleware
 *   AgentContext::current();          // returns Agent|null from anywhere
 *   AgentContext::hasFeature($key);   // returns bool, cached 5 min
 */
class AgentContext
{
    protected static ?Agent $agent = null;

    public static function set(?Agent $agent): void
    {
        static::$agent = $agent;
    }

    public static function current(): ?Agent
    {
        return static::$agent;
    }

    public static function hasAgent(): bool
    {
        return static::$agent !== null;
    }

    public static function hasFeature(string $key): bool
    {
        return static::$agent?->hasFeature($key) ?? false;
    }
}
