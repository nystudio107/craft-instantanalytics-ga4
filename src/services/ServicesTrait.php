<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2022 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\services;

use craft\helpers\ArrayHelper;
use nystudio107\instantanalyticsGa4\assetbundles\instantanalytics\InstantAnalyticsAsset;
use nystudio107\instantanalyticsGa4\services\Commerce as CommerceService;
use nystudio107\pluginvite\services\VitePluginService;
use yii\base\InvalidConfigException;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     4.0.0
 *
 * @property Ga4 $ga4
 * @property CommerceService $commerce
 * @property VitePluginService $vite
 */
trait ServicesTrait
{
    public function __construct($id, $parent = null, array $config = [])
    {
        // Constants aren't allowed in traits until PHP >= 8.2
        // so we can't extract it from the passed in $config
        $majorVersion = '1';
        // Dev server container name & port are based on the major version of this plugin
        $devPort = 3000 + (int)$majorVersion;
        $versionName = 'v' . $majorVersion;
        // Merge in the passed config, so it our config can be overridden by Plugins::pluginConfigs['vite']
        // ref: https://github.com/craftcms/cms/issues/1989
        $config = ArrayHelper::merge([
            'components' => [
                'ga4' => Ga4::class,
                'commerce' => CommerceService::class,
                // Register the vite service
                'vite' => [
                    'assetClass' => InstantAnalyticsAsset::class,
                    'checkDevServer' => true,
                    'class' => VitePluginService::class,
                    'devServerInternal' => 'http://craft-instantanalytics-' . $versionName . '-buildchain-dev:' . $devPort,
                    'devServerPublic' => 'http://localhost:' . $devPort,
                    'errorEntry' => 'src/js/app.ts',
                    'useDevServer' => true,
                ],
            ]
        ], $config);

        parent::__construct($id, $parent, $config);
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the GA4 service
     *
     * @return Ga4 The GA4 service
     * @throws InvalidConfigException
     */
    public function getGa4(): Ga4
    {
        return $this->get('ga4');
    }

    /**
     * Returns the commerce service
     *
     * @return CommerceService The commerce service
     * @throws InvalidConfigException
     */
    public function getCommerce(): CommerceService
    {
        return $this->get('commerce');
    }

    /**
     * Returns the vite service
     *
     * @return VitePluginService The vite service
     * @throws InvalidConfigException
     */
    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }
}
