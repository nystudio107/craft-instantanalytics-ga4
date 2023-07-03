# Using Instant Analytics

## Simple Page Tracking

Once you’ve entered your **Google Analytics Tracking ID**, Instant Analytics will automatically send PageViews to Google Analytics if you have **Auto Send PageViews** on (which it defaults to). There is no step 2.
  
 To control which pages Instant Analytics sends PageViews on, set **Auto Send PageViews** to `off`.  Then you just need to add a call to `{% hook 'iaSendPageView' %}` to your frontend templates to send PageView tracking to Google Analytics.  We recommend that you do this in a block at the bottom of your `layout.twig` template that other templates extend, right before the `</body>` tag, like this:

```twig
    {% block analytics %}
        {% hook 'iaSendPageView' %}
    {% endblock %}
```

That’s it!  Once you have added this hook, Instant Analytics will start sending PageViews to Google Analytics. It does not send any Google Analytics data if:

* You have not entered a valid **Google Analytics Tracking ID:**
* You are viewing templates in Live Preview
* The request is a CP or Console request
* If you have `sendAnalyticsData` set to false in the `config.php` file

By default, the "title" used for your pages is the current template path; if you have [SEOmatic](https://github.com/nystudio107/seomatic) installed, Instant Analytics will automatically grab the current page title from it.

Instant Analytics will also automatically parse and set any [UTM query string parameters](https://blog.kissmetrics.com/how-to-use-utm-parameters/) such as `utm_campaign`, `utm_source`, `utm_medium`, and `utm_content` in the analytics object.

### Advanced Page Tracking

This is where the fun begins.  Instant Analytics injects an `instantAnalytics` object into your templates, the same way that Craft injects an `entry` object or Craft Commerce injects a `product` object.  This is the actual `Analytics` object that the `{% hook 'iaSendPageView' %}` will send to Google Analytics.

You can manipulate this object as you see fit, adding data to be sent to Google Analytics along with your PageView.

For example, let’s say that you want to add an `Affiliation`:

```twig
    {% do instantAnalytics.setAffiliation("Brads for Men") %}
```

Or perhaps for a particular page, you want to change the the `API secret` and `Measurement Id` settings used by Google Analytics:

```twig
    {% do instantAnalytics.setMeasurementId("G-7DDE6SKB8E") %}
    {% do instantAnalytics.setApiSecret('xyz') %}
```

Or do all of that at the same time:

```twig
    {% do instantAnalytics.setAffiliation("Brads for Men").setMeasurementId("G-7DDE6SKB8E").setApiSecret('xyz') %}
```

#### Sending events

With GA4, you can create multitude of events that you want to track. For a list see [PHP GA4 Measurement Protocol](https://github.com/br33f/php-GA4-Measurement-Protocol) documentation. This plugin also adds a `PageView` event you can use.

The principal flow is that you create the event, populate it with the desired properties and add it to the list of events to be sent. All of the collected events over the course of a request are sent after the response has been sent back to the user.

Please note, that the maximum amount of events you can send in a single request is 25.

Here’s some sample code to give you an idea of how you’d create an event and send it.

```twig
    {% set searchEvent = instantAnalytics.create.SearchEvent %}
    {% do searchEvent.setSearchTerm(searchTerm) %}
    {% do instantAnalytics.addEvent(searchEvent) %}
```

You can also set arbitrary parameters, if you want. For example
```twig
    {% set searchEvent = instantAnalytics.create.SearchEvent %}
    {% do searchEvent.setSearchTerm(searchTerm).setResultsFound(searchResultCount) %}
    {% do instantAnalytics.addEvent(searchEvent) %}
```

#### Event Reference

Below is a list of the events you can create via `instantAnalytics.create.`:

| GA Event name | Instant Analytics GA4 class | Documentation |
| ---------- | --------- | --------------|
| add_payment_info | AddPaymentInfoEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#add_payment_info)
| add_shipping_info | AddShippingInfoEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#add_shipping_info)
| add_to_cart | AddToCartEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#add_to_cart)
| begin_checkout | BeginCheckoutEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#begin_checkout)
| login | LoginEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#login)
| purchase | PurchaseEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#purchase)
| refund | RefundEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#refund)
| remove_from_cart | RemoveFromCartEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#remove_from_cart)
| search | SearchEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#search)
| select_item | SelectItemEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#select_item)
| sign_up | SignUpEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#sign_up)
| view_cart | ViewCartEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#view_cart)
| view_item | ViewItemEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#view_item)
| view_search_results | ViewSearchResultsEvent | [see documentation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#view_search_results)

So for example, if you wanted to send a `view_item` event to GA4, you’d create it like this:

Twig:

```twig
    {# @var viewItemEvent \Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewItemEvent #}
    {% set viewItemEvent = instantAnalytics.create.ViewItemEvent %}
```

The `{# @var viewItemEvent` comment is a typehint, which will give you auto-completion of the methods available for the event type in your IDE.

This just creates the event object. You then will want to add or modify various settings available to the event:

```twig
    {% do viewItemEvent.setValue(51.10).setCurrency('EUR') %}
```

...and then add the event to Instant Analytics so it will be sent via `isPageViewSend`:

```twig
    {% do instantAnalytics.viewItemEvent(searchEvent) %}
```

Here’s the analogous code in PHP:

```php
    $viewItemEvent = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->ViewItemEvent();
    $viewItemEvent
        ->setValue(51.10)
        ->setCurrency('EUR');
    InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($viewItemEvent);
```

Your IDE should give you autocomplete for the various parameters each event takes, but you can also refer to the [PHP GA4 Measurement Protocol](https://github.com/br33f/php-GA4-Measurement-Protocol) documentation for a static reference.

### Plugin interaction

There are several interactions with other plugins possible. For example, if you have the SEOmatic plugin installed, every time you send a `PageView` event, by default the page title will be set to the `seoTitle` determined by the SEOmatic plugin.

#### Craft Commerce Tracking with Google Enhanced Ecommerce

If you are using Craft Commerce, Instant Analytics will recognize this, and automatically send Google Enhanced Ecommerce data for the following actions:

* **Add to Cart** - When someone adds an item from your Craft Commerce store to their cart.  This will include data for the Product or Variant that was added to the cart.
* **Remove from Cart** - When someone removes an item from your Craft Commerce store cart (requires Craft Commerce 1.2.x or later).  This will include data for the Product or Variant that was removed from the cart.
* **Purchase** - When someone completes a purchase in your Craft Commerce store.  This will include all of the LineItems that were added to the cart, as well as the Order Reference, Revenue, Tax, Shipping, and Coupon Code used (if any).

It’ll just work.  In addition to the basic automatic tracking that Instant Analytics does, you can use the `instantAnalytics` object to send additional data to Google Analytics Enhanced Ecommerce:

* `{% do instantAnalytics.addCommerceProductListImpression(PAGE_PRODUCTS, LIST_NAME) %}` - This will send a `ViewItemList` event for a given Craft Commerce product list. `PAGE_PRODUCTS` should be an array of `Product` or `Variant` elements, while `LIST_NAME` should be a name for the product list being displayed. It’s optional and defaults to `default` if not specified.
* `{% do instantAnalytics.addCommerceProductImpression(PRODUCT_VARIANT) %}` - This will send a `ViewItem` event for a given Craft Commerce `Product` or `Variant` (you can pass in either in `PRODUCT_VARIANT`).

You can also take advantage of the built-in events, such as `AddShippingInfo` like this

```twig
    {% set shippingInfoEvent = instantAnalytics.create.AddShippingInfo %}
    {% do shippingInfoEvent.setAddress(shippingInfoAddress).setShippingMethod(shippingMethod) %}
    {% do instantAnalytics.addEvent(shippingInfoEvent) %}
```

**Please note**
Sending GA4 events via the API is not meant to handle all the session information. If you want to take advantage of session tracking and User purchase journey on GA4 console, there are a few steps you must ensure are taken care of:
1. You need to have a `_ga` Cookie in place, as there is no way to start a session using the API. If you're not using `gtag` or Google Tag Manager already, you can use the `iaInsertGtag` template hook to insert the relevant JavaScript that will start the session for you.
2. User purchase journey report is a closed funnel report, which means that any previous step must take place, before user can proceed in the funnel. In practical terms this means the following events _must_ be fired in the following order for the user purchase journey to be completed.

## Sending Events

The collected events are sent automatically once the response has been sent back to the user, however you can trigger the process manually
```twig
    {% do instantAnalytics.sendCollectedEvents() %}
```

This will empty the collected event list so that they are not sent twice by accident.

## Tracking Assets/Resources

Instant Analytics lets you track assets/resources that you can’t normally track, by providing a tracking URL that you use in your frontend templates.

You can track as PageViews like this:

```twig
    {{ pageViewTrackingUrl(URL, TITLE) }}
```

In the above example, `URL` and `TITLE` are mandatory.

Or you can track as Events using the following code:

```twig
    {{ eventTrackingUrl(URL, EVENT_NAME, PARAMS) }}
```

In the above example, `URL` and `EVENT_NAME` are mandatory. You can skip providing `PARAMS`, but, in case you do, it should be a hash with parameter names pointing to parameter values, that will be added to the event.

These can be wrapped around any URL, so you could wrap your tracking URL around an image, a PDF, or an externally linked file... Whatever.

What happens when the link is clicked on is Instant Analytics sends the tracking PageView or Event to Google Analytics, and then the original URL is seamlessly accessed.

The URL that Instant Analytics generates will look like this:

```twig
    http://yoursite.com/instantAnalytics/pageViewTrack/FILENAME.EXT?url=XXX&title=AAA
    -OR-
    http://yoursite.com/instantAnalytics/eventTrack/FILENAME.EXT?url=XXX&eventName=AAA&params[one]=BBB&params[two]=CCC
```

It’s done this way so that the URL can be directly used in RSS feeds for the media object URLs, which require that the filename is in the URL path.

## Custom Tracking via Twig or Plugin

If your needs are more specialized, you can build a base event and set whatever you want on it. To do that, do the following:

Twig:

```twig
    {% set event = simpleAnalyticsEvent('reply_to_comment').setThreadId(999).setUserTenure('2y').setArticleId(articleId) %}
```

PHP via Plugin:

```php
    $event = InstantAnalytics::$plugin->ga4->getSimpleEvent('reply_to_comment')
        ->setThreadId(999)
        ->setUserTenure('2y')
        ->setArticleId($articleId);
```

You are then free to change any of the parameters as you see fit (as outlined in the examples). Just don’t forget to actually enqueue the event for sending

Twig:

```twig
    {% do instantAnalytics.addEvent(event) %}
```

PHP via Plugin:

```php
    InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);
```

The sky’s the limit in either case, you can do anything from simple PageViews to complicated Google Enhanced eCommerce analytics tracking.

Brought to you by [nystudio107](http://nystudio107.com)
