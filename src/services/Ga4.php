<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\services;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Parameter\BaseParameter;
use Craft;
use craft\base\Component;
use nystudio107\instantanalyticsGa4\ga4\Analytics;
use nystudio107\instantanalyticsGa4\ga4\events\PageViewEvent;
use nystudio107\instantanalyticsGa4\helpers\Analytics as AnalyticsHelper;
use nystudio107\instantanalyticsGa4\InstantAnalytics;
use nystudio107\seomatic\helpers\Json;

/** @noinspection MissingPropertyAnnotationsInspection */

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     5.0.0
 */
class Ga4 extends Component
{

    /**
     * @var Analytics
     */
    private $_analytics;

    /**
     * @var bool
     */
    private $_pageViewSent = false;

    public function getAnalytics(): Analytics
    {
        if (!$this->_analytics) {
            $this->_analytics = \Craft::createObject(Analytics::class);
            $this->_analytics->init();
        }

        return $this->_analytics;
    }

    /**
     * Send a page view event
     */
    public function addPageViewEvent(string $url = '', string $pageTitle = ''): void
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest() && !$this->_pageViewSent) {
            $this->_pageViewSent = true;

            $pageView = $this->getPageViewEvent($url, !empty($pageTitle) ? $pageTitle : InstantAnalytics::$currentTemplate);
            $this->getAnalytics()->addEvent($pageView);

            InstantAnalytics::$plugin->logAnalyticsEvent(
                'pageView event queued for sending',
                [],
                __METHOD__
            );
        }
    }

    /**
     * Add a basic event to be sent to GA4
     *
     * @param string $url
     * @param string $eventName
     * @param array $params
     */
    public function addSimpleEvent(string $url, string $eventName, array $params): void
    {
        $baseEvent = $this->getSimpleEvent($eventName);
        $baseEvent->setDocumentPath(parse_url($url, PHP_URL_PATH));

        foreach ($params as $param => $value) {
            $baseEvent->addParam($param, new BaseParameter($value));
        }

        $this->getAnalytics()->addEvent($baseEvent);

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Simple event queued for {url} with the following parameters {params}',
            ['url' => $url, 'params' => Json::encode($params)],
            __METHOD__
        );
    }

    /**
     * Create a page view event
     *
     * @param string $url
     * @param string $pageTitle
     * @return PageViewEvent
     */
    public function getPageViewEvent(string $url = '', string $pageTitle = ''): PageViewEvent
    {
        $event = $this->getAnalytics()->create()->PageViewEvent();
        $event->setPageTitle($pageTitle);

        // If SEOmatic is installed, set the page title from it
        $seomaticTitle = AnalyticsHelper::getTitleFromSeomatic();

        if ($seomaticTitle) {
            $event->setPageTitle($seomaticTitle);
        }

        $event->setPageLocation(AnalyticsHelper::getDocumentPathFromUrl($url));

        return $event;
    }

    /**
     * Create a simple event
     *
     * @param string $eventName
     * @return BaseEvent
     */
    public function getSimpleEvent(string $eventName): BaseEvent
    {
        $baseEvent = $this->getAnalytics()->create()->BaseEvent();
        $baseEvent->setName($eventName);

        return $baseEvent;
    }
}
