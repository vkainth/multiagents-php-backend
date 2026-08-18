<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api2',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api-internal')
                ->group(__DIR__.'/../routes/api-internal.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //

        // ---- as BCCH-v1 [BEGINS]--------

        $middleware->use([
            \App\Http\Middleware\CheckForMaintenanceMode::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \App\Http\Middleware\TrustProxies::class,
        ]);

        $middleware->alias([

            // ------ Laravel's built-in aliasea by default [BEGINS] -----------
            /*
            // Complete list: https://laravel.com/docs/middleware#middleware-aliases
            */
            // 'auth' => \App\Http\Middleware\Authenticate::class,
            // 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            // 'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
            // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            // 'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            // ------ Laravel's built-in aliasea by default [ENDS] -----------

            'redirect.authenticated' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'check.agent.id' => \App\Http\Middleware\CheckAgentId::class,
            'check.email.verified' => \App\Http\Middleware\CheckEmailVerified::class,
            'check.profile.completion' => \App\Http\Middleware\CheckProfileCompletion::class,
            'check.vow.access' => \App\Http\Middleware\CheckVowAccessOfAgent::class,
            'isAgent'=> \App\Http\Middleware\isAgent::class,
            'redirect.agent.login'=>\App\Http\Middleware\RedirectToAgentLoginPage::class,
            'api.auth'=>\App\Http\Middleware\ApiAuth::class,
            'phone.verified'=>\App\Http\Middleware\CheckPhoneNumberVerified::class,
            'check.agent.switch'=>\App\Http\Middleware\CheckForAgentSwitch::class,
            'redirect.map'=>\App\Http\Middleware\RedirectToMapPage::class,
            'listing.og.tags'=>\App\Http\Middleware\setPropertyOpenGraphTags::class,
            'redirect.agent_user.login'=>\App\Http\Middleware\RedirectAgentLoginPage::class,
            
            'google.auth'=>\App\Http\Middleware\GoogleAuth::class,
            'handle.ref'=>\App\Http\Middleware\HandleReferral::class,
            'stripe.webhook'=>\App\Http\Middleware\StripeWebhook::class,
            'track.history'=>\App\Http\Middleware\TrackHistoryURL::class,
            'force.subscribe'=> \App\Http\Middleware\ForceSubscribe::class,
            'alert.api.auth'=> \App\Http\Middleware\AlertApiAuth::class,
            'resolve.agent'   => \App\Http\Middleware\ResolveAgent::class,
            'auth.agent'      => \App\Http\Middleware\AuthenticateAgent::class,
            'guest.agent'     => \App\Http\Middleware\RedirectIfAgentAuthenticated::class,
            'auth.admin'      => \App\Http\Middleware\AuthenticateAdmin::class,
            'guest.admin'     => \App\Http\Middleware\RedirectIfAdminAuthenticated::class,
        ]);


        $middleware->web(append: [
            // --- by default-registered in Laravel-11 [BEGNIS] -----
            // \App\Http\Middleware\EncryptCookies::class, // replaced with Lv-11(Illuminate\...):
            // \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class, // replaced with Lv-11(Illuminate\...):
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // --- by default-registered in Laravel-11 [ENDS] -----
            
            // \Illuminate\Session\Middleware\AuthenticateSession::class, // Disabled in BCCH-v1
            
            \App\Http\Middleware\TrackHistoryURL::class,
            \App\Http\Middleware\ServerLog::class,
            \App\Http\Middleware\SetLoggedInCookie::class,
            \App\Http\Middleware\ResolveAgent::class,
            // \App\Http\Middleware\CheckEmailVerified::class,
            // \App\Http\Middleware\RedirectIfAuthenticated::class,
            // \App\Http\Middleware\CheckAgentId::class,
            // \App\Http\Middleware\CheckProfileCompletion::class,
            // \App\Http\Middleware\CheckVowAccessOfAgent::class,
        ]);

        $middleware->api(prepend: [
            // --- by default-registered in Laravel-11 [BEGNIS] -----
            // --- by default-registered in Laravel-11 [ENDS] -----
            // \App\Http\Middleware\EncryptCookies::class, // replaced with Lv-11(Illuminate\...):
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            'throttle:600,1',
            'bindings',
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\Authenticate::class, // replaced with Lv-11(Illuminate\...):
            Illuminate\Auth\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        // ---- as BCCH-v1 [ENDS]--------
        
        /**
         * BCCH-v1 App\Http\Middleware\EncryptCookies - functionality
         * > cookies not to encrypt
         */
        $middleware->encryptCookies(except: [
            'user_id','sponsor_id','logged_in',
        ]);
        
        $middleware->validateCsrfTokens(except: [
            '/stripe/webhook',
            '/handle_auth-json',
            '/api2/v1/alert-subscriptions',
            '/api2/v1/alert-sent',
            '/api2/v1/deactivate-alert',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom-logging [2024-10-27]:
        $exceptions->renderable(function (Throwable $ex) {
            $pathsArr = ['/home/bccondosandhomes/bcchv2','\/home\/bccondosandhomes\/bcchv2'];
            $_maxStacks = config('bcch.misc.log_max_stacks_count',env('LOG_MAX_STACKS_COUNT',4))??4;
            $userId = auth()->check() ? ('[userId:' . (auth()?->id()??'-') . ']') : '';
            $exTxt = $ex->getMessage() . rtrim(' at '. str_replace($pathsArr,'...',$ex->getFile()??'') . ':' . ($ex->getLine()??''),' at :') . " (" . get_class($ex) . ") ";
            $trace = collect($ex->getTrace())->take($_maxStacks)->map(fn($ln, $i) => "#{$i} " . str_replace($pathsArr, '...', $ln['file'] ?? '[-NA-]') . ' ('.($ln['line'] ?? '') . ')' )->implode("\n");
            logger()->error("(URL Visited: " . request()->fullUrl() . ") {$userId} \nException: ({$exTxt}) \n[stacktrace]:\n{$trace}  ...[stacktrace-trimmed].");
        });
    })->create();
