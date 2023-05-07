<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalytics\services;

use Craft;
use craft\base\Component;
use craft\elements\User as UserElement;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use nystudio107\instantanalytics\ga4\Analytics;
use nystudio107\instantanalytics\ga4\events\PageViewEvent;
use nystudio107\instantanalytics\helpers\Analytics as AnalyticsHelper;
use nystudio107\instantanalytics\helpers\IAnalytics;
use nystudio107\instantanalytics\InstantAnalytics;
use nystudio107\seomatic\Seomatic;
use yii\base\Exception;
use function array_slice;
use function is_array;

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

    public function getAnalytics(): Analytics
    {
        if (!$this->_analytics) {
            $this->_analytics = \Craft::createObject(Analytics::class);
        }

        return $this->_analytics;
    }

    public function getPageViewEvent(string $pageTitle = ''): PageViewEvent
    {
        $event = $this->getAnalytics()->create()->PageViewEvent();
        $event->setPageTitle($pageTitle);

        // If SEOmatic is installed, set the page title from it
        $seomaticTitle = AnalyticsHelper::getTitleFromSeomatic();

        if ($seomaticTitle) {
            $event->setPageTitle($seomaticTitle);
        }

        $event->setPageLocation(AnalyticsHelper::getDocumentPathFromUrl());

        return $event;
    }


}
