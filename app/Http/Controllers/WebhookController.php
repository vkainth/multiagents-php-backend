<?php
namespace App\Http\Controllers;
 
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use App\Models\Auth\FirebaseUser;
use Stripe\Subscription as StripeSubscription;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Stripe\Customer as StripeCustomer;
 
class WebhookController extends CashierController
{

    protected static $stripeKey;
    protected $stripeFreeItemPriceId = "price_1OEdFCJMQ9rLXPTOZDWcVFzF";
    
    public static function getStripeKey()
    {
        if (static::$stripeKey) {
            return static::$stripeKey;
        }

        if ($key = getenv('STRIPE_SECRET')) {
            return $key;
        }

        return config('services.stripe.secret');
    }


    /**
     * Handle invoice payment succeeded.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    
    protected function handleCustomerSubscriptionUpdated(array $payload){

        echo "customer: ".$payload['data']['object']['customer']."\n";
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            echo "stripe user found \n";
            $data = $payload['data']['object'];
            $subscription = Subscription::where('stripe_id', $data['id'])->where('firebase_user_id', $user->id)->first();
            
            if($subscription){
            
                // Quantity...
                if (isset($data['quantity'])) {
                    $subscription->quantity = $data['quantity'];
                }

                // Plan...
                if (isset($data['plan']['id'])) {
                    $subscription->stripe_plan = $data['plan']['id'];
                    $subscription->stripe_price = $data['plan']['id'];
                    
                    if($data['plan']['id'] == $this->stripeFreeItemPriceId){
                        $subscription->name = "free";
                        $subscription->type = "free";
                    }
                    else{
                        $subscription->name = "premium";
                        $subscription->type = "premium";
                    }
                }

                // Trial ending date...
                if (isset($data['trial_end'])) {
                    $trial_ends = Carbon::createFromTimestamp($data['trial_end']);

                    if (! $subscription->trial_ends_at || $subscription->trial_ends_at->ne($trial_ends)) {
                        $subscription->trial_ends_at = $trial_ends;
                    }
                }

                if(isset($data['current_period_end']) && $data['current_period_end']){
                    $subscription->ends_at = Carbon::createFromTimestamp($data['current_period_end']);
                }

                // Cancellation date...
                if (isset($data['cancel_at_period_end']) && $data['cancel_at_period_end']) {

                    $subscription->ends_at = ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture())? $subscription->trial_ends_at: Carbon::createFromTimestamp($data['current_period_end']);
                }

                $subscription->save();
            }
        }

    }

    protected function handleCustomerSubscriptionCreated(array $payload){
        $customer = $payload['data']['object']['customer'];
        $subscription_id = $payload['data']['object']['id'];
        echo "subscription: ". $subscription_id."\n";
        $db_subscription = Subscription::where('stripe_id', $subscription_id)->first();
        if(!$db_subscription){
            echo "no subscription found.\n";
            echo "passing to checkout session completed\n";
            $this->handleCheckoutSessionCompleted($payload);
        }
    }

    protected function handleCheckoutSessionCompleted(array $payload){
        $client_reference_id  = null;
        $customer_email = null;

        if(array_key_exists('customer_email', $payload['data']['object'])){
            $customer_email = $payload['data']['object']['customer_email'];
        }

        if(array_key_exists('client_reference_id', $payload['data']['object'])){
            $client_reference_id = $payload['data']['object']['client_reference_id'];
        }
       
        $customer = $payload['data']['object']['customer'];

        echo "customer: ".$customer."\n";

        $user = null;
        
        if($client_reference_id){
            $user = FirebaseUser::where('uid', $client_reference_id)->first();
            $user->stripe_id = $payload['data']['object']['customer'];
            $user->save();
        }
        elseif($customer_email){
            $user = FirebaseUser::where('email', $customer_email)->first();
            $user->stripe_id = $payload['data']['object']['customer'];
            $user->save();
        }
        else{
            $user = FirebaseUser::where('stripe_id', $customer)->first();
        }

        // if($user){
        //     echo "user found. \n";
        //     echo "type:". $payload['type']."\n";
            if($payload['type'] == 'customer.subscription.created'){
                $subscription_id = $payload['data']['object']['id'];
            }
            else{
                $subscription_id = $payload['data']['object']['subscription'];
            }
            
            $subscription = StripeSubscription::retrieve($subscription_id, $this->getStripeKey());

            $db_subscription = Subscription::where('stripe_id', $subscription_id)->first();

            if(!$db_subscription){
                $db_subscription = new Subscription();
            }
                    
            //$db_subscription->firebase_user_id = $user->id;

            if($user){
                $db_subscription->firebase_user_id = $user->id;
            }

            if(array_key_exists('customer_details', $payload['data']['object'])){
                $db_subscription->user_stripe_email = $payload['data']['object']['customer_details']['email'];
            }
            else{
                $customer_detail = StripeCustomer::retrieve($customer, $this->getStripeKey());
                $db_subscription->user_stripe_email = $customer_detail->email;
            }

            $db_subscription->stripe_id = $subscription_id;
            $db_subscription->name = "premium";
            $db_subscription->type = "premium";
            $db_subscription->user_stripe_id = $customer;

            if(array_key_exists('customer_details', $payload['data']['object'])){
                $db_subscription->user_stripe_email = $payload['data']['object']['customer_details']['email'];
            }
            else{
                $customer_detail = StripeCustomer::retrieve($customer, $this->getStripeKey());
                $db_subscription->user_stripe_email = $customer_detail->email;
            }

            // if($payload['data']['object']['items']['data'][0]['price']['lookup_key'] == 'free_monthly'){
            //     $db_subscription->name = "free";
            // }

            if($subscription->plan->id == $this->stripeFreeItemPriceId){
                $db_subscription->name = "free";
                $db_subscription->type = "free";
            }

            if (isset($subscription->quantity)) {
                $db_subscription->quantity = $subscription->quantity;
            }

            if (isset($subscription->plan->id)) {
                $db_subscription->stripe_plan = $subscription->plan->id;
                $db_subscription->stripe_price = $subscription->plan->id;
            }

            // Trial ending date...
            if (isset($subscription->trial_end)) {
                $db_subscription->trial_ends_at = Carbon::createFromTimestamp($subscription->trial_end);
            }

              //ending date...
            if (isset($subscription->current_period_end)) {
                $db_subscription->ends_at = Carbon::createFromTimestamp($subscription->current_period_end);
            }

            $db_subscription->save();


        // }
    }

    protected function handleCustomerCreated(array $payload){
        $customer_email = $payload['data']['object']['email'];
        $customer_stripe_id = $payload['data']['object']['id'];

        if($customer_email && $customer_stripe_id){
            $user = FirebaseUser::where('email', $customer_email)->first();
            if($user){
                $user->stripe_id = $customer_stripe_id;
                $user->save();
            }
        }
    }

}