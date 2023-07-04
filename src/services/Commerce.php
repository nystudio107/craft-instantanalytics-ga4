<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4\services;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\ItemBaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\PurchaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Parameter\ItemParameter;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\db\TagQuery;
use nystudio107\instantanalyticsGa4\InstantAnalytics;

/**
 * Commerce Service
 *
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class Commerce extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Enqueue analytics information for the completed order
     *
     * @param ?Order $order the Product or Variant
     */
    public function triggerOrderCompleteEvent(Order $order = null)
    {
        if ($order) {
            $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->PurchaseEvent();
            $this->addCommerceOrderToEvent($event, $order);

            InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

            InstantAnalytics::$plugin->logAnalyticsEvent(
                'Adding `Commerce - Order Complete event`: `{reference}` => `{price}`',
                ['reference' => $order->reference, 'price' => $order->totalPrice],
                __METHOD__
            );
        }
    }

    /**
     * Enqueue analytics information for a new checkout flow
     *
     * @param ?Order $order
     */
    public function triggerBeginCheckoutEvent(Order $order = null)
    {
        if ($order) {
            $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->BeginCheckoutEvent();
            // First, include the transaction data
            $event->setCurrency($order->getPaymentCurrency())
                ->setValue($order->getTotalPrice());

            // Add each line item in the cart
            $index = 1;
            foreach ($order->lineItems as $lineItem) {
                $this->addProductDataFromLineItem($event, $lineItem, $index);
                $index++;
            }

            InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

            InstantAnalytics::$plugin->logAnalyticsEvent(
                'Adding `Commerce - Begin Checkout event``',
                [],
                __METHOD__
            );
        }
    }

    /**
     * Send analytics information for the item added to the cart
     *
     * @param LineItem $lineItem the line item that was added
     */
    public function triggerAddToCartEvent(LineItem $lineItem): void
    {
        $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->AddToCartEvent();
        $this->addProductDataFromLineItem($event, $lineItem);
        InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Adding `Commerce - Add to Cart event`: `{title}` => `{quantity}`',
            ['title' => $lineItem->purchasable->title ?? $lineItem->getDescription(), 'quantity' => $lineItem->qty],
            __METHOD__
        );
    }

    /**
     * Send analytics information for the item removed from the cart
     *
     * @param LineItem $lineItem
     */
    public function triggerRemoveFromCartEvent(LineItem $lineItem)
    {
        $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->RemoveFromCartEvent();
        $this->addProductDataFromLineItem($event, $lineItem);
        InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

        InstantAnalytics::$plugin->logAnalyticsEvent(
            'Adding `Commerce - Remove from Cart event`: `{title}` => `{quantity}`',
            ['title' => $lineItem->purchasable->title ?? $lineItem->getDescription(), 'quantity' => $lineItem->qty],
            __METHOD__
        );
    }


    /**
     * Add a Craft Commerce OrderModel to a Purchase Event
     *
     * @param PurchaseEvent $event The PurchaseEvent
     * @param Order $order
     */
    protected function addCommerceOrderToEvent(PurchaseEvent $event, Order $order)
    {
        // First, include the transaction data
        $event->setCurrency($order->getPaymentCurrency())
            ->setTransactionId($order->reference)
            ->setValue($order->getTotalPrice())
            ->setTax($order->getTotalTax())
            ->setShipping($order->getTotalShippingCost());

        // Coupon code
        if ($order->couponCode) {
            $event->setCoupon($order->couponCode);
        }

        // Add each line item in the transaction
        // Two cases - variant and non variant products
        $index = 1;

        foreach ($order->lineItems as $lineItem) {
            $this->addProductDataFromLineItem($event, $lineItem, $index);
            $index++;
        }
    }

    /**
     * Add a Craft Commerce LineItem to an Analytics object
     *
     * @param ItemBaseEvent $event
     * @param LineItem $lineItem
     * @param int $index
     * @param string $listName
     *
     * @return string the title of the product
     * @throws \yii\base\InvalidConfigException
     */
    protected function addProductDataFromLineItem(ItemBaseEvent $event, LineItem $lineItem, int $index = 0, string $listName = ''): string
    {
        $eventItem = $this->getNewItemParameter();

        $product = null;
        $purchasable = $lineItem->purchasable;

        $eventItem->setItemName($purchasable->title ?? $lineItem->getDescription());
        $eventItem->setItemId($purchasable->getSku() ?? $lineItem->getSku());
        $eventItem->setPrice($lineItem->salePrice);
        $eventItem->setQuantity($lineItem->qty);

        // Handle this purchasable being a Variant
        if (is_a($purchasable, Variant::class)) {
            /** @var Variant $purchasable */
            $product = $purchasable->getProduct();
            $variant = $purchasable;
            // Product with variants
            $eventItem->setItemName($product->title);
            $eventItem->setItemVariant($variant->title);
            $eventItem->setItemCategory($product->getType());
        }

        // Handle this purchasable being a Product
        if (is_a($purchasable, Product::class)) {
            /** @var Product $purchasable */
            $product = $purchasable;
            $eventItem->setItemName($product->title);
            $eventItem->setItemVariant($product->title);
            $eventItem->setItemCategory($product->getType());
        }

        // Handle product lists
        if ($index) {
            $eventItem->setIndex($index);
        }

        if ($listName) {
            $eventItem->setItemListName($listName);
        }

        // Add in any custom categories/brands that might be set
        if (InstantAnalytics::$settings && $product) {
            if (isset(InstantAnalytics::$settings['productCategoryField'])
                && !empty(InstantAnalytics::$settings['productCategoryField'])) {
                $category = $this->pullDataFromField(
                    $product,
                    InstantAnalytics::$settings['productCategoryField']
                );
                $eventItem->setItemCategory($category);
            }
            if (isset(InstantAnalytics::$settings['productBrandField'])
                && !empty(InstantAnalytics::$settings['productBrandField'])) {
                $brand = $this->pullDataFromField(
                    $product,
                    InstantAnalytics::$settings['productBrandField']
                );

                $eventItem->setItemBrand($brand);
            }
        }

        //Add each product to the hit to be sent
        $event->addItem($eventItem);

        return $eventItem->getItemName();
    }

    /**
     * Add a product impression from a Craft Commerce Product or Variant
     *
     * @param Product|Variant $productVariant the Product or Variant
     * @throws \yii\base\InvalidConfigException
     */
    public function addCommerceProductImpression(Variant|Product $productVariant): void
    {
        if ($productVariant) {
            $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->ViewItemEvent();
            $this->addProductDataFromProductOrVariant($event, $productVariant);

            InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

            $sku = $productVariant instanceof Product ? $productVariant->getDefaultVariant()->sku : $productVariant->sku;
            $name = $productVariant instanceof Product ? $productVariant->getName() : $productVariant->getProduct()->getName();
            InstantAnalytics::$plugin->logAnalyticsEvent(
                'Adding view item event for `{sku}` - `{name}` - `{name}` - `{index}`',
                ['sku' => $sku, 'name' => $name],
                __METHOD__
            );
        }
    }

    /**
     * Add a product list impression from a Craft Commerce Product or Variant list
     *
     * @param Product[]|Variant[] $products
     * @param string $listName
     */
    public function addCommerceProductListImpression(array $products, string $listName = 'default'): void
    {
        if (!empty($products)) {
            $event = InstantAnalytics::$plugin->ga4->getAnalytics()->create()->ViewItemListEvent();
            foreach ($products as $index => $productVariant) {
                $this->addProductDataFromProductOrVariant($event, $productVariant, $index, $listName);
            }

            InstantAnalytics::$plugin->ga4->getAnalytics()->addEvent($event);

            InstantAnalytics::$plugin->logAnalyticsEvent(
                'Adding view item list event. Listing {number} of items from the `{listName}` list.',
                ['number' => count($products), 'listName' => $listName],
                __METHOD__
            );
        }
    }

    /**
     * Extract product data from a Craft Commerce Product or Variant
     *
     * @param Product|Variant|null $productVariant the Product or Variant
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function addProductDataFromProductOrVariant(ItemBaseEvent $event, $productVariant = null, $index = null, $listName = ''): void
    {
        if ($productVariant === null) {
            return;
        }

        $eventItem = $this->getNewItemParameter();

        $isVariant = $productVariant instanceof Variant;
        $variant = $isVariant ? $productVariant : $productVariant->getDefaultVariant();

        if (!$variant) {
            return;
        }

        $eventItem->setItemId($variant->sku);
        $eventItem->setItemName($variant->title);
        $eventItem->setPrice(number_format($variant->price, 2, '.', ''));

        $category = ($isVariant ? $variant->getProduct() : $productVariant)->getType()['name'];

        if (InstantAnalytics::$settings) {
            if (isset(InstantAnalytics::$settings['productCategoryField'])
                && !empty(InstantAnalytics::$settings['productCategoryField'])) {
                $category = $this->pullDataFromField(
                    $productVariant,
                    InstantAnalytics::$settings['productCategoryField']
                );
                if (empty($productData['category']) && $isVariant) {
                    $category = $this->pullDataFromField(
                        $productVariant->product,
                        InstantAnalytics::$settings['productCategoryField']
                    );
                }
            }
            $eventItem->setItemCategory($category);

            if (isset(InstantAnalytics::$settings['productBrandField'])
                && !empty(InstantAnalytics::$settings['productBrandField'])) {
                $brand = $this->pullDataFromField(
                    $productVariant,
                    InstantAnalytics::$settings['productBrandField'],
                    true
                );

                if (empty($productData['brand']) && $isVariant) {
                    $brand = $this->pullDataFromField(
                        $productVariant,
                        InstantAnalytics::$settings['productBrandField'],
                        true
                    );
                }
                $eventItem->setItemBrand($brand);
            }
        }

        if ($index !== null) {
            $eventItem->setIndex($index);
        }

        if (!empty($listName)) {
            $eventItem->setItemListName($listName);
        }

        // Add item info to the event
        $event->addItem($eventItem);
    }

    /**
     * @param Product|Variant|null $productVariant
     * @param string $fieldHandle
     * @param bool $isBrand
     *
     * @return string
     */
    protected function pullDataFromField($productVariant, $fieldHandle, $isBrand = false): string
    {
        $result = '';
        if ($productVariant && $fieldHandle) {
            $srcField = $productVariant[$fieldHandle] ?? $productVariant->product[$fieldHandle] ?? null;
            // Handle eager loaded elements
            if (is_array($srcField)) {
                return $this->getDataFromElements($isBrand, $srcField);
            }
            // If the source field isn't an object, return nothing
            if (!is_object($srcField)) {
                return $result;
            }
            switch (\get_class($srcField)) {
                case MatrixBlockQuery::class:
                case TagQuery::class:
                    break;
                case CategoryQuery::class:
                case EntryQuery::class:
                    $result = $this->getDataFromElements($isBrand, $srcField->all());
                    break;


                default:
                    $result = strip_tags($srcField);
                    break;
            }
        }

        return $result;
    }

    /**
     * @param bool $isBrand
     * @param array $elements
     * @return string
     */
    protected function getDataFromElements(bool $isBrand, array $elements): string
    {
        $cats = [];

        if ($isBrand) {
            // Because we can only have one brand, we'll get
            // the very last category. This means if our
            // brand is a sub-category, we'll get the child
            // not the parent.
            foreach ($elements as $cat) {
                $cats = [$cat->title];
            }
        } else {
            // For every category, show its ancestors
            // delimited by a slash.
            foreach ($elements as $cat) {
                $name = $cat->title;

                while ($cat = $cat->parent) {
                    $name = $cat->title . '/' . $name;
                }

                $cats[] = $name;
            }
        }

        // Join separate categories with a pipe.
        return implode('|', $cats);
    }

    /**
     * Create an item parameter and set affiliation on it, if any exists.
     *
     * @return ItemParameter
     */
    protected function getNewItemParameter(): ItemParameter
    {
        $parameter = new ItemParameter();
        $parameter->setAffiliation(InstantAnalytics::$plugin->ga4->getAnalytics()->getAffiliation());
        return $parameter;
    }
}
