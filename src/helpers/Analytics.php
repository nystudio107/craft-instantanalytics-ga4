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

namespace nystudio107\instantanalyticsGa4\helpers;

use Craft;
use craft\elements\User as UserElement;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\helpers\App;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use nystudio107\instantanalyticsGa4\InstantAnalytics;
use nystudio107\seomatic\Seomatic;
use yii\base\Exception;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     5.0.0
 */
class Analytics
{
    /**
     * If SEOmatic is installed, set the page title from it
     */
    public static function getTitleFromSeomatic(): ?string
    {
        if (!InstantAnalytics::$seomaticPlugin) {
            return null;
        }
        if (!Seomatic::$settings->renderEnabled) {
            return null;
        }
        $titleTag = Seomatic::$plugin->title->get('title');

        if ($titleTag === null) {
            return null;
        }

        $titleArray = $titleTag->renderAttributes();

        if (empty($titleArray['title'])) {
            return null;
        }

        return $titleArray['title'];
    }

    /**
     * Return a sanitized documentPath from a URL
     *
     * @param string $url
     *
     * @return string
     */
    public static function getDocumentPathFromUrl(string $url = ''): string
    {
        if ($url === '') {
            $url = Craft::$app->getRequest()->getFullPath();
        }

        // We want to send just a path to GA for page views
        if (UrlHelper::isAbsoluteUrl($url)) {
            $urlParts = parse_url($url);
            $url = $urlParts['path'] ?? '/';
            if (isset($urlParts['query'])) {
                $url .= '?' . $urlParts['query'];
            }
        }

        // We don't want to send protocol-relative URLs either
        if (UrlHelper::isProtocolRelativeUrl($url)) {
            $url = substr($url, 1);
        }

        // Strip the query string if that's the global config setting
        if (InstantAnalytics::$settings) {
            if (InstantAnalytics::$settings->stripQueryString !== null
                && InstantAnalytics::$settings->stripQueryString) {
                $url = UrlHelper::stripQueryString($url);
            }
        }

        // We always want the path to be / rather than empty
        if ($url === '') {
            $url = '/';
        }

        return $url;
    }

    /**
     * Get a PageView tracking URL
     *
     * @param $url
     * @param $title
     *
     * @return string
     * @throws Exception
     */
    public static function getPageViewTrackingUrl($url, $title): string
    {
        $urlParams = compact('url', 'title');

        $path = parse_url($url, PHP_URL_PATH);
        $pathFragments = explode('/', rtrim($path, '/'));
        $fileName = end($pathFragments);
        $trackingUrl = UrlHelper::siteUrl('instantanalytics/pageViewTrack/' . $fileName, $urlParams);

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Created pageViewTrackingUrl for: {trackingUrl}',
            [
                'trackingUrl' => $trackingUrl
            ],
            __METHOD__
        );

        return $trackingUrl;
    }

    /**
     * Get an Event tracking URL
     *
     * @param string $url
     * @param string $eventName
     * @param array $params
     * @return string
     * @throws Exception
     */
    public static function getEventTrackingUrl(
        string $url,
        string $eventName,
        array $params = [],
    ): string
    {
        $urlParams = compact('url', 'eventName', 'params');

        $fileName = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_BASENAME);
        $trackingUrl = UrlHelper::siteUrl('instantanalytics/eventTrack/' . $fileName, $urlParams);

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Created eventTrackingUrl for: {trackingUrl}',
            [
                'trackingUrl' => $trackingUrl
            ],
            __METHOD__
        );

        return $trackingUrl;
    }

    /**
     * _shouldSendAnalytics determines whether we should be sending Google
     * Analytics data
     *
     * @return bool
     */
    public static function shouldSendAnalytics(): bool
    {
        $result = true;
        $request = Craft::$app->getRequest();

        $logExclusion = static function (string $setting)
        {
            if (InstantAnalytics::$settings->logExcludedAnalytics) {
                $request = Craft::$app->getRequest();
                $requestIp = $request->getUserIP();
                InstantAnalytics::$plugin->logAnalyticsEvent(
                    'Analytics excluded for:: {requestIp} due to: `{setting}`',
                    compact('requestIp', 'setting'),
                    __METHOD__
                );
            }
        };

        if (!InstantAnalytics::$settings->sendAnalyticsData) {
            $logExclusion('sendAnalyticsData');
            return false;
        }

        if (!InstantAnalytics::$settings->sendAnalyticsInDevMode && Craft::$app->getConfig()->getGeneral()->devMode) {
            $logExclusion('sendAnalyticsInDevMode');
            return false;
        }

        if ($request->getIsConsoleRequest()) {
            $logExclusion('Craft::$app->getRequest()->getIsConsoleRequest()');
            return false;
        }

        if ($request->getIsCpRequest()) {
            $logExclusion('Craft::$app->getRequest()->getIsCpRequest()');
            return false;
        }

        if ($request->getIsLivePreview()) {
            $logExclusion('Craft::$app->getRequest()->getIsLivePreview()');
            return false;
        }

        // Check the $_SERVER[] super-global exclusions
        if (InstantAnalytics::$settings->serverExcludes !== null
            && is_array(InstantAnalytics::$settings->serverExcludes)) {
            foreach (InstantAnalytics::$settings->serverExcludes as $match => $matchArray) {
                if (isset($_SERVER[$match])) {
                    foreach ($matchArray as $matchItem) {
                        if (preg_match($matchItem, $_SERVER[$match])) {
                            $logExclusion('serverExcludes');

                            return false;
                        }
                    }
                }
            }
        }

        // Filter out bot/spam requests via UserAgent
        if (InstantAnalytics::$settings->filterBotUserAgents) {
            $crawlerDetect = new CrawlerDetect;
            // Check the user agent of the current 'visitor'
            if ($crawlerDetect->isCrawler()) {
                $logExclusion('filterBotUserAgents');

                return false;
            }
        }

        // Filter by user group
        $userService = Craft::$app->getUser();
        /** @var UserElement $user */
        $user = $userService->getIdentity();
        if ($user) {
            if (InstantAnalytics::$settings->adminExclude && $user->admin) {
                $logExclusion('adminExclude');

                return false;
            }

            if (InstantAnalytics::$settings->groupExcludes !== null
                && is_array(InstantAnalytics::$settings->groupExcludes)) {
                foreach (InstantAnalytics::$settings->groupExcludes as $matchItem) {
                    if ($user->isInGroup($matchItem)) {
                        $logExclusion('groupExcludes');

                        return false;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * getClientId handles the parsing of the _ga cookie or setting it to a
     * unique identifier
     *
     * @return string the cid
     */
    public static function getClientId(): string
    {
        $cid = '';
        if (isset($_COOKIE['_ga'])) {
            $parts = explode(".", $_COOKIE['_ga'], 4);
            if ($parts !== false) {
                $cid = implode('.', array_slice($parts, 2));
            }
        } elseif (isset($_COOKIE['_ia']) && $_COOKIE['_ia'] !== '') {
            $cid = $_COOKIE['_ia'];
        } else {
            // Generate our own client id, otherwise.
            $cid = static::gaGenUUID() . '.1';
        }

        if (InstantAnalytics::$settings->createGclidCookie && !empty($cid)) {
            setcookie('_ia', $cid, strtotime('+' . InstantAnalytics::$settings->sessionDuration . ' minutes'), '/'); // Two years
        }

        return $cid;
    }

    /**
     * getSessionCookie handles the parsing of the _ga_*MEASUREMENT ID* cookie
     * unique identifier of session ID & number
     *
     * @return null|array $sessionCookie
     */
    public static function getSessionCookie(): ?array
    {
        $measurementId = App::parseEnv(InstantAnalytics::$settings->googleAnalyticsMeasurementId);
        $cookieName = '_ga_' . StringHelper::removeLeft($measurementId, 'G-');
        if (isset($_COOKIE[$cookieName])) {
            $sessionCookie = null;

            $parts = explode(".", $_COOKIE[$cookieName], 5);
            if ($parts && count($parts) > 1) {
                $sessionCookie = implode('.', array_slice($parts, 2, 2));
            }

            if (str_contains($sessionCookie, '.')) {
                [$sessionId, $sessionNumber] = explode('.', $sessionCookie);
                return ['sessionId' => $sessionId, 'sessionNumber' => $sessionNumber];
            }
        }

        return null;
    }

    /**
     * Get the user id.
     *
     * @return string
     */
    public static function getUserId(): string
    {
        $userId = Craft::$app->getUser()->getId();

        if (!$userId) {
            return '';
        }

        return $userId;
    }

    /**
     * gaGenUUID Generate UUID v4 function - needed to generate a CID when one
     * isn't available
     *
     * @return string The generated UUID
     */
    protected static function gaGenUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
