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

namespace nystudio107\instantanalytics\helpers;

use Craft;
use craft\helpers\UrlHelper;
use nystudio107\instantanalytics\InstantAnalytics;
use nystudio107\seomatic\Seomatic;

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
        if (!InstantAnalytics::$seomaticPlugin && Seomatic::$settings->renderEnabled) {
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
}
