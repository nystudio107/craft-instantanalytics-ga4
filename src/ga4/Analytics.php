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
use craft\helpers\App;
use nystudio107\instantanalytics\InstantAnalytics;
use yii\helpers\StringHelper;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.2.0
 *
 * @method setAllowGoogleSignals()
 * @method setAllowAdPersonalizationSignals()
 * @method setCampaignContent()
 * @method setCampaignId()
 * @method setCampaignMedium()
 * @method setCampaignName()
 * @method setCampaignSource()
 * @method setCampaignTerm()
 * @method setCampaign()
 * @method setClientId()
 * @method setContentGroup()
 * @method setCookieDomain()
 * @method setCookieExpires()
 * @method setCookieFlags()
 * @method setCookiePath()
 * @method setCookiePrefix()
 * @method setCookieUpdate()
 * @method setLanguage()
 * @method setPageLocation()
 * @method setPageReferrer()
 * @method setPageTitle()
 * @method setSendPageView()
 * @method setScreenResolution()
 * @method setUserId()
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
        $request = $this->request();
        $eventCount = count($request->getEvents()->getEventList());
        if ($eventCount === 0) {
            Craft::info(
                Craft::t(
                    'instant-analytics',
                    'No events collected to send',
                ),
                __METHOD__
            );

            return null;
        }

        Craft::info(
            Craft::t(
                'instant-analytics',
                'Sending {count} analytics events',
                [
                    'count' => $eventCount,
                ]
            ),
            __METHOD__
        );

        $response = $this->service()->send($request);

        // Clear events already sent from the list.
        $request->getEvents()->setEventList([]);
        return $response;
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

            if (!empty($knownProperties[$methodName])) {
                $this->service()->setAdditionalQueryParam($knownProperties[$methodName], $arguments[0]);
            }

        }
    }
    protected function request(): BaseRequest
    {
        if ($this->_request === null) {
            $this->_request = new BaseRequest(InstantAnalytics::$plugin->getIa()->getClientId());
        }

        return $this->_request;
    }

    protected function service(): Service
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
}
