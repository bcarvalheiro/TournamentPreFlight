<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use SocialiteProviders\Manager\ServiceProvider as SocialiteProvidersServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SocialiteProvidersServiceProvider::class,
];
