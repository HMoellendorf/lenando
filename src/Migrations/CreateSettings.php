<?php
/**
 * Created by IntelliJ IDEA.
 * User: ckunze
 * Date: 23/2/17
 * Time: 15:54
 */

namespace lenando\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use lenando\Models\Settings;
use lenando\Services\SettingsService;

class CreateSettings
{

    public function run(Migrate $migrate)
    {
            $migrate->createTable(Settings::class);
            $this->setInitialSettings();
    }

    private function setInitialSettings()
    {
        /** @var SettingsService $service */
        $service = pluginApp(SettingsService::class);
        $clients = $service->getClients();

        foreach(Settings::AVAILABLE_LANGUAGES as $lang)
        {
            foreach ($clients as $plentyId)
            {
                $service->createInitialSettingsForPlentyId($plentyId, $lang);
            }
        }
    }

}
