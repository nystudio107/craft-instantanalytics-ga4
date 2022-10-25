<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * @author    nystudio107
 * @copyright Copyright (c) 2017 nystudio107
 * @link      http://nystudio107.com
 * @package   InstantAnalytics
 * @since     1.0.0
 */

namespace nystudio107\instantanalytics\ga4;

use Br33f\Ga4\MeasurementProtocol\Dto\Request\BaseRequest;
use Br33f\Ga4\MeasurementProtocol\HttpClient;
use Br33f\Ga4\MeasurementProtocol\Service;
use Craft;
use craft\helpers\App;
use nystudio107\instantanalytics\InstantAnalytics;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.2.0
 */
class Analytics
{
    /**
     * @var BaseRequest
     */
    private $_request;

    /**
     * @var Service
     */
    private $_service;

    public function create(): ComponentFactory
    {
        return new ComponentFactory();
    }

    public function request(): BaseRequest
    {
        if ($this->_request === null) {
            $this->_request = new BaseRequest(InstantAnalytics::$plugin->getIa()->getClientId());
        }

        return $this->_request;
    }

    public function service(): Service
    {
        if ($this->_service === null) {
            $settings = InstantAnalytics::$settings;
            $apiSecret = App::parseEnv($settings->googleAnalyticsMeasurementApiSecret);
            $measurementId = App::parseEnv($settings->googleAnalyticsMeasurementId);
            $this->_service = new Service($apiSecret, $measurementId);
            $ga4Client = new HttpClient();
            $ga4Client->setClient(Craft::createGuzzleClient());
            $this->_service->setHttpClient($ga4Client);
        }

        return $this->_service;
    }

    public function sendAnalytics()
    {
        $service = $this->service();
        $response = $service->send($this->_request);
        return $response;
    }
}
