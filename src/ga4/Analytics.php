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

use Br33f\Ga4\MeasurementProtocol\Dto\Event\AbstractEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Request\BaseRequest;
use Br33f\Ga4\MeasurementProtocol\Dto\Response\BaseResponse;
use Br33f\Ga4\MeasurementProtocol\HttpClient;
use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use nystudio107\instantanalytics\InstantAnalytics;
use nystudio107\seomatic\Seomatic;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.2.0
 *
 * @method Analytics setAllowGoogleSignals(string $value)
 * @method Analytics setAllowAdPersonalizationSignals(string $value)
 * @method Analytics setCampaignContent(string $value)
 * @method Analytics setCampaignId(string $value)
 * @method Analytics setCampaignMedium(string $value)
 * @method Analytics setCampaignName(string $value)
 * @method Analytics setCampaignSource(string $value)
 * @method Analytics setCampaignTerm(string $value)
 * @method Analytics setCampaign(string $value)
 * @method Analytics setClientId(string $value)
 * @method Analytics setContentGroup(string $value)
 * @method Analytics setCookieDomain(string $value)
 * @method Analytics setCookieExpires(string $value)
 * @method Analytics setCookieFlags(string $value)
 * @method Analytics setCookiePath(string $value)
 * @method Analytics setCookiePrefix(string $value)
 * @method Analytics setCookieUpdate(string $value)
 * @method Analytics setLanguage(string $value)
 * @method Analytics setPageLocation(string $value)
 * @method Analytics setPageReferrer(string $value)
 * @method Analytics setPageTitle(string $value)
 * @method Analytics setSendPageView(string $value)
 * @method Analytics setScreenResolution(string $value)
 * @method Analytics setUserId(string $value)
 */
class Analytics
{
    /**
     * @var BaseRequest
     */
    private BaseRequest $_request;

    /**
     * @var Service|null|false
     */
    private mixed $_service;

    /**
     * @var string|null
     */
    private ?string $_affiliation;

    /**
     * Component factory for creating events.
     *
     * @return ComponentFactory
     */
    public function create(): ComponentFactory
    {
        return new ComponentFactory();
    }

    /**
     * Add an event to be sent to Google
     *
     * @param AbstractEvent $event
     * @return BaseRequest
     */
    public function addEvent(AbstractEvent $event): BaseRequest
    {
        return $this->request()->addEvent($event);
    }

    /**
     * Send the events collected so far.
     *
     * @return BaseResponse|null
     * @throws \Br33f\Ga4\MeasurementProtocol\Exception\HydrationException
     * @throws \Br33f\Ga4\MeasurementProtocol\Exception\ValidationException
     */
    public function sendCollectedEvents(): ?BaseResponse
    {
        $service = $this->service();

        if (!$service) {
            return null;
        }

        $request = $this->request();
        $eventCount = count($request->getEvents()->getEventList());

        if (!InstantAnalytics::$settings->sendAnalyticsData) {
            InstantAnalytics::$plugin->logAnalyticsEvent(
                'Analytics not enabled - skipped sending {count} events',
                ['count' => $eventCount],
                __METHOD__
            );

            return null;
        }

        if ($eventCount === 0) {
            InstantAnalytics::$plugin->logAnalyticsEvent(
                'No events collected to send',
                [],
                __METHOD__
            );

            return null;
        }

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Sending {count} analytics events',
            ['count' => $eventCount],
            __METHOD__
        );

        $response = $service->send($request);

        // Clear events already sent from the list.
        $request->getEvents()->setEventList([]);

        return $response;
    }

    /**
     * Set affiliation for all the events that incorporate Commerce Product info for the remaining duration of request.
     *
     * @param string $affiliation
     * @return $this
     */
    public function setAffiliation(string $affiliation): self
    {
        $this->_affiliation = $affiliation;
        return $this;
    }

    public function getAffiliation(): ?string
    {
        return $this->_affiliation;
    }

    public function addCommerceProductImpression(Product|Variant $productVariant, $index, $listName) {
        InstantAnalytics::$plugin->commerce->addCommerceProductImpression($productVariant, $index, $listName);
    }

    public function addCommerceProductListImpression(array $products, $listName) {
        InstantAnalytics::$plugin->commerce->addCommerceProductListImpression($products, $listName);
    }

    public function __call(string $methodName, array $arguments)
    {
        $knownProperties = [
            'allowGoogleSignals' => 'allow_google_signals',
            'allowAdPersonalizationSignals' => 'allow_ad_personalization_signals',
            'campaignContent' => 'campaign_content',
            'campaignId' => 'campaign_id',
            'campaignMedium' => 'campaign_medium',
            'campaignName' => 'campaign_name',
            'campaignSource' => 'campaign_source',
            'campaignTerm' => 'campaign_term',
            'campaign' => 'campaign',
            'clientId' => 'client_id',
            'contentGroup' => 'content_group',
            'cookieDomain' => 'cookie_domain',
            'cookieExpires' => 'cookie_expires',
            'cookieFlags' => 'cookie_flags',
            'cookiePath' => 'cookie_path',
            'cookiePrefix' => 'cookie_prefix',
            'cookieUpdate' => 'cookie_update',
            'language' => 'language',
            'pageLocation' => 'page_location',
            'pageReferrer' => 'page_referrer',
            'pageTitle' => 'page_title',
            'sendPageView' => 'send_page_view',
            'screenResolution' => 'screen_resolution',
            'userId' => 'user_id'
        ];

        if (str_starts_with($methodName, 'set')) {
            $methodName = substr($methodName, 3);

            $service = $this->service();
            if ($service && !empty($knownProperties[$methodName])) {
                $service->setAdditionalQueryParam($knownProperties[$methodName], $arguments[0]);

                return $this;
            }

        }

        return null;
    }

    protected function request(): BaseRequest
    {
        if ($this->_request === null) {
            $this->_request = new BaseRequest(InstantAnalytics::$plugin->getIa()->getClientId());
        }

        return $this->_request;
    }

    protected function service(): ?Service
    {
        if ($this->_service === false) {
            return null;
        }

        if ($this->_service === null) {
            $settings = InstantAnalytics::$settings;
            $apiSecret = App::parseEnv($settings->googleAnalyticsMeasurementApiSecret);
            $measurementId = App::parseEnv($settings->googleAnalyticsMeasurementId);

            if (empty($apiSecret) || empty($measurementId)) {
                InstantAnalytics::$plugin->logAnalyticsEvent(
                    'API secret or measurement ID not set up for Instant Analytics',
                    [],
                    __METHOD__
                );
                $this->_service = false;

                return null;
            }
            $this->_service = new Service($apiSecret, $measurementId);

            $ga4Client = new HttpClient();
            $ga4Client->setClient(Craft::createGuzzleClient());
            $this->_service->setHttpClient($ga4Client);

            $request = Craft::$app->getRequest();
            try {
                $session = Craft::$app->getSession();
            } catch (MissingComponentException $exception) {
                $session = null;
            }

            $this->setPageReferrer($request->getReferrer());

            // Load any campaign values from session or request
            $campaignParams = [
                'utm_source' => 'CampaignSource',
                'utm_medium' => 'CampaignMedium',
                'utm_campaign' => 'CampaignName',
                'utm_content' => 'CampaignContent',
                'utm_term' => 'CampaignTerm',
            ];

            // Load them up for GA4
            foreach ($campaignParams as $key => $method) {
                $value = $request->getParam($key) ?? $session->get($key) ?? null;
                $method = 'set' . $method;

                $this->$method($value);

                if ($session && $value) {
                    $session->set($key, $value);
                }

            }

            // If SEOmatic is installed, set the affiliation as well
            if (InstantAnalytics::$seomaticPlugin && Seomatic::$settings->renderEnabled && Seomatic::$plugin->metaContainers->metaSiteVars !== null) {
                $siteName = Seomatic::$plugin->metaContainers->metaSiteVars->siteName;
                $this->setAffiliation($siteName);
            }

        }

        return $this->_service;
    }
}