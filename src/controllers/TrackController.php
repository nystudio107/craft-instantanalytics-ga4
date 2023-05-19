<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\controllers;

use craft\web\Controller;
use nystudio107\instantanalyticsGa4\InstantAnalytics;

/**
 * TrackController
 *
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class TrackController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|bool|int $allowAnonymous = [
        'track-page-view-url',
        'track-event-url'
    ];

    // Public Methods
    // =========================================================================

    /**
     * @param string $url
     * @param string $title
     */
    public function actionTrackPageViewUrl(string $url, string $title): void
    {
        InstantAnalytics::$plugin->ga4->addPageViewEvent($url, $title);
        $this->redirect($url, 200);
    }

    /**
     * @param string $url
     * @param string $eventName
     * @param array $params
     */
    public function actionTrackEventUrl(
        string $url,
        string $eventName = '',
        array  $params = [],
    ): void
    {
        InstantAnalytics::$plugin->ga4->addSimpleEvent($url, $eventName, $params);

        $this->redirect($url, 200);
    }
}
