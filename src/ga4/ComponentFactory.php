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

namespace nystudio107\instantanalyticsGa4\ga4;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\AddPaymentInfoEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\AddShippingInfoEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\AddToCartEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\BeginCheckoutEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\LoginEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\PurchaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\RefundEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\RemoveFromCartEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\SearchEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\SelectItemEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\SignUpEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewCartEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewItemEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewItemListEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewSearchResultsEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Parameter\ItemParameter;
use Craft;
use nystudio107\instantanalyticsGa4\ga4\events\PageViewEvent;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.2.0
 *
 * @method ItemParameter ItemParameter()
 * @method AddPaymentInfoEvent AddPaymentInfoEvent()
 * @method AddToCartEvent AddToCartEvent()
 * @method AddShippingInfoEvent AddShippingInfoEvent()
 * @method BaseEvent BaseEvent()
 * @method BeginCheckoutEvent BeginCheckoutEvent()
 * @method LoginEvent LoginEvent()
 * @method PageViewEvent PageViewEvent()
 * @method PurchaseEvent PurchaseEvent()
 * @method RefundEvent RefundEvent()
 * @method RemoveFromCartEvent RemoveFromCartEvent()
 * @method SearchEvent SearchEvent()
 * @method SelectItemEvent SelectItemEvent()
 * @method SignUpEvent SignUpEvent()
 * @method ViewCartEvent ViewCartEvent()
 * @method ViewItemEvent ViewItemEvent()
 * @method ViewItemListEvent ViewItemListEvent()
 * @method ViewSearchResultsEvent ViewSearchResultsEvent()
 */
class ComponentFactory
{
    public function __call(string $componentName, $args)
    {
        $componentMap = [
            'ItemParameter' => ItemParameter::class,
            'AddPaymentInfoEvent' => AddPaymentInfoEvent::class,
            'AddShippingInfoEvent' => AddShippingInfoEvent::class,
            'AddToCartEvent' => AddToCartEvent::class,
            'BaseEvent' => BaseEvent::class,
            'BeginCheckoutEvent' => BeginCheckoutEvent::class,
            'LoginEvent' => LoginEvent::class,
            'PageViewEvent' => PageViewEvent::class,
            'PurchaseEvent' => PurchaseEvent::class,
            'RefundEvent' => RefundEvent::class,
            'RemoveFromCartEvent' => RemoveFromCartEvent::class,
            'SearchEvent' => SearchEvent::class,
            'SelectItemEvent' => SelectItemEvent::class,
            'SignUpEvent' => SignUpEvent::class,
            'ViewCartEvent' => ViewCartEvent::class,
            'ViewItemEvent' => ViewItemEvent::class,
            'ViewItemListEvent' => ViewItemListEvent::class,
            'ViewSearchResultsEvent' => ViewSearchResultsEvent::class,
        ];

        if (!array_key_exists($componentName, $componentMap)) {
            throw new \InvalidArgumentException(Craft::t('instant-analytics-ga4', 'Unknown event type - ' . $componentName));
        }
        
        return new $componentMap[$componentName];

    }
}
