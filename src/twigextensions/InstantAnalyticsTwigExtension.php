<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\twigextensions;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use Craft;
use craft\helpers\Template;
use nystudio107\instantanalyticsGa4\ga4\events\PageViewEvent;
use nystudio107\instantanalyticsGa4\helpers\Analytics;
use nystudio107\instantanalyticsGa4\InstantAnalytics;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\Exception;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests,
 * operators, global variables, and functions. You can even extend the parser
 * itself with node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class InstantAnalyticsTwigExtension extends AbstractExtension implements GlobalsInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'InstantAnalytics';
    }

    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        $globals = [];
        $view = Craft::$app->getView();
        if ($view->getIsRenderingPageTemplate()) {
            $request = Craft::$app->getRequest();
            if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
                // Return our Analytics object as a Twig global
                $globals = [
                    'instantAnalytics' => InstantAnalytics::$plugin->ga4->getAnalytics(),
                ];
            }
        }

        return $globals;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pageViewEvent', [$this, 'pageViewEvent']),
            new TwigFilter('simpleAnalyticsEvent', [$this, 'simpleEvent']),
            new TwigFilter('pageViewTrackingUrl', [$this, 'pageViewTrackingUrl']),
            new TwigFilter('eventTrackingUrl', [$this, 'eventTrackingUrl']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pageViewEvent', [$this, 'pageViewEvent']),
            new TwigFunction('simpleAnalyticsEvent', [$this, 'simpleEvent']),
            new TwigFunction('pageViewTrackingUrl', [$this, 'pageViewTrackingUrl']),
            new TwigFunction('eventTrackingUrl', [$this, 'eventTrackingUrl']),
        ];
    }

    /**
     * Get a PageView analytics object
     *
     * @param string $url
     * @param string $title
     *
     * @return PageViewEvent object
     */
    public function pageViewEvent(string $url = '', string $title = ''): PageViewEvent
    {
        return InstantAnalytics::$plugin->ga4->getPageViewEvent($url, $title);
    }

    /**
     * Get an Event analytics object
     *
     * @param string $eventName
     * @return BaseEvent
     */
    public function simpleEvent(string $eventName = ''): BaseEvent
    {
        return InstantAnalytics::$plugin->ga4->getSimpleEvent($eventName);
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
        return Template::raw(Analytics::getPageViewTrackingUrl($url, $title));
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
        string $eventName,
        array $params = []
    ): Markup
    {
        return Template::raw(Analytics::getEventTrackingUrl($url, $eventName, $params));
    }
}
