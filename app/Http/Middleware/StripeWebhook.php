<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Error\SignatureVerification;
use Stripe\WebhookSignature;
use App\Models\Auth\FirebaseUser;

class StripeWebhook {

     /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

   

    public function __construct(Application $app, Config $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

     /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

     public function handle ($request, Closure $next){

            try {
                WebhookSignature::verifyHeader(
                    $request->getContent(),
                    $request->header('Stripe-Signature'),
                    $this->config->get('services.stripe.webhook.secret'),
                    $this->config->get('services.stripe.webhook.tolerance')
                );
            } catch (SignatureVerification $exception) {
                echo "error verify signature";
                $this->app->abort(403);
            }

            $json = $request->getContent();

            DB::table('stripe_webhook_log')->insert(
                [
                    'data'=>$json
                ]
            );

           $data = json_decode($json, true);

           
           return $next($request);
     }
}