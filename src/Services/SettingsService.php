<?php
namespace LenandoCatalogExport\Services;

use Aws\CloudFront\Exception\Exception;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\System\Models\Webstore;
use Plenty\Plugin\Application;
use Plenty\Validation\Contracts\Attribute;
use LenandoCatalogExport\Models\Settings;
use Plenty\Modules\Frontend\Services\SystemService;
use LenandoCatalogExport\Helpers\LogHelper;


class SettingsService
{

    /** @var Application */
    private $app;

    /** @var  DataBase */
    private $db;

    /** @var  array */
    private $loadedSettings;

    /** @var $systemService SystemService */
    protected $systemService;

    private $logHelper;

    public function __construct(SystemService $systemService, Application $app, DataBase $db, LogHelper $logHelper)
    {
        $this->systemService = $systemService;
        $this->app = $app;
        $this->db = $db;
        $this->logHelper = $logHelper;
    }

    /**
     * Load a specific setting for system client by plentyId
     *
     * @param string $name
     * @param string $lang
     *
     * @return mixed|Settings
     * @throws ValidationException
     */
    public function getSetting(string $name, $lang = 'de')
    {
        $plentyId = $this->app->getPlentyId();

        if (empty($this->loadedSettings)) {
            $this->loadedSettings = $this->getSettingsForPlentyId($plentyId, $lang);
        }

        if (array_key_exists($name, $this->loadedSettings)) {
            return $this->loadedSettings[$name];
        }

        throw new ValidationException('No such setting found: ' . $name);
    }

    public function getAllSettings($lang = 'de')
    {

        $plentyId = $this->app->getPlentyId();

        if (empty($this->loadedSettings)) {
            $this->loadedSettings = $this->getSettingsForPlentyId($plentyId, $lang);
            return $this->loadedSettings;
        }

    }

    /**
     * Get client settings by specific plentyId, indicating the array conversion
     *
     * @param $plentyId
     * @param $lang
     * @param bool $convertToArray
     *
     * @return array|Settings[]
     */
    public function getSettingsForPlentyId($plentyId, $lang, bool $convertToArray = true)
    {

        $lang = $this->checkLanguage($lang);

        /** @var Settings $settings */
        $settings = $this->loadClientSettings($plentyId, $lang);

        if ($convertToArray && (count($settings) || count($manufacturerSettings) || count($stockSettings) || count($sizeAttributeSettings) || count($colorAttributeSettings))) {
            $outputArray = array();

            $availableSettings = Settings::AVAILABLE_SETTINGS;

            /** @var Settings $setting */
            foreach ($settings as $setting) {

                if (array_key_exists($setting->name, $availableSettings)) {
                    $outputArray[$setting->name] = $setting->value;
                }

            }

            $outputArray['plentyId'] = $settings[0]->plentyId;
            $outputArray['lang'] = $settings[count($settings) - 1]->lang;

            $outputArray = $this->convertSettingsToCorrectFormat($outputArray, $availableSettings);

            $outputArray['type'] = "extended";
            return $outputArray;

        }
        $settings['type'] = "normal";
        return $settings;

    }

    /**
     * Update Settings
     *
     * @param $data
     *
     * @return int
     */
    public function saveSettings($data)
    {
        $pid = $data['plentyId'];
        $lang = $data['lang'];
        unset($data['lang']);
        unset($data['plentyId']);
        if (count($data) > 0 && !empty($pid)) {
            $settingsToSave = $this->convertSettingsToCorrectFormat($data, Settings::AVAILABLE_SETTINGS);

            /** @var Settings[] $settings */
            try {
                $settings = $this->loadAllClientSettings($pid);
            } catch (\Exception $e) {
                $this->updateClient($pid);
                $settings = $this->loadAllClientSettings($pid);
            }

            $newLang = true;

            /** @var Settings $setting */
            foreach ($settings as $setting) {
                if (array_key_exists($setting->name, $settingsToSave)) {
                    $setting->value = (string)$settingsToSave[$setting->name];
                    $setting->updatedAt = date('Y-m-d H:i:s');

                    $this->db->save($setting);

                    if ($setting->name == 'name') {
                        $newLang = false;
                    }
                }
            }
            if ($newLang) {
                foreach ($settingsToSave as $name => $value) {
                    if (!in_array($name, ['feeDomestic', 'feeForeign', 'showBankData', 'plentyId', 'lang',
                        'invoiceEqualsShippingAddress', 'disallowInvoiceForGuest', 'quorumOrders', 'minimumAmount', 'maximumAmount'])) {
                        $newSetting = pluginApp(Settings::class);
                        $newSetting->plentyId = $pid;
                        $newSetting->lang = $lang;
                        $newSetting->name = $name;
                        $newSetting->value = $value;
                        $newSetting->updatedAt = date('Y-m-d H:i:s');
                        $this->db->save($newSetting);
                    }
                }
            }

            return 1;
        }

        return 0;
    }

    public function saveSetting($parameter, $value) {


        $plentyId = $this->app->getPlentyId();

        $newSetting = pluginApp(Settings::class);
        $newSetting->plentyId = $plentyId;
        $newSetting->lang = "de";
        $newSetting->name = $parameter;
        $newSetting->value = $value;
        $newSetting->updatedAt = date('Y-m-d H:i:s');
        $this->db->save($newSetting);
    }


    /**
     * Delete settings for one client
     *
     * @param integer $plentyId
     */
    public function deleteSettings($plentyId)
    {
        $this->db->query(Settings::MODEL_NAMESPACE)
            ->where("plentyId", '=', $plentyId)->delete();
    }

    /**
     * Creates initial settings by plentyId and language
     *
     * @param $plentyId
     * @param $lang
     *
     * @return array
     * @throws ValidationException
     */
    public function createInitialSettingsForPlentyId($plentyId, $lang)
    {
        $generatedSettings = $this->createLangIndependentInitialSettings($plentyId);

        foreach (Settings::AVAILABLE_SETTINGS as $setting => $type) {
            if ($setting != 'plentyId' && $setting != 'lang' && !in_array($setting, Settings::LANG_INDEPENDENT_SETTINGS)) {
                /** @var Settings $newSetting */
                $newSetting = pluginApp(Settings::class);
                $newSetting->plentyId = $plentyId;
                $newSetting->lang = $lang;
                $newSetting->name = $setting;

                if (array_key_exists($lang, Settings::SETTINGS_DEFAULT_VALUES)) {
                    $newSetting->value = (string)Settings::SETTINGS_DEFAULT_VALUES[$lang][$setting];
                } elseif (array_key_exists(Settings::DEFAULT_LANGUAGE, Settings::SETTINGS_DEFAULT_VALUES)) {
                    $newSetting->value = (string)Settings::SETTINGS_DEFAULT_VALUES[Settings::DEFAULT_LANGUAGE][$setting];
                } else {
                    throw new ValidationException('No such default values for language: ' . $lang . ' for ' . $setting);
                }

                $newSetting->updatedAt = date('Y-m-d H:i:s');

                $generatedSettings[] = $this->db->save($newSetting);
            }
        }

        return $generatedSettings;
    }

    /**
     * Create initial language independent settings
     *
     * @param $plentyId
     *
     * @return array
     */
    private function createLangIndependentInitialSettings($plentyId)
    {
        $generatedSettings = array();

        /** @var Settings[] $storedSettings */
        $storedSettings = $this->db->query(Settings::MODEL_NAMESPACE)->where('plentyId', '=', $plentyId)
            ->where('lang', '=', '')->get();

        $settingIds = array();
        /** @var Settings $storedSetting */
        foreach ($storedSettings as $storedSetting) {
            $settingIds[$storedSetting->name] = $storedSetting->id;
        }

        foreach (Settings::LANG_INDEPENDENT_SETTINGS as $setting) {
            if ($setting != 'plentyId' && $setting != 'lang') {
                /** @var Settings $newSetting */
                $newSetting = pluginApp(Settings::class);
                if (array_key_exists($setting, $settingIds) && !empty($settingIds[$setting])) {
                    $newSetting->id = $settingIds[$setting];
                }

                $newSetting->plentyId = $plentyId;
                $newSetting->lang = '';
                $newSetting->name = $setting;
                $newSetting->value = (string)Settings::SETTINGS_DEFAULT_VALUES[$setting];
                $newSetting->updatedAt = date('Y-m-d H:i:s');

                $generatedSettings[] = $this->db->save($newSetting);
            }
        }

        return $generatedSettings;
    }

    /**
     * Get available clients of the system
     *
     * @return array
     */
    public function getClients()
    {
        /** @var WebstoreRepositoryContract $wsRepo */
        $wsRepo = pluginApp(WebstoreRepositoryContract::class);

        $clients = array();

        /** @var Webstore[] $result */
        $result = $wsRepo->loadAll();

        /** @var Webstore $record */
        foreach ($result as $record) {
            if ($record->storeIdentifier > 0)
                $clients[] = $record->storeIdentifier;
        }

        return $clients;
    }

    /**
     * Get available clients of the system
     *
     * @return array
     */
    public function getInvoiceClients()
    {
        /** @var WebstoreRepositoryContract $wsRepo */
        $wsRepo = pluginApp(WebstoreRepositoryContract::class);

        $clients = array();

        /** @var Webstore[] $result */
        $result = $wsRepo->loadAll();

        /** @var Webstore $record */
        foreach ($result as $record) {
            if ($record->storeIdentifier > 0) {
                $settings = $this->clientSettingsExist($record->storeIdentifier);
                if ($settings) {
                    $clients[] = $record->storeIdentifier;
                }
            }
        }

        return $clients;
    }


    /**
     * Checks if input language is valid language, instead return default language
     *
     * @param $lang
     *
     * @return string
     */
    private function checkLanguage($lang)
    {
        if (!in_array($lang, Settings::AVAILABLE_LANGUAGES)) {
            $lang = Settings::DEFAULT_LANGUAGE;
        }
        return $lang;
    }

    /**
     * Load settings for specified system clients by plentyId and language
     *
     * @param $plentyId
     * @param $lang
     *
     * @return Settings[]
     * @throws ValidationException
     */
    private function loadClientSettings($plentyId, $lang)
    {

        /** @var Query $query */
        $query = $this->db->query(Settings::MODEL_NAMESPACE);
        $query->where('plentyId', '=', $plentyId)->where('lang', '=', $lang);
        $query->orWhere('lang', '=', '')->where('plentyId', '=', $plentyId);

        /** @var Settings[] $clientSettings */
        $clientSettings = $query->get();

        if (!count($clientSettings)) {
            $clientSettings = $query->get();
        }

        if (!count($clientSettings)) {
            throw new ValidationException('Error loading Settings');
        }

        return $clientSettings;
    }

    /**
     * Load settings for specified system clients by plentyId
     *
     * @param $plentyId
     *
     * @return Settings[]
     * @throws ValidationException
     */
    private function loadAllClientSettings($plentyId)
    {

        /** @var Query $query */
        $query = $this->db->query(Settings::MODEL_NAMESPACE);
        $query->where('plentyId', '=', $plentyId);

        /** @var Settings[] $clientSettings */
        $clientSettings = $query->get();

        if (!count($clientSettings)) {
            $clientSettings = $query->get();
        }

        if (!count($clientSettings)) {
            throw new ValidationException('Error loading Settings');
        }

        return $clientSettings;
    }

    /**
     * Check if settings exist for plentyId
     *
     * @param $plentyId
     *
     * @return boolean
     */
    public function clientSettingsExist($plentyId)
    {
        /** @var Query $query */
        $query = $this->db->query(Settings::MODEL_NAMESPACE);
        $query->where('plentyId', '=', $plentyId);
        return $query->count();
    }

    /**
     * Creates new settings for clients which are not in the DB but available in the system
     */
    public function updateClients()
    {
        $clients = $this->getClients();

        foreach ($clients as $plentyId) {
            /** @var Settings[] $query */
            $query = $this->db->query(Settings::MODEL_NAMESPACE)
                ->where('plentyId', '=', $plentyId)->get();

            if (!count($query) > 0 || !$this->areAllLanguagesAvailable($query)) {
                $storedLangs = $this->detectStoredLanguages($query);

                foreach (Settings::AVAILABLE_LANGUAGES as $lang) {
                    if (!in_array($lang, $storedLangs)) {
                        $this->createInitialSettingsForPlentyId($plentyId, $lang);
                    }
                }
            }
        }

    }

    /**
     * Creates new settings for clients which are not in the DB but available in the system
     *
     * @param integer $plentyId
     *
     * @throws ValidationException
     */
    public function updateClient($plentyId)
    {
        /** @var Settings[] $query */
        $query = $this->db->query(Settings::MODEL_NAMESPACE)
            ->where('plentyId', '=', $plentyId)->get();

        if (!count($query) > 0 || !$this->areAllLanguagesAvailable($query)) {
            $storedLangs = $this->detectStoredLanguages($query);

            foreach (Settings::AVAILABLE_LANGUAGES as $lang) {
                if (!in_array($lang, $storedLangs)) {
                    $this->createInitialSettingsForPlentyId($plentyId, $lang);
                }
            }
        }

    }

    /**
     * Checks if all defined languages are stored in the given settings model
     *
     * @param Settings[] $settings
     *
     * @return bool
     */
    private function areAllLanguagesAvailable(array $settings)
    {
        $languages = $this->detectStoredLanguages($settings);

        foreach (Settings::AVAILABLE_LANGUAGES as $lang) {
            if (!in_array($lang, $languages)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Detects all languages contained in the settings model
     *
     * @param Settings[] $settings
     *
     * @return array
     */
    private function detectStoredLanguages(array $settings)
    {
        $storedLanguages = array();

        /** @var Settings $setting */
        foreach ($settings as $setting) {
            if (!in_array($setting->lang, $storedLanguages)) {
                $storedLanguages[] = $setting->lang;
            }
        }

        return $storedLanguages;
    }


    /**
     * Convert settings of type string to the correct format defined in Settings.php
     * NOTE: Array types only can be of 1 value type, e.g. float
     *
     * @param array $settings
     * @param array $format
     *
     * @return array
     */
    private function convertSettingsToCorrectFormat(array $settings, array $format)
    {
        $convertedSettings = array();
        foreach ($format as $setting => $type) {
            if (!is_array($type)) {
                $convertedSettings[$setting] = $this->setType($settings[$setting], $type);
            }
        }

        return $convertedSettings;
    }

    /**
     * settype() is not allowed, this method should do nearly the same except for array / object / null.
     *
     * @param $value
     * @param $type
     *
     * @return bool|float|int|string
     */
    private function setType($value, $type)
    {
        switch ($type) {
            case "boolean":
                return $value == 0 ? false : true;
            case "bool":
                return $value == 0 ? false : true;
            case "integer":
                return (int)$value;
            case "int":
                return (int)$value;
            case "float":
                return (float)$value;
            case "string":
                return (string)$value;
        }
    }

    /**
     * Load the activated shipping countries for plentyId
     *
     * @return mixed
     */

    /**
     * Load the current activated shipping countries
     *
     * @return mixed|Settings
     * @throws ValidationException
     */
    public function getShippingCountries()
    {

        return $this->getShippingCountriesByPlentyId();
    }

}
