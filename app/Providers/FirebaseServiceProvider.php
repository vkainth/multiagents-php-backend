<?php

namespace App\Providers;

use App\Repository\FirebaseRepository;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('App\Repository\FirebaseRepository', function($app){
            return new FirebaseRepository();
        });
    }
}
