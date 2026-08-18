<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
// use App\Models\Auth\FirebaseUser;
use Laravel\Cashier\Cashier;
use Stripe\Stripe;
use App\Models\Places;
use App\Observers\PlacesObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // --------- as BCCHv1 [BEGINS] ------------
        // if($this->app->environment() == 'local') {
        //     $this->app->register('\Laracademy\Generators\GeneratorsServiceProvider');
        // }
        // --------- as BCCHv1 [ENDS] ------------
        
        // $this->app->usePublicPath(base_path().'/../public_html/public'); // lv-11+ > point to non-default public folder
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Places::observe(PlacesObserver::class);

        config(['debugbar.enabled' => ('12376' == request()->cookie('user_id'))]);
        // --------- as BCCHv1 [BEGINS] ------------
        
        // \URL::forceScheme('https'); 

        // // Cashier::useCustomerModel(FirebaseUser::class);
        // Cashier::useCurrency('cad', '$');

        Validator::extend('phone', function($attribute, $value, $parameters, $validator) {
            return preg_match('%^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$%i', $value) && strlen($value) >= 10;
        });

        Validator::replacer('phone', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute',$attribute, ':attribute is invalid phone number');
        });

        Validator::extend('phone1', function($attribute, $value, $parameters, $validator) {
            return preg_match('%^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$%i', $value) && strlen($value) >= 10;
        });

        Validator::replacer('phone1', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute',$attribute, ':attribute is invalid phone number');
        });

        Validator::extend('url1', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/', $value);
        });

        Validator::replacer('url1', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute',$attribute, ':attribute is invalid url');
        });

        // setlocale(LC_MONETARY, 'en_US.UTF-8');
        // --------- as BCCHv1 [ENDS] ------------
        
        Gate::define('dev-dj', function ($user=null) {
            return in_array($user->email??auth()->user()?->email??'-', ['diljeet@pixilink.com']);
        });
        Gate::define('dev-dj-approve', function ($user=null) {
            return in_array($user->email??auth()->user()?->email??'-', ['diljeet@pixilink.com','varinder@pixilink.com']);
        });
        Gate::define('pixi-dev', function ($user=null) {
            return in_array($user->email??auth()->user()?->email??'-', ['diljeet@pixilink.com']);
        });
        Gate::define('pixi-devs', function ($user=null) {
            return in_array($user->email??auth()->user()?->email??'-', ['diljeet@pixilink.com','varinder@pixilink.com','parvinder@pixilink.com']);
        });
        Gate::define('pixi-member', function ($user=null) {
            return in_array(substr(strstr($user->email??auth()->user()?->email??'-','@'),1), ['pixilink.com','bccondosandhomes.com','bccondos.net','6717000.com']);
        });

        Stripe::setApiKey(config('services.stripe.secret'));
    }
}
