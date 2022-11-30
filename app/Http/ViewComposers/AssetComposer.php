<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;

class AssetComposer
{
    /**
     * @var \Pterodactyl\Services\Helpers\AssetHashService
     */
    private $assetHashService;

    /**
     * AssetComposer constructor.
     */
    public function __construct(AssetHashService $assetHashService)
    {
        $this->assetHashService = $assetHashService;
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $drivers = [];
        $driversConfig = json_decode(app('config')->get('oauth.drivers'), true);

        foreach ($driversConfig as $driver => $options) {
            if ($options['enabled']) {
                array_push($drivers, $driver);
            }
        }

        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'oauth' => [
                'enabled' => config('oauth.enabled', false),
                'required' => config('oauth.required', 0) == 3
                    && config('oauth.disable_other_authentication_if_required', false),
                'drivers' => json_encode($drivers),
            ],
        ]);
    }
}
