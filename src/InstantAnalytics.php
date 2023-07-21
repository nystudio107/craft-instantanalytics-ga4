<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * Instant Analytics brings full Google Analytics support to your Twig templates
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\instantanalyticsGa4;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\commerce\events\LineItemEvent;
use craft\commerce\Plugin as Commerce;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Exception;
use nystudio107\instantanalyticsGa4\helpers\Analytics;
use nystudio107\instantanalyticsGa4\helpers\Field as FieldHelper;
use nystudio107\instantanalyticsGa4\models\Settings;
use nystudio107\instantanalyticsGa4\services\ServicesTrait;
use nystudio107\instantanalyticsGa4\twigextensions\InstantAnalyticsTwigExtension;
use nystudio107\instantanalyticsGa4\variables\InstantAnalyticsVariable;
use nystudio107\seomatic\Seomatic;
use yii\base\Event;
use yii\web\Response;
use function array_merge;

/** @noinspection MissingPropertyAnnotationsInspection */

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.0.0
 */
class InstantAnalytics extends Plugin
{
    // Traits
    // =========================================================================

    use ServicesTrait;

    // Constants
    // =========================================================================

    /**
     * @var string
     */
    protected const COMMERCE_PLUGIN_HANDLE = 'commerce';

    /**
     * @var string
     */
    protected const SEOMATIC_PLUGIN_HANDLE = 'seomatic';

    // Static Properties
    // =========================================================================

    /**
     * @var null|InstantAnalytics
     */
    public static ?InstantAnalytics $plugin = null;

    /**
     * @var null|Settings
     */
    public static ?Settings $settings = null;

    /**
     * @var null|Commerce
     */
    public static ?Commerce $commercePlugin = null;

    /**
     * @var null|Seomatic
     */
    public static ?Seomatic $seomaticPlugin = null;

    /**
     * @var string
     */
    public static string $currentTemplate = '';

    /**
     * @var bool
     */
    public static bool $pageViewSent = false;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;
        self::$settings = $this->getSettings();

        // Add in our Craft components
        $this->addComponents();
        // Install our global event handlers
        $this->installEventListeners();

        Craft::info(
            Craft::t(
                'instant-analytics-ga4',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $commerceFields = [];

        if (self::$commercePlugin !== null) {
            $productTypes = self::$commercePlugin->getProductTypes()->getAllProductTypes();

            foreach ($productTypes as $productType) {
                $productFields = $this->getPullFieldsFromLayoutId($productType->fieldLayoutId);
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $commerceFields = array_merge($commerceFields, $productFields);
                if ($productType->hasVariants) {
                    $variantFields = $this->getPullFieldsFromLayoutId($productType->variantFieldLayoutId);
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $commerceFields = array_merge($commerceFields, $variantFields);
                }
            }
        }

        // Rend the settings template
        try {
            return Craft::$app->getView()->renderTemplate(
                'instant-analytics-ga4/settings',
                [
                    'settings' => $this->getSettings(),
                    'commerceFields' => $commerceFields,
                ]
            );
        } catch (Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
        }

        return '';
    }

    /**
     * Handle the `{% hook iaSendPageView %}`
     */
    public function iaSendPageView(/** @noinspection PhpUnusedParameterInspection */ array &$context = []): string
    {
        $this->ga4->addPageViewEvent();
        return '';
    }

    /**
     * Handle the `{% hook iaInsertGtag %}`
     */
    public function iaInsertGtag(/** @noinspection PhpUnusedParameterInspection */ array &$context = []): string
    {
        $config = [];

        if (self::$settings->sendUserId) {
            $userId = Analytics::getUserId();
            if (!empty($userId)) {
                $config['user_id'] = $userId;
            }
        }

        $measurementId = App::parseEnv(self::$settings->googleAnalyticsMeasurementId);


        $view = Craft::$app->getView();
        $existingMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $content = $view->renderTemplate('instant-analytics-ga4/_includes/gtag', compact('measurementId', 'config'));
        $view->setTemplateMode($existingMode);

        return $content;
    }

    public function logAnalyticsEvent(string $message, array $variables = [], string $category = ''): void
    {
        Craft::info(
            Craft::t('instant-analytics-ga4', $message, $variables),
            $category
        );
    }
    // Protected Methods
    // =========================================================================

    /**
     * Add in our Craft components
     */
    protected function addComponents(): void
    {
        $view = Craft::$app->getView();
        // Add in our Twig extensions
        $view->registerTwigExtension(new InstantAnalyticsTwigExtension());
        // Install our template hooks
        $view->hook('iaSendPageView', [$this, 'iaSendPageView']);
        $view->hook('iaInsertGtag', [$this, 'iaInsertGtag']);

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('instantAnalytics', [
                    'class' => InstantAnalyticsVariable::class,
                    'viteService' => $this->vite,
                ]);
            }
        );
    }

    /**
     * Install our event listeners
     */
    protected function installEventListeners(): void
    {
        // Handler: Plugins::EVENT_AFTER_INSTALL_PLUGIN
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event): void {
                if ($event->plugin === $this) {
                    $request = Craft::$app->getRequest();
                    if ($request->isCpRequest) {
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('instant-analytics-ga4/welcome'))->send();
                    }
                }
            }
        );

        // Handler: Plugins::EVENT_AFTER_LOAD_PLUGINS
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                // Determine if Craft Commerce is installed & enabled
                self::$commercePlugin = Craft::$app->getPlugins()->getPlugin(self::COMMERCE_PLUGIN_HANDLE);
                // Determine if SEOmatic is installed & enabled
                self::$seomaticPlugin = Craft::$app->getPlugins()->getPlugin(self::SEOMATIC_PLUGIN_HANDLE);

                // Make sure to install these only after we definitely know whether other plugins are installed
                $request = Craft::$app->getRequest();
                // Install only for non-console site requests
                if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
                    $this->installSiteEventListeners();
                }

                // Install only for non-console Control Panel requests
                if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
                    $this->installCpEventListeners();
                }
            }
        );
    }

    /**
     * Install site event listeners for site requests only
     */
    protected function installSiteEventListeners(): void
    {
        // Handler: UrlManager::EVENT_REGISTER_SITE_URL_RULES
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event): void {
                Craft::debug(
                    'UrlManager::EVENT_REGISTER_SITE_URL_RULES',
                    __METHOD__
                );
                // Register our Control Panel routes
                $event->rules = array_merge(
                    $event->rules,
                    $this->customFrontendRoutes()
                );
            }
        );
        // Remember the name of the currently rendering template
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            static function (TemplateEvent $event): void {
                self::$currentTemplate = $event->template;
            }
        );
        // Send the page-view event.
        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event): void {
                if (self::$settings->autoSendPageView) {
                    $request = Craft::$app->getRequest();
                    if (!$request->getIsAjax()) {
                        $this->ga4->addPageViewEvent();
                    }
                }
            }
        );

        // Send the collected events
        Event::on(
            Response::class,
            Response::EVENT_BEFORE_SEND,
            function (Event $event): void {
                // Initialize this sooner rather than later, since it's possible this will want to tinker with cookies
                $this->ga4->getAnalytics();
            }
        );

        // Send the collected events
        Event::on(
            Response::class,
            Response::EVENT_AFTER_SEND,
            function (Event $event): void {
                $this->ga4->getAnalytics()->sendCollectedEvents();
            }
        );

        // Commerce-specific hooks
        if (self::$commercePlugin !== null) {
            Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, function (Event $e): void {
                $order = $e->sender;
                if (self::$settings->autoSendPurchaseComplete) {
                    $this->commerce->triggerOrderCompleteEvent($order);
                }
            });

            Event::on(Order::class, Order::EVENT_AFTER_ADD_LINE_ITEM, function (LineItemEvent $e): void {
                $lineItem = $e->lineItem;
                if (self::$settings->autoSendAddToCart) {
                    $this->commerce->triggerAddToCartEvent($lineItem);
                }
            });

            // Check to make sure Order::EVENT_AFTER_REMOVE_LINE_ITEM is defined
            if (defined(Order::class . '::EVENT_AFTER_REMOVE_LINE_ITEM')) {
                Event::on(Order::class, Order::EVENT_AFTER_REMOVE_LINE_ITEM, function (LineItemEvent $e): void {
                    $lineItem = $e->lineItem;
                    if (self::$settings->autoSendRemoveFromCart) {
                        $this->commerce->triggerRemoveFromCartEvent($lineItem);
                    }
                });
            }
        }
    }

    /**
     * Install site event listeners for Control Panel requests only
     */
    protected function installCpEventListeners(): void
    {
    }

    /**
     * Return the custom frontend routes
     *
     * @return array<string, string>
     */
    protected function customFrontendRoutes(): array
    {
        return [
            'instantanalytics/pageViewTrack' =>
                'instant-analytics-ga4/track/track-page-view-url',
            'instantanalytics/eventTrack/<filename:[-\w\.*]+>?' =>
                'instant-analytics-ga4/track/track-event-url',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $layoutId
     *
     * @return mixed[]|array<string, string>
     */
    private function getPullFieldsFromLayoutId($layoutId): array
    {
        $result = ['' => 'none'];
        if ($layoutId === null) {
            return $result;
        }

        $fieldLayout = Craft::$app->getFields()->getLayoutById($layoutId);
        if ($fieldLayout) {
            $result = FieldHelper::fieldsOfTypeFromLayout(FieldHelper::TEXT_FIELD_CLASS_KEY, $fieldLayout, false);
        }

        return $result;
    }
}
