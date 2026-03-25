<?php

namespace App\Providers;

use App\Models\Tracking;
use App\Models\Vessel;
use App\Models\VesselStatus;
use App\Models\VesselType;
use App\Policies\TrackingPolicy;
use App\Policies\VesselPolicy;
use App\Policies\VesselStatusPolicy;
use App\Policies\VesselTypePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    protected $policies = [
        Vessel::class   => VesselPolicy::class,
        Tracking::class => TrackingPolicy::class,
        VesselType::class   => VesselTypePolicy::class,
        VesselStatus::class => VesselStatusPolicy::class,
    ];


    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
