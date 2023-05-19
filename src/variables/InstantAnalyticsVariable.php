<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\variables;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\Template;
use nystudio107\instantanalyticsGa4\ga4\Analytics;
use nystudio107\instantanalyticsGa4\ga4\events\PageViewEvent;
use nystudio107\instantanalyticsGa4\helpers\Analytics as AnalyticsHelper;
use nystudio107\instantanalyticsGa4\InstantAnalytics;
use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;
use Twig\Markup;
use yii\base\Exception;

/**
 * Instant Analytics Variable
 *
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class InstantAnalyticsVariable implements ViteVariableInterface
{
    use ViteVariableTrait;

    // Public Methods
    // =========================================================================

    /**
     * Get a PageView Event
     *
     * @param string $url
     * @param string $title
     *
     * @return PageViewEvent
     */
    public function pageViewEvent(string $url = '', string $title = ''): PageViewEvent
    {
        return InstantAnalytics::$plugin->ga4->getPageViewEvent($url, $title);
    }

    /**
     * Get a simple event
     *
     * @param string $eventName
     * @return BaseEvent
     */
    public function simpleEvent(string $eventName = ''): BaseEvent
    {
        return InstantAnalytics::$plugin->ga4->getSimpleEvent($eventName);
    }

    /**
     * Return the GA4 Analytics object
     *
     * @return Analytics
     */
    public function ga4(): Analytics
    {
        return InstantAnalytics::$plugin->ga4->getAnalytics();
    }

    /**
     * @param Product|Variant $productVariant the Product or Variant
     */
    public function addCommerceProductView($productVariant): void
    {
        InstantAnalytics::$plugin->commerce->addCommerceProductImpression($productVariant);
    }

    /**
     * Get a PageView tracking URL
     *
     * @param $url
     * @param $title
     *
     * @return Markup
     * @throws Exception
     */
    public function pageViewTrackingUrl($url, $title): Markup
    {
        return Template::raw(AnalyticsHelper::getPageViewTrackingUrl($url, $title));
    }

    /**
     * Get an Event tracking URL
     *
     * @param string $url
     * @param string $eventName
     * @param array $params
     * @return Markup
     * @throws Exception
     */
    public function eventTrackingUrl(
        string $url,
        string $eventName = '',
        array  $params = []
    ): Markup
    {
        return Template::raw(AnalyticsHelper::getEventTrackingUrl($url, $eventName, $params));
    }

}
