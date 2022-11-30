<?php

namespace Pterodactyl\Providers;

use SocialiteProviders\Manager\SocialiteWasCalled;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Observers\UserObserver;
use Pterodactyl\Observers\ServerObserver;
use Pterodactyl\Observers\SubuserObserver;
use Pterodactyl\Observers\EggVariableObserver;
use Pterodactyl\Listeners\Auth\AuthenticationListener;
use Pterodactyl\Events\Server\Installed as ServerInstalledEvent;
use Pterodactyl\Notifications\ServerInstalled as ServerInstalledNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ServerInstalledEvent::class => [ServerInstalledNotification::class],
    ];

    protected $subscribe = [
        AuthenticationListener::class,
    ];

    public function boot()
    {
        parent::boot();

        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);
        Subuser::observe(SubuserObserver::class);
        EggVariable::observe(EggVariableObserver::class);

        // Add dynamic Socialite providers from settings
        if (!app('config')->get('oauth.enabled')) {
            return;
        }

        $drivers = json_decode(app('config')->get('oauth.drivers'), true);

        $listeners = [];

        foreach ($drivers as $options) {
            if (!array_has($options, 'listener')) {
                continue;
            }

            $listener = $options['listener'];
            if (strpos($listener, '@') !== false) {
                $class = explode('@', $listener)[0];
                $method = explode('@', $listener)[1];

                if (method_exists($class, $method)) {
                    array_push($listeners, $listener);
                }
            }
        }

        foreach (array_unique($listeners) as $listener) {
            app('events')->listen(SocialiteWasCalled::class, $listener);
        }
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
