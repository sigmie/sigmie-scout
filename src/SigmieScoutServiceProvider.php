<?php

namespace Sigmie\Scout;

use Laravel\Scout\EngineManager;
use Sigmie\Application\Client;
use Sigmie\Http\JSONClient;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SigmieScoutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('sigmie-scout')->hasConfigFile();

        resolve(EngineManager::class)->extend(
            'sigmie',
            function () {

                $config = config('sigmie-scout');

                $applicationId = $config['application_id'] ?? '';
                $adminKey = $config['admin_key'] ?? '';

                $sigmie = new Client($applicationId, $adminKey);

                return new SigmieEngine($sigmie);
            }
        );
    }
}
