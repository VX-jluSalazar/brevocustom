<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Brevocustom extends Module
{
    public const CONFIG_ENABLED = 'BREVOCUSTOM_ENABLED';
    public const CONFIG_BREVO_API_KEY = 'BREVOCUSTOM_BREVO_API_KEY';
    public const CONFIG_DEBUG = 'BREVOCUSTOM_DEBUG';
    public const CONFIG_ABANDONED_CART_DELAY_MINUTES = 'BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES';
    public const CONFIG_SHOP_URL = 'BREVOCUSTOM_SHOP_URL';
    public const CONFIG_SHOP_REVIEW_URL = 'BREVOCUSTOM_SHOP_REVIEW_URL';
    public const CONFIG_CONTACT_URL = 'BREVOCUSTOM_CONTACT_URL';
    public const CONFIG_REORDER_URL_PATTERN = 'BREVOCUSTOM_REORDER_URL_PATTERN';
    public const CONFIG_CUSTOM_URLS = 'BREVOCUSTOM_CUSTOM_URLS';
    public const CONFIG_CRON_TOKEN = 'BREVOCUSTOM_CRON_TOKEN';

    private const ORDER_STATE_SENT = 4;
    private const ORDER_STATE_DELIVERED = 5;
    private const NEWSLETTER_SUBSCRIPTION = 0;

    private const SUBMIT_ACTION = 'submitBrevocustomConfig';

    public function __construct()
    {
        $this->loadClasses();

        $this->name = 'brevocustom';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'jluSalazar';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.2.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Brevo Custom Events', [], 'Modules.Brevocustom.Admin');
        $this->description = $this->trans('Send custom ecommerce events to Brevo.', [], 'Modules.Brevocustom.Admin');
    }

    private function loadClasses(): void
    {
        $basePath = __DIR__ . '/classes/';

        $files = [
            'Service/BrevoEventLogger.php',
            'Service/BrevoEventClient.php',
            'Service/BrevoPayloadBuilder.php',
            'Provider/BrevoRelatedProductsProvider.php',
            'Provider/BrevoShopReviewsProvider.php',
            'Provider/BrevoServientregaProvider.php',
        ];

        foreach ($files as $file) {
            $path = $basePath . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function install(): bool
    {
        return parent::install()
            && $this->installDatabase()
            && $this->installDefaultConfiguration()
            && $this->registerHook([
                'displayBackOfficeHeader',
                'actionValidateOrder',
                'actionNewsletterRegistrationAfter',
                'actionOrderStatusPostUpdate',
            ]);
    }

    public function uninstall(): bool
    {
        return $this->uninstallDatabase()
            && $this->deleteConfiguration()
            && parent::uninstall();
    }

    public function hookDisplayBackOfficeHeader(): void
    {
        if (Tools::getValue('configure') !== $this->name) {
            return;
        }

        $cssVersion = file_exists(__DIR__ . '/views/css/admin.css') ? (string) filemtime(__DIR__ . '/views/css/admin.css') : $this->version;
        $jsVersion = file_exists(__DIR__ . '/views/js/admin.js') ? (string) filemtime(__DIR__ . '/views/js/admin.js') : $this->version;

        $this->context->controller->addCSS($this->_path . 'views/css/admin.css?v=' . rawurlencode($cssVersion));
        $this->context->controller->addJS($this->_path . 'views/js/admin.js?v=' . rawurlencode($jsVersion));
    }

    public function getContent(): string
    {
        $output = '';
        $this->installDatabase();
        $this->installDefaultConfiguration(false);
        $this->registerHook('actionValidateOrder');
        $this->registerHook('actionNewsletterRegistrationAfter');
        $this->registerHook('actionOrderStatusPostUpdate');

        if (Tools::isSubmit(self::SUBMIT_ACTION)) {
            $output .= $this->postProcessConfiguration();
        }

        $this->context->smarty->assign([
            'brevocustom_form_action' => AdminController::$currentIndex
                . '&configure=' . $this->name
                . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'brevocustom_values' => $this->getConfigurationValues(),
            'brevocustom_custom_urls' => $this->getCustomUrlItems(),
            'brevocustom_submit_name' => self::SUBMIT_ACTION,
            'brevocustom_status' => $this->getStatusSummary(),
            'brevocustom_recent_logs' => $this->getRecentLogs(),
            'brevocustom_cron_url' => $this->context->link->getModuleLink(
                $this->name,
                'cron',
                ['token' => (string) Configuration::get(self::CONFIG_CRON_TOKEN)],
                true
            ),
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookActionValidateOrder(array $params): void
    {
        if (!(bool) Configuration::get(self::CONFIG_ENABLED)) {
            return;
        }

        try {
            if (empty($params['order']) || !$params['order'] instanceof Order) {
                return;
            }

            /** @var Order $order */
            $order = $params['order'];
            if (!Validate::isLoadedObject($order)) {
                return;
            }

            $logger = new BrevoEventLogger();
            $objectId = 'ps_order_' . (int) $order->id;
            if ($logger->hasSentOrPendingLog('vx_order_created', 'order', $objectId)) {
                $logger->logDuplicateSkip('vx_order_created', 'order', $objectId);
                return;
            }

            $payload = $this->buildOrderCreatedPayload($order, $params);
            if (empty($payload)) {
                return;
            }

            $client = new BrevoEventClient(
                (string) Configuration::get(self::CONFIG_BREVO_API_KEY),
                $logger,
                (bool) Configuration::get(self::CONFIG_DEBUG)
            );
            $client->send($payload);
        } catch (Throwable $exception) {
            (new BrevoEventLogger())->log(
                'vx_order_created',
                'order',
                isset($params['order']) && $params['order'] instanceof Order ? 'ps_order_' . (int) $params['order']->id : null,
                null,
                [],
                'failed',
                null,
                '',
                $exception->getMessage()
            );
        }
    }

    public function hookActionNewsletterRegistrationAfter(array $params): void
    {
        if (!(bool) Configuration::get(self::CONFIG_ENABLED)) {
            return;
        }

        try {
            $email = trim((string) ($params['email'] ?? Tools::getValue('email')));
            $action = (int) ($params['action'] ?? -1);
            $error = $params['error'] ?? null;

            if ($email === '' || !Validate::isEmail($email) || $action !== self::NEWSLETTER_SUBSCRIPTION || !empty($error)) {
                return;
            }

            $payload = $this->buildSubscriberPayload($email, $params);
            $logger = new BrevoEventLogger();
            $objectId = 'newsletter_' . $this->normalizeIdentifier($email);

            if ($logger->hasSentOrPendingLog('vx_subscriber', 'subscription', $objectId)) {
                $logger->logDuplicateSkip('vx_subscriber', 'subscription', $objectId, $email);
                return;
            }

            $client = new BrevoEventClient(
                (string) Configuration::get(self::CONFIG_BREVO_API_KEY),
                $logger,
                (bool) Configuration::get(self::CONFIG_DEBUG)
            );
            $client->send($payload);
        } catch (Throwable $exception) {
            (new BrevoEventLogger())->log(
                'vx_subscriber',
                'subscription',
                null,
                isset($email) ? $email : null,
                [],
                'failed',
                null,
                '',
                $exception->getMessage()
            );
        }
    }

    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        if (!(bool) Configuration::get(self::CONFIG_ENABLED)) {
            return;
        }

        try {
            if (empty($params['id_order'])) {
                return;
            }

            $order = new Order((int) $params['id_order']);
            if (!Validate::isLoadedObject($order)) {
                return;
            }

            $newOrderStatus = $params['newOrderStatus'] ?? null;
            $newStateId = $newOrderStatus instanceof OrderState ? (int) $newOrderStatus->id : (int) $order->current_state;

            if ($newStateId === self::ORDER_STATE_SENT) {
                $this->sendOrderStatusEvent($order, 'vx_order_sent', 'sent_at');
            } elseif ($newStateId === self::ORDER_STATE_DELIVERED) {
                $this->sendOrderStatusEvent($order, 'vx_order_delivered', 'delivered_at');
            }
        } catch (Throwable $exception) {
            (new BrevoEventLogger())->log(
                'vx_order_status_update',
                'order',
                !empty($params['id_order']) ? 'ps_order_' . (int) $params['id_order'] : null,
                null,
                [],
                'failed',
                null,
                '',
                $exception->getMessage()
            );
        }
    }

    private function sendOrderStatusEvent(Order $order, string $eventName, string $eventDateProperty): void
    {
        $logger = new BrevoEventLogger();
        $objectId = 'ps_order_' . (int) $order->id;

        if ($logger->hasSentOrPendingLog($eventName, 'order', $objectId)) {
            $logger->logDuplicateSkip($eventName, 'order', $objectId);
            return;
        }

        $payload = $this->buildOrderStatusPayload($order, $eventName, $eventDateProperty);
        if (empty($payload)) {
            return;
        }

        $client = new BrevoEventClient(
            (string) Configuration::get(self::CONFIG_BREVO_API_KEY),
            $logger,
            (bool) Configuration::get(self::CONFIG_DEBUG)
        );
        $client->send($payload);
    }

    public function runAbandonedCartCron(string $token): array
    {
        if (!hash_equals((string) Configuration::get(self::CONFIG_CRON_TOKEN), $token)) {
            return [
                'success' => false,
                'message' => 'Invalid cron token.',
                'processed' => 0,
                'sent' => 0,
                'skipped' => 0,
            ];
        }

        if (!(bool) Configuration::get(self::CONFIG_ENABLED)) {
            return [
                'success' => true,
                'message' => 'Integration disabled.',
                'processed' => 0,
                'sent' => 0,
                'skipped' => 0,
            ];
        }

        $carts = $this->getAbandonedCartCandidates();
        $logger = new BrevoEventLogger();
        $client = new BrevoEventClient(
            (string) Configuration::get(self::CONFIG_BREVO_API_KEY),
            $logger,
            (bool) Configuration::get(self::CONFIG_DEBUG)
        );
        $processed = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($carts as $cartRow) {
            $processed++;
            $cart = new Cart((int) $cartRow['id_cart']);
            if (!Validate::isLoadedObject($cart)) {
                $skipped++;
                continue;
            }

            $objectId = 'ps_cart_' . (int) $cart->id;
            $email = (string) $cartRow['email'];

            if ($logger->hasSentOrPendingLog('vx_abandoned_cart', 'cart', $objectId)) {
                $logger->logDuplicateSkip('vx_abandoned_cart', 'cart', $objectId, $email);
                $skipped++;
                continue;
            }

            $payload = $this->buildAbandonedCartPayload($cart, $cartRow);
            if (empty($payload)) {
                $skipped++;
                continue;
            }

            $result = $client->send($payload);
            if (!empty($result['success'])) {
                $sent++;
            }
        }

        return [
            'success' => true,
            'message' => 'Abandoned cart cron finished.',
            'processed' => $processed,
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    private function buildOrderCreatedPayload(Order $order, array $hookParams): array
    {
        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer) || empty($customer->email)) {
            return [];
        }

        $currency = new Currency((int) $order->id_currency);
        $orderState = new OrderState((int) $order->current_state, (int) $order->id_lang);
        $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
        $address = new Address((int) $order->id_address_delivery);
        $idShop = (int) $order->id_shop ?: (int) $this->context->shop->id;
        $idLang = (int) $order->id_lang ?: (int) $this->context->language->id;
        $paymentMethodId = $this->getPaymentMethodId((string) $order->module);

        $products = $this->buildOrderProducts($order, $idLang);
        $categoryIds = [];
        $excludedProductIds = [];
        foreach ($products as $product) {
            if (!empty($product['category_id'])) {
                $categoryIds[] = (int) $product['category_id'];
            }
            if (!empty($product['id_product'])) {
                $excludedProductIds[] = (int) $product['id_product'];
            }
        }

        $relatedProducts = (new BrevoRelatedProductsProvider($this->context))->getRelatedProducts(
            array_values(array_unique($categoryIds)),
            array_values(array_unique($excludedProductIds)),
            3
        );

        $shopReviews = (new BrevoShopReviewsProvider())->getLatestApprovedReviews($idShop, 5);

        $orderDate = $this->formatDateForBrevo($order->date_add ?: date('Y-m-d H:i:s'));
        $orderDateFormatted = $this->formatCalendarDate((string) $order->date_add);
        $reorderUrl = str_replace(
            '{id_order}',
            (string) (int) $order->id,
            (string) Configuration::get(self::CONFIG_REORDER_URL_PATTERN)
        );

        return [
            'event_name' => 'vx_order_created',
            'event_date' => $orderDate,
            '_log_object_type' => 'order',
            '_log_object_id' => 'ps_order_' . (int) $order->id,
            'identifiers' => [
                'email_id' => (string) $customer->email,
                'ext_id' => 'ps_customer_' . (int) $customer->id,
            ],
            'contact_properties' => [
                'FIRSTNAME' => (string) $customer->firstname,
                'LASTNAME' => (string) $customer->lastname,
                'PS_CUSTOMER_ID' => (int) $customer->id,
            ],
            'event_properties' => [
                'customer' => $this->buildCustomerGroupPayload($customer),
                'order' => $this->buildOrderGroupPayload($order, $orderState, $currency, $products, [
                    'date' => $orderDate,
                    'date_formatted' => $orderDateFormatted,
                ]),
                'shipping' => $this->buildShippingGroupPayload($address, $carrier, $idLang),
                'payment' => $this->buildPaymentGroupPayload($order, $paymentMethodId),
                'reviews' => $shopReviews,
                'main_categories' => [],
                'misc' => $this->buildMiscGroupPayload([
                    'shop_review_url' => (string) Configuration::get(self::CONFIG_SHOP_REVIEW_URL),
                    'reorder_url' => $reorderUrl,
                    'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                ]),
                'order_id' => (int) $order->id,
                'order_reference' => (string) $order->reference,
                'order_date' => $orderDate,
                'order_date_formatted' => $orderDateFormatted,
                'order_status' => Validate::isLoadedObject($orderState) ? (string) $orderState->name : '',
                'order_status_id' => (int) $order->current_state,
                'shop_url' => (string) Configuration::get(self::CONFIG_SHOP_URL),
                'currency' => Validate::isLoadedObject($currency) ? (string) $currency->iso_code : '',
                'total_paid' => (float) $order->total_paid,
                'total_paid_tax_incl' => (float) $order->total_paid_tax_incl,
                'total_paid_tax_excl' => (float) $order->total_paid_tax_excl,
                'total_tax_amount' => (float) $order->total_paid_tax_incl - (float) $order->total_paid_tax_excl,
                'total_products' => (float) $order->total_products,
                'total_products_tax_incl' => (float) $order->total_products_wt,
                'total_products_tax_excl' => (float) $order->total_products,
                'total_products_tax_amount' => (float) $order->total_products_wt - (float) $order->total_products,
                'total_shipping' => (float) $order->total_shipping,
                'total_shipping_tax_incl' => (float) $order->total_shipping_tax_incl,
                'total_shipping_tax_excl' => (float) $order->total_shipping_tax_excl,
                'total_shipping_tax_amount' => (float) $order->total_shipping_tax_incl - (float) $order->total_shipping_tax_excl,
                'total_discounts' => (float) $order->total_discounts,
                'total_discounts_tax_incl' => (float) $order->total_discounts_tax_incl,
                'total_discounts_tax_excl' => (float) $order->total_discounts_tax_excl,
                'total_discounts_tax_amount' => (float) $order->total_discounts_tax_incl - (float) $order->total_discounts_tax_excl,
                'payment_method_id' => $paymentMethodId,
                'payment_method' => (string) $order->payment,
                'payment_module' => (string) $order->module,
                'carrier_id' => (int) $order->id_carrier,
                'carrier_reference_id' => Validate::isLoadedObject($carrier) ? (int) $carrier->id_reference : 0,
                'carrier_name' => Validate::isLoadedObject($carrier) ? (string) $carrier->name : '',
                'customer_email' => (string) $customer->email,
                'customer_firstname' => (string) $customer->firstname,
                'customer_lastname' => (string) $customer->lastname,
                'shipping_address' => $this->buildAddressPayload($address, $idLang),
                'products' => $products,
                'related_products' => $relatedProducts,
                'shop_reviews' => $shopReviews,
                'shop_review_url' => (string) Configuration::get(self::CONFIG_SHOP_REVIEW_URL),
                'reorder_url' => $reorderUrl,
                'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
            ],
        ];
    }

    private function buildOrderStatusPayload(Order $order, string $eventName, string $eventDateProperty): array
    {
        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer) || empty($customer->email)) {
            return [];
        }

        $currency = new Currency((int) $order->id_currency);
        $orderState = new OrderState((int) $order->current_state, (int) $order->id_lang);
        $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
        $idShop = (int) $order->id_shop ?: (int) $this->context->shop->id;
        $idLang = (int) $order->id_lang ?: (int) $this->context->language->id;
        $paymentMethodId = $this->getPaymentMethodId((string) $order->module);
        $products = $this->buildOrderProducts($order, $idLang);
        $categoryIds = [];
        $excludedProductIds = [];

        foreach ($products as $product) {
            if (!empty($product['category_id'])) {
                $categoryIds[] = (int) $product['category_id'];
            }
            if (!empty($product['id_product'])) {
                $excludedProductIds[] = (int) $product['id_product'];
            }
        }

        $servientrega = (new BrevoServientregaProvider())->getTrackingByOrderId((int) $order->id);
        $trackingCode = is_array($servientrega) ? (string) $servientrega['id_order_servientrega'] : '';
        $trackingUrl = is_array($servientrega) && $this->isValidUrl((string) $servientrega['rastreoEnvio'])
            ? (string) $servientrega['rastreoEnvio']
            : 'https://www.servientrega.com.ec/rastreo';
        $eventDate = date(DATE_ATOM);
        $orderDate = $this->formatDateForBrevo($order->date_add ?: date('Y-m-d H:i:s'));
        $orderDateFormatted = $this->formatCalendarDate((string) $order->date_add);
        $shopReviews = (new BrevoShopReviewsProvider())->getLatestApprovedReviews($idShop, 5);
        $reorderUrl = str_replace(
            '{id_order}',
            (string) (int) $order->id,
            (string) Configuration::get(self::CONFIG_REORDER_URL_PATTERN)
        );

        $contactProperties = [
            'FIRSTNAME' => (string) $customer->firstname,
            'LASTNAME' => (string) $customer->lastname,
            'PS_CUSTOMER_ID' => (int) $customer->id,
        ];

        if ($eventName === 'vx_order_sent') {
            $contactProperties['LAST_SENT_ORDER_ID'] = (int) $order->id;
            $contactProperties['LAST_SENT_ORDER_DATE'] = $eventDate;
        } else {
            $contactProperties['LAST_DELIVERED_ORDER_ID'] = (int) $order->id;
            $contactProperties['LAST_DELIVERED_ORDER_DATE'] = $eventDate;
        }

        return [
            'event_name' => $eventName,
            'event_date' => $eventDate,
            '_log_object_type' => 'order',
            '_log_object_id' => 'ps_order_' . (int) $order->id,
            'identifiers' => [
                'email_id' => (string) $customer->email,
                'ext_id' => 'ps_customer_' . (int) $customer->id,
            ],
            'contact_properties' => $contactProperties,
            'event_properties' => [
                'customer' => $this->buildCustomerGroupPayload($customer),
                'order' => $this->buildOrderGroupPayload($order, $orderState, $currency, $products, [
                    'date' => $orderDate,
                    'date_formatted' => $orderDateFormatted,
                    $eventDateProperty => $eventDate,
                ]),
                'shipping' => $this->buildShippingGroupPayload(null, $carrier, $idLang, [
                    'tracking_code' => $trackingCode,
                    'tracking_url' => $trackingUrl,
                    'servientrega' => $servientrega ?: [],
                ]),
                'payment' => $this->buildPaymentGroupPayload($order, $paymentMethodId),
                'reviews' => $shopReviews,
                'main_categories' => [],
                'misc' => $this->buildMiscGroupPayload([
                    'shop_review_url' => (string) Configuration::get(self::CONFIG_SHOP_REVIEW_URL),
                    'reorder_url' => $reorderUrl,
                    'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                ]),
                'order_id' => (int) $order->id,
                'order_reference' => (string) $order->reference,
                'order_date' => $orderDate,
                'order_date_formatted' => $orderDateFormatted,
                'order_status' => Validate::isLoadedObject($orderState) ? (string) $orderState->name : '',
                'order_status_id' => (int) $order->current_state,
                'shop_url' => (string) Configuration::get(self::CONFIG_SHOP_URL),
                'currency' => Validate::isLoadedObject($currency) ? (string) $currency->iso_code : '',
                'total_paid' => (float) $order->total_paid,
                'total_paid_tax_incl' => (float) $order->total_paid_tax_incl,
                'total_paid_tax_excl' => (float) $order->total_paid_tax_excl,
                'total_tax_amount' => (float) $order->total_paid_tax_incl - (float) $order->total_paid_tax_excl,
                'total_products' => (float) $order->total_products,
                'total_products_tax_incl' => (float) $order->total_products_wt,
                'total_products_tax_excl' => (float) $order->total_products,
                'total_products_tax_amount' => (float) $order->total_products_wt - (float) $order->total_products,
                'total_shipping' => (float) $order->total_shipping,
                'total_shipping_tax_incl' => (float) $order->total_shipping_tax_incl,
                'total_shipping_tax_excl' => (float) $order->total_shipping_tax_excl,
                'total_shipping_tax_amount' => (float) $order->total_shipping_tax_incl - (float) $order->total_shipping_tax_excl,
                'total_discounts' => (float) $order->total_discounts,
                'total_discounts_tax_incl' => (float) $order->total_discounts_tax_incl,
                'total_discounts_tax_excl' => (float) $order->total_discounts_tax_excl,
                'total_discounts_tax_amount' => (float) $order->total_discounts_tax_incl - (float) $order->total_discounts_tax_excl,
                'payment_method_id' => $paymentMethodId,
                'payment_method' => (string) $order->payment,
                'payment_module' => (string) $order->module,
                'carrier_id' => (int) $order->id_carrier,
                'carrier_reference_id' => Validate::isLoadedObject($carrier) ? (int) $carrier->id_reference : 0,
                'carrier_name' => Validate::isLoadedObject($carrier) ? (string) $carrier->name : '',
                'tracking_code' => $trackingCode,
                'tracking_url' => $trackingUrl,
                'servientrega' => $servientrega ?: [],
                $eventDateProperty => $eventDate,
                'products' => $products,
                'related_products' => (new BrevoRelatedProductsProvider($this->context))->getRelatedProducts(
                    array_values(array_unique($categoryIds)),
                    array_values(array_unique($excludedProductIds)),
                    3
                ),
                'shop_reviews' => $shopReviews,
                'shop_review_url' => (string) Configuration::get(self::CONFIG_SHOP_REVIEW_URL),
                'reorder_url' => $reorderUrl,
                'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
            ],
        ];
    }

    private function buildSubscriberPayload(string $email, array $hookParams): array
    {
        $customer = $this->findCustomerByEmail($email);
        $idShop = (int) $this->context->shop->id;
        $subscribedAt = date(DATE_ATOM);
        $source = !empty($hookParams['hookName']) ? (string) $hookParams['hookName'] : 'ps_emailsubscription';
        $shopReviews = (new BrevoShopReviewsProvider())->getLatestApprovedReviews($idShop, 5);
        $mainCategories = $this->getMainCategories((int) $this->context->language->id, $idShop);

        return [
            'event_name' => 'vx_subscriber',
            'event_date' => $subscribedAt,
            '_log_object_type' => 'subscription',
            '_log_object_id' => 'newsletter_' . $this->normalizeIdentifier($email),
            'identifiers' => [
                'email_id' => $email,
                'ext_id' => 'newsletter_' . $this->normalizeIdentifier($email),
            ],
            'contact_properties' => [
                'EMAIL' => $email,
                'FIRSTNAME' => Validate::isLoadedObject($customer) ? (string) $customer->firstname : '',
                'LASTNAME' => Validate::isLoadedObject($customer) ? (string) $customer->lastname : '',
                'NEWSLETTER' => true,
                'SUBSCRIBED_AT' => $subscribedAt,
                'SUBSCRIPTION_SOURCE' => $source,
            ],
            'event_properties' => [
                'customer' => $this->buildCustomerGroupPayload($customer, $email),
                'reviews' => $shopReviews,
                'main_categories' => $mainCategories,
                'misc' => $this->buildMiscGroupPayload([
                    'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                ]),
                'email' => $email,
                'customer_id' => Validate::isLoadedObject($customer) ? (int) $customer->id : null,
                'is_customer' => Validate::isLoadedObject($customer),
                'shop_url' => (string) Configuration::get(self::CONFIG_SHOP_URL),
                'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                'shop_reviews' => $shopReviews,
                'main_categories' => $mainCategories,
            ],
        ];
    }

    private function buildAbandonedCartPayload(Cart $cart, array $cartRow): array
    {
        $customer = new Customer((int) $cart->id_customer);
        if (!Validate::isLoadedObject($customer) || empty($customer->email)) {
            return [];
        }

        $idLang = (int) $cart->id_lang ?: (int) $this->context->language->id;
        $idShop = (int) $cart->id_shop ?: (int) $this->context->shop->id;
        $currency = new Currency((int) $cart->id_currency);
        $products = $this->buildCartProducts($cart, $idLang);
        if (empty($products)) {
            return [];
        }

        $categoryIds = [];
        $excludedProductIds = [];
        foreach ($products as $product) {
            if (!empty($product['category_id'])) {
                $categoryIds[] = (int) $product['category_id'];
            }
            if (!empty($product['id_product'])) {
                $excludedProductIds[] = (int) $product['id_product'];
            }
        }

        $eventDate = date(DATE_ATOM);
        $cartUpdatedAt = $this->formatDateForBrevo((string) $cart->date_upd);
        $cartTotalTaxIncl = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $cartTotalTaxExcl = (float) $cart->getOrderTotal(false, Cart::BOTH);
        $cartProductsTotalTaxIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $cartProductsTotalTaxExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $cartShippingTotalTaxIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $cartShippingTotalTaxExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_SHIPPING);
        $cartDiscountsTotalTaxIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $cartDiscountsTotalTaxExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS);

        return [
            'event_name' => 'vx_abandoned_cart',
            'event_date' => $eventDate,
            '_log_object_type' => 'cart',
            '_log_object_id' => 'ps_cart_' . (int) $cart->id,
            'identifiers' => [
                'email_id' => (string) $customer->email,
                'ext_id' => 'ps_customer_' . (int) $customer->id,
            ],
            'contact_properties' => [
                'FIRSTNAME' => (string) $customer->firstname,
                'LASTNAME' => (string) $customer->lastname,
                'PS_CUSTOMER_ID' => (int) $customer->id,
                'LAST_ABANDONED_CART_ID' => (int) $cart->id,
                'LAST_ABANDONED_CART_DATE' => $eventDate,
            ],
            'event_properties' => [
                'customer' => $this->buildCustomerGroupPayload($customer),
                'cart' => $this->buildCartGroupPayload($cart, $products, $cartUpdatedAt, [
                    'total_tax_incl' => $cartTotalTaxIncl,
                    'total_tax_excl' => $cartTotalTaxExcl,
                    'products_tax_incl' => $cartProductsTotalTaxIncl,
                    'products_tax_excl' => $cartProductsTotalTaxExcl,
                    'shipping_tax_incl' => $cartShippingTotalTaxIncl,
                    'shipping_tax_excl' => $cartShippingTotalTaxExcl,
                    'discounts_tax_incl' => $cartDiscountsTotalTaxIncl,
                    'discounts_tax_excl' => $cartDiscountsTotalTaxExcl,
                ], $idLang),
                'shipping' => [
                    'carrier' => [],
                ],
                'payment' => [],
                'reviews' => [],
                'main_categories' => [],
                'misc' => $this->buildMiscGroupPayload([
                    'cart_url' => $this->context->link->getPageLink('cart', true, $idLang, ['action' => 'show']),
                    'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                ]),
                'cart_id' => (int) $cart->id,
                'customer_id' => (int) $customer->id,
                'customer_email' => (string) $customer->email,
                'shop_url' => (string) Configuration::get(self::CONFIG_SHOP_URL),
                'currency' => Validate::isLoadedObject($currency) ? (string) $currency->iso_code : '',
                'cart_total' => $cartTotalTaxIncl,
                'cart_total_tax_incl' => $cartTotalTaxIncl,
                'cart_total_tax_excl' => $cartTotalTaxExcl,
                'cart_total_tax_amount' => $cartTotalTaxIncl - $cartTotalTaxExcl,
                'cart_products_total' => $cartProductsTotalTaxIncl,
                'cart_products_total_tax_incl' => $cartProductsTotalTaxIncl,
                'cart_products_total_tax_excl' => $cartProductsTotalTaxExcl,
                'cart_products_total_tax_amount' => $cartProductsTotalTaxIncl - $cartProductsTotalTaxExcl,
                'cart_shipping_total' => $cartShippingTotalTaxIncl,
                'cart_shipping_total_tax_incl' => $cartShippingTotalTaxIncl,
                'cart_shipping_total_tax_excl' => $cartShippingTotalTaxExcl,
                'cart_shipping_total_tax_amount' => $cartShippingTotalTaxIncl - $cartShippingTotalTaxExcl,
                'cart_discounts_total' => $cartDiscountsTotalTaxIncl,
                'cart_discounts_total_tax_incl' => $cartDiscountsTotalTaxIncl,
                'cart_discounts_total_tax_excl' => $cartDiscountsTotalTaxExcl,
                'cart_discounts_total_tax_amount' => $cartDiscountsTotalTaxIncl - $cartDiscountsTotalTaxExcl,
                'cart_updated_at' => $cartUpdatedAt,
                'abandoned_minutes' => (int) floor((time() - strtotime((string) $cart->date_upd)) / 60),
                'cart_url' => $this->context->link->getPageLink('cart', true, $idLang, ['action' => 'show']),
                'contact_url' => (string) Configuration::get(self::CONFIG_CONTACT_URL),
                'products' => $products,
                'related_products' => (new BrevoRelatedProductsProvider($this->context))->getRelatedProducts(
                    array_values(array_unique($categoryIds)),
                    array_values(array_unique($excludedProductIds)),
                    3
                ),
            ],
        ];
    }

    private function buildCartProducts(Cart $cart, int $idLang): array
    {
        $products = [];

        foreach ($cart->getProducts() as $cartProduct) {
            $idProduct = (int) ($cartProduct['id_product'] ?? 0);
            $idProductAttribute = (int) ($cartProduct['id_product_attribute'] ?? 0);
            if ($idProduct <= 0) {
                continue;
            }

            $product = new Product($idProduct, false, $idLang, (int) $cart->id_shop);
            $categoryId = (int) ($product->id_category_default ?? 0);
            $quantity = (int) ($cartProduct['cart_quantity'] ?? $cartProduct['quantity'] ?? 0);
            $unitPriceTaxIncl = (float) ($cartProduct['price_wt'] ?? $cartProduct['price'] ?? 0);
            $unitPriceTaxExcl = (float) ($cartProduct['price'] ?? $unitPriceTaxIncl);
            $totalPriceTaxIncl = (float) ($cartProduct['total_wt'] ?? ($unitPriceTaxIncl * $quantity));
            $totalPriceTaxExcl = (float) ($cartProduct['total'] ?? ($unitPriceTaxExcl * $quantity));

            $products[] = [
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
                'reference' => (string) ($cartProduct['reference'] ?? ''),
                'name' => (string) ($cartProduct['name'] ?? ''),
                'category_id' => $categoryId,
                'category_name' => $this->getCategoryName($categoryId, $idLang),
                'quantity' => $quantity,
                'unit_price' => $unitPriceTaxIncl,
                'unit_price_tax_incl' => $unitPriceTaxIncl,
                'unit_price_tax_excl' => $unitPriceTaxExcl,
                'unit_price_tax_amount' => $unitPriceTaxIncl - $unitPriceTaxExcl,
                'total_price' => $totalPriceTaxIncl,
                'total_price_tax_incl' => $totalPriceTaxIncl,
                'total_price_tax_excl' => $totalPriceTaxExcl,
                'total_price_tax_amount' => $totalPriceTaxIncl - $totalPriceTaxExcl,
                'tax_rate' => (float) ($cartProduct['rate'] ?? 0),
                'tax_name' => (string) ($cartProduct['tax_name'] ?? ''),
                'product_url' => $this->context->link->getProductLink($product),
                'image_url' => $this->getProductImageUrl($product, $idProduct, $idProductAttribute),
                'attributes' => $this->getProductAttributes($idProduct, $idProductAttribute),
            ];
        }

        return $products;
    }

    private function buildOrderProducts(Order $order, int $idLang): array
    {
        $products = [];

        foreach ($order->getProducts() as $orderProduct) {
            $idProduct = (int) ($orderProduct['product_id'] ?? $orderProduct['id_product'] ?? 0);
            $idProductAttribute = (int) ($orderProduct['product_attribute_id'] ?? $orderProduct['id_product_attribute'] ?? 0);
            if ($idProduct <= 0) {
                continue;
            }

            $product = new Product($idProduct, false, $idLang, (int) $order->id_shop);
            $categoryId = (int) ($product->id_category_default ?? 0);
            $unitPriceTaxIncl = (float) ($orderProduct['unit_price_tax_incl'] ?? $orderProduct['product_price'] ?? 0);
            $unitPriceTaxExcl = (float) ($orderProduct['unit_price_tax_excl'] ?? $orderProduct['product_price'] ?? $unitPriceTaxIncl);
            $totalPriceTaxIncl = (float) ($orderProduct['total_price_tax_incl'] ?? 0);
            $totalPriceTaxExcl = (float) ($orderProduct['total_price_tax_excl'] ?? $orderProduct['total_price'] ?? 0);

            $products[] = [
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
                'reference' => (string) ($orderProduct['product_reference'] ?? ''),
                'name' => (string) ($orderProduct['product_name'] ?? ''),
                'category_id' => $categoryId,
                'category_name' => $this->getCategoryName($categoryId, $idLang),
                'quantity' => (int) ($orderProduct['product_quantity'] ?? 0),
                'unit_price' => $unitPriceTaxIncl,
                'unit_price_tax_incl' => $unitPriceTaxIncl,
                'unit_price_tax_excl' => $unitPriceTaxExcl,
                'unit_price_tax_amount' => $unitPriceTaxIncl - $unitPriceTaxExcl,
                'total_price' => $totalPriceTaxIncl,
                'total_price_tax_incl' => $totalPriceTaxIncl,
                'total_price_tax_excl' => $totalPriceTaxExcl,
                'total_price_tax_amount' => $totalPriceTaxIncl - $totalPriceTaxExcl,
                'tax_rate' => (float) ($orderProduct['tax_rate'] ?? 0),
                'tax_name' => (string) ($orderProduct['tax_name'] ?? ''),
                'product_url' => $this->context->link->getProductLink($product),
                'image_url' => $this->getProductImageUrl($product, $idProduct, $idProductAttribute),
                'is_variant' => $idProductAttribute > 0,
                'attributes' => $this->getProductAttributes($idProduct, $idProductAttribute),
            ];
        }

        return $products;
    }

    private function buildAddressPayload(Address $address, int $idLang): array
    {
        if (!Validate::isLoadedObject($address)) {
            return [];
        }

        $country = new Country((int) $address->id_country, $idLang);
        $stateName = '';
        if ((int) $address->id_state > 0) {
            $state = new State((int) $address->id_state);
            $stateName = Validate::isLoadedObject($state) ? (string) $state->name : '';
        }

        return [
            'firstname' => (string) $address->firstname,
            'lastname' => (string) $address->lastname,
            'dni' => (string) $address->dni,
            'address1' => (string) $address->address1,
            'address2' => (string) $address->address2,
            'city' => (string) $address->city,
            'state' => $stateName,
            'country' => Validate::isLoadedObject($country) ? (string) $country->name : '',
            'phone' => (string) ($address->phone_mobile ?: $address->phone),
        ];
    }

    private function buildCustomerGroupPayload(Customer $customer, string $emailFallback = ''): array
    {
        $isCustomer = Validate::isLoadedObject($customer);

        return [
            'id' => $isCustomer ? (int) $customer->id : null,
            'email' => $isCustomer ? (string) $customer->email : $emailFallback,
            'firstname' => $isCustomer ? (string) $customer->firstname : '',
            'lastname' => $isCustomer ? (string) $customer->lastname : '',
            'is_customer' => $isCustomer,
        ];
    }

    private function buildOrderGroupPayload(Order $order, OrderState $orderState, Currency $currency, array $items, array $dates = []): array
    {
        $orderPayload = [
            'id' => (int) $order->id,
            'reference' => (string) $order->reference,
            'date' => (string) ($dates['date'] ?? ''),
            'date_formatted' => (string) ($dates['date_formatted'] ?? ''),
            'status' => Validate::isLoadedObject($orderState) ? (string) $orderState->name : '',
            'status_id' => (int) $order->current_state,
            'currency' => Validate::isLoadedObject($currency) ? (string) $currency->iso_code : '',
            'totals' => [
                'paid' => (float) $order->total_paid,
                'paid_tax_incl' => (float) $order->total_paid_tax_incl,
                'paid_tax_excl' => (float) $order->total_paid_tax_excl,
                'tax_amount' => (float) $order->total_paid_tax_incl - (float) $order->total_paid_tax_excl,
                'products' => (float) $order->total_products,
                'products_tax_incl' => (float) $order->total_products_wt,
                'products_tax_excl' => (float) $order->total_products,
                'products_tax_amount' => (float) $order->total_products_wt - (float) $order->total_products,
                'shipping' => (float) $order->total_shipping,
                'shipping_tax_incl' => (float) $order->total_shipping_tax_incl,
                'shipping_tax_excl' => (float) $order->total_shipping_tax_excl,
                'shipping_tax_amount' => (float) $order->total_shipping_tax_incl - (float) $order->total_shipping_tax_excl,
                'discounts' => (float) $order->total_discounts,
                'discounts_tax_incl' => (float) $order->total_discounts_tax_incl,
                'discounts_tax_excl' => (float) $order->total_discounts_tax_excl,
                'discounts_tax_amount' => (float) $order->total_discounts_tax_incl - (float) $order->total_discounts_tax_excl,
            ],
            'items' => $items,
        ];

        if (!empty($dates['sent_at'])) {
            $orderPayload['sent_at'] = (string) $dates['sent_at'];
        }
        if (!empty($dates['delivered_at'])) {
            $orderPayload['delivered_at'] = (string) $dates['delivered_at'];
        }

        return $orderPayload;
    }

    private function buildCartGroupPayload(Cart $cart, array $items, string $cartUpdatedAt, array $totals, int $idLang): array
    {
        $totalTaxIncl = (float) ($totals['total_tax_incl'] ?? 0);
        $totalTaxExcl = (float) ($totals['total_tax_excl'] ?? 0);
        $productsTaxIncl = (float) ($totals['products_tax_incl'] ?? 0);
        $productsTaxExcl = (float) ($totals['products_tax_excl'] ?? 0);
        $shippingTaxIncl = (float) ($totals['shipping_tax_incl'] ?? 0);
        $shippingTaxExcl = (float) ($totals['shipping_tax_excl'] ?? 0);
        $discountsTaxIncl = (float) ($totals['discounts_tax_incl'] ?? 0);
        $discountsTaxExcl = (float) ($totals['discounts_tax_excl'] ?? 0);

        return [
            'id' => (int) $cart->id,
            'updated_at' => $cartUpdatedAt,
            'abandoned_minutes' => (int) floor((time() - strtotime((string) $cart->date_upd)) / 60),
            'url' => $this->context->link->getPageLink('cart', true, $idLang, ['action' => 'show']),
            'totals' => [
                'total' => $totalTaxIncl,
                'total_tax_incl' => $totalTaxIncl,
                'total_tax_excl' => $totalTaxExcl,
                'total_tax_amount' => $totalTaxIncl - $totalTaxExcl,
                'products' => $productsTaxIncl,
                'products_tax_incl' => $productsTaxIncl,
                'products_tax_excl' => $productsTaxExcl,
                'products_tax_amount' => $productsTaxIncl - $productsTaxExcl,
                'shipping' => $shippingTaxIncl,
                'shipping_tax_incl' => $shippingTaxIncl,
                'shipping_tax_excl' => $shippingTaxExcl,
                'shipping_tax_amount' => $shippingTaxIncl - $shippingTaxExcl,
                'discounts' => $discountsTaxIncl,
                'discounts_tax_incl' => $discountsTaxIncl,
                'discounts_tax_excl' => $discountsTaxExcl,
                'discounts_tax_amount' => $discountsTaxIncl - $discountsTaxExcl,
            ],
            'items' => $items,
        ];
    }

    private function buildShippingGroupPayload(?Address $address, Carrier $carrier, int $idLang, array $tracking = []): array
    {
        return [
            'address' => $address instanceof Address ? $this->buildAddressPayload($address, $idLang) : [],
            'carrier' => [
                'id' => Validate::isLoadedObject($carrier) ? (int) $carrier->id : 0,
                'reference_id' => Validate::isLoadedObject($carrier) ? (int) $carrier->id_reference : 0,
                'name' => Validate::isLoadedObject($carrier) ? (string) $carrier->name : '',
            ],
            'tracking_code' => (string) ($tracking['tracking_code'] ?? ''),
            'tracking_url' => (string) ($tracking['tracking_url'] ?? ''),
            'servientrega' => $tracking['servientrega'] ?? [],
        ];
    }

    private function buildPaymentGroupPayload(Order $order, int $paymentMethodId): array
    {
        return [
            'method_id' => $paymentMethodId,
            'method' => (string) $order->payment,
            'module' => (string) $order->module,
        ];
    }

    private function buildMiscGroupPayload(array $urls = []): array
    {
        $misc = array_merge([
            'shop_url' => (string) Configuration::get(self::CONFIG_SHOP_URL),
        ], $urls);

        foreach ($this->getCustomUrlsForPayload() as $key => $url) {
            if (!array_key_exists($key, $misc)) {
                $misc[$key] = $url;
            }
        }

        return $misc;
    }

    private function getCategoryName(int $idCategory, int $idLang): string
    {
        if ($idCategory <= 0) {
            return '';
        }

        $category = new Category($idCategory, $idLang);

        return Validate::isLoadedObject($category) ? (string) $category->name : '';
    }

    private function getProductImageUrl(Product $product, int $idProduct, int $idProductAttribute): string
    {
        $cover = Product::getCover($idProduct);
        if (!is_array($cover) || empty($cover['id_image'])) {
            return '';
        }

        $rewrite = is_array($product->link_rewrite)
            ? (string) reset($product->link_rewrite)
            : (string) $product->link_rewrite;

        return $this->context->link->getImageLink($rewrite, (string) $cover['id_image'], ImageType::getFormattedName('home'));
    }

    private function getProductAttributes(int $idProduct, int $idProductAttribute): array
    {
        if ($idProductAttribute <= 0 || !method_exists('Product', 'getAttributesParams')) {
            return [];
        }

        $attributes = Product::getAttributesParams($idProduct, $idProductAttribute);
        if (!is_array($attributes)) {
            return [];
        }

        return array_map(function (array $attribute): array {
            return [
                'label' => (string) ($attribute['group'] ?? ''),
                'value' => (string) ($attribute['name'] ?? ''),
            ];
        }, $attributes);
    }

    private function findCustomerByEmail(string $email): Customer
    {
        $idCustomer = (int) Customer::customerExists($email, true, false);

        return new Customer($idCustomer);
    }

    private function normalizeIdentifier(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized);

        return trim((string) $normalized, '_');
    }

    private function getPaymentMethodId(string $moduleName): int
    {
        if ($moduleName === '') {
            return 0;
        }

        $module = Module::getInstanceByName($moduleName);

        return Validate::isLoadedObject($module) ? (int) $module->id : 0;
    }

    private function getMainCategories(int $idLang, int $idShop): array
    {
        $rootCategoryId = (int) Configuration::get('PS_HOME_CATEGORY');
        if ($rootCategoryId <= 0) {
            $rootCategoryId = (int) Category::getRootCategory($idLang, null)->id;
        }

        $sql = 'SELECT c.id_category, cl.name, cl.description, cl.link_rewrite
            FROM `' . _DB_PREFIX_ . 'category` c
            INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON (
                cs.id_category = c.id_category
                AND cs.id_shop = ' . (int) $idShop . '
            )
            INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
                cl.id_category = c.id_category
                AND cl.id_lang = ' . (int) $idLang . '
                AND cl.id_shop = ' . (int) $idShop . '
            )
            WHERE c.active = 1
                AND c.id_parent = ' . (int) $rootCategoryId . '
            ORDER BY c.position ASC';

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        $categories = [];
        foreach ($rows as $row) {
            $idCategory = (int) $row['id_category'];
            $category = new Category($idCategory, $idLang, $idShop);
            $imageUrl = '';

            if (file_exists(_PS_CAT_IMG_DIR_ . $idCategory . '.jpg')) {
                $imageUrl = $this->context->link->getCatImageLink(
                    (string) $row['link_rewrite'],
                    $idCategory
                );
            }

            $categories[] = [
                'id_category' => $idCategory,
                'name' => (string) $row['name'],
                'description' => trim(strip_tags((string) $row['description'])),
                'category_url' => $this->context->link->getCategoryLink($category),
                'image_url' => $imageUrl,
            ];
        }

        return $categories;
    }

    private function getAbandonedCartCandidates(int $limit = 50): array
    {
        $delay = max(15, (int) Configuration::get(self::CONFIG_ABANDONED_CART_DELAY_MINUTES));
        $sql = 'SELECT c.id_cart, c.id_customer, c.id_lang, c.id_shop, c.id_currency, c.date_upd, cu.email
            FROM `' . _DB_PREFIX_ . 'cart` c
            INNER JOIN `' . _DB_PREFIX_ . 'customer` cu ON (cu.id_customer = c.id_customer)
            WHERE c.id_customer > 0
                AND cu.email != \'\'
                AND c.date_upd <= DATE_SUB(NOW(), INTERVAL ' . (int) $delay . ' MINUTE)
                AND c.date_upd >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND NOT EXISTS (
                    SELECT 1
                    FROM `' . _DB_PREFIX_ . 'orders` o
                    WHERE o.id_cart = c.id_cart
                )
                AND EXISTS (
                    SELECT 1
                    FROM `' . _DB_PREFIX_ . 'cart_product` cp
                    WHERE cp.id_cart = c.id_cart
                )
            ORDER BY c.date_upd ASC
            LIMIT ' . (int) $limit;

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    private function formatDateForBrevo(string $date): string
    {
        try {
            return (new DateTime($date))->format(DATE_ATOM);
        } catch (Exception $exception) {
            return date(DATE_ATOM);
        }
    }

    private function formatCalendarDate(string $date): string
    {
        try {
            $dateTime = new DateTime($date ?: date('Y-m-d H:i:s'));
        } catch (Exception $exception) {
            $dateTime = new DateTime();
        }

        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $month = $months[(int) $dateTime->format('n')] ?? $dateTime->format('m');

        return $dateTime->format('d') . ' ' . $month . ', ' . $dateTime->format('Y');
    }

    private function installDatabase(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'brevocustom_event_log` (
            `id_brevocustom_event_log` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `event_name` varchar(255) NOT NULL,
            `object_type` varchar(64) DEFAULT NULL,
            `object_id` varchar(64) DEFAULT NULL,
            `email` varchar(255) DEFAULT NULL,
            `payload_hash` varchar(64) DEFAULT NULL,
            `status` varchar(32) NOT NULL DEFAULT \'pending\',
            `http_code` int(11) DEFAULT NULL,
            `request_payload` mediumtext DEFAULT NULL,
            `response_body` mediumtext DEFAULT NULL,
            `error_message` text DEFAULT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_brevocustom_event_log`),
            KEY `event_object` (`event_name`, `object_type`, `object_id`),
            KEY `status` (`status`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return (bool) Db::getInstance()->execute($sql);
    }

    private function uninstallDatabase(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'brevocustom_event_log`';

        return (bool) Db::getInstance()->execute($sql);
    }

    private function installDefaultConfiguration(bool $overwrite = true): bool
    {
        $defaults = [
            self::CONFIG_ENABLED => 0,
            self::CONFIG_BREVO_API_KEY => '',
            self::CONFIG_DEBUG => 0,
            self::CONFIG_ABANDONED_CART_DELAY_MINUTES => 60,
            self::CONFIG_SHOP_URL => 'https://farmagro.desarrollovelox.com',
            self::CONFIG_SHOP_REVIEW_URL => 'https://farmagro.desarrollovelox.com/shop-reviews-add',
            self::CONFIG_CONTACT_URL => 'https://api.whatsapp.com/send/?phone=593959212641&text=%C2%A1Hola%21+Necesito+informaci%C3%B3n+sobre%3A+&type=phone_number&app_absent=0',
            self::CONFIG_REORDER_URL_PATTERN => 'https://farmagro.desarrollovelox.com/pedido?submitReorder=1&id_order={id_order}',
            self::CONFIG_CUSTOM_URLS => '[]',
            self::CONFIG_CRON_TOKEN => Tools::passwdGen(32),
        ];

        foreach ($defaults as $key => $value) {
            if ($overwrite || Configuration::get($key) === false) {
                if (!Configuration::updateValue($key, $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function deleteConfiguration(): bool
    {
        $keys = [
            self::CONFIG_ENABLED,
            self::CONFIG_BREVO_API_KEY,
            self::CONFIG_DEBUG,
            self::CONFIG_ABANDONED_CART_DELAY_MINUTES,
            self::CONFIG_SHOP_URL,
            self::CONFIG_SHOP_REVIEW_URL,
            self::CONFIG_CONTACT_URL,
            self::CONFIG_REORDER_URL_PATTERN,
            self::CONFIG_CUSTOM_URLS,
            self::CONFIG_CRON_TOKEN,
        ];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    private function postProcessConfiguration(): string
    {
        $enabled = (int) Tools::getValue(self::CONFIG_ENABLED, 0);
        $debug = (int) Tools::getValue(self::CONFIG_DEBUG, 0);
        $delay = (int) Tools::getValue(self::CONFIG_ABANDONED_CART_DELAY_MINUTES, 60);
        $apiKey = trim((string) Tools::getValue(self::CONFIG_BREVO_API_KEY, ''));
        $shopUrl = trim((string) Tools::getValue(self::CONFIG_SHOP_URL, ''));
        $shopReviewUrl = trim((string) Tools::getValue(self::CONFIG_SHOP_REVIEW_URL, ''));
        $contactUrl = trim((string) Tools::getValue(self::CONFIG_CONTACT_URL, ''));
        $reorderUrlPattern = trim((string) Tools::getValue(self::CONFIG_REORDER_URL_PATTERN, ''));
        $errors = [];
        $customUrls = $this->parseCustomUrlItemsFromRequest($errors);

        if ($enabled && $apiKey === '') {
            $errors[] = $this->trans('Brevo API key is required when the integration is enabled.', [], 'Modules.Brevocustom.Admin');
        }

        if ($delay < 15) {
            $errors[] = $this->trans('Abandoned cart delay must be at least 15 minutes.', [], 'Modules.Brevocustom.Admin');
        }

        foreach ([
            self::CONFIG_SHOP_URL => $shopUrl,
            self::CONFIG_SHOP_REVIEW_URL => $shopReviewUrl,
            self::CONFIG_CONTACT_URL => $contactUrl,
        ] as $label => $url) {
            if (!$this->isValidUrl($url)) {
                $errors[] = sprintf('%s: %s', $label, $this->trans('Invalid URL.', [], 'Modules.Brevocustom.Admin'));
            }
        }

        if (strpos($reorderUrlPattern, '{id_order}') === false || !$this->isValidUrl(str_replace('{id_order}', '1', $reorderUrlPattern))) {
            $errors[] = $this->trans('Reorder URL pattern must be a valid URL and include {id_order}.', [], 'Modules.Brevocustom.Admin');
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', array_map('htmlspecialchars', $errors)));
        }

        Configuration::updateValue(self::CONFIG_ENABLED, $enabled);
        Configuration::updateValue(self::CONFIG_BREVO_API_KEY, $apiKey);
        Configuration::updateValue(self::CONFIG_DEBUG, $debug);
        Configuration::updateValue(self::CONFIG_ABANDONED_CART_DELAY_MINUTES, $delay);
        Configuration::updateValue(self::CONFIG_SHOP_URL, rtrim($shopUrl, '/'));
        Configuration::updateValue(self::CONFIG_SHOP_REVIEW_URL, $shopReviewUrl);
        Configuration::updateValue(self::CONFIG_CONTACT_URL, $contactUrl);
        Configuration::updateValue(self::CONFIG_REORDER_URL_PATTERN, $reorderUrlPattern);
        Configuration::updateValue(self::CONFIG_CUSTOM_URLS, json_encode($customUrls));

        return $this->displayConfirmation($this->trans('Configuration saved.', [], 'Modules.Brevocustom.Admin'));
    }

    private function getConfigurationValues(): array
    {
        return [
            self::CONFIG_ENABLED => (int) Configuration::get(self::CONFIG_ENABLED),
            self::CONFIG_BREVO_API_KEY => (string) Configuration::get(self::CONFIG_BREVO_API_KEY),
            self::CONFIG_DEBUG => (int) Configuration::get(self::CONFIG_DEBUG),
            self::CONFIG_ABANDONED_CART_DELAY_MINUTES => (int) Configuration::get(self::CONFIG_ABANDONED_CART_DELAY_MINUTES),
            self::CONFIG_SHOP_URL => (string) Configuration::get(self::CONFIG_SHOP_URL),
            self::CONFIG_SHOP_REVIEW_URL => (string) Configuration::get(self::CONFIG_SHOP_REVIEW_URL),
            self::CONFIG_CONTACT_URL => (string) Configuration::get(self::CONFIG_CONTACT_URL),
            self::CONFIG_REORDER_URL_PATTERN => (string) Configuration::get(self::CONFIG_REORDER_URL_PATTERN),
            self::CONFIG_CUSTOM_URLS => (string) Configuration::get(self::CONFIG_CUSTOM_URLS),
        ];
    }

    private function parseCustomUrlItemsFromRequest(array &$errors): array
    {
        $keys = Tools::getValue('custom_url_key', []);
        $labels = Tools::getValue('custom_url_label', []);
        $urls = Tools::getValue('custom_url_value', []);

        if (!is_array($keys) || !is_array($labels) || !is_array($urls)) {
            return [];
        }

        $reservedKeys = $this->getReservedMiscUrlKeys();
        $seenKeys = [];
        $items = [];

        foreach ($keys as $index => $rawKey) {
            $rawUrl = isset($urls[$index]) ? trim((string) $urls[$index]) : '';
            $rawLabel = isset($labels[$index]) ? trim((string) $labels[$index]) : '';
            $key = $this->normalizeIdentifier((string) $rawKey);

            if ($key === '' && $rawUrl === '' && $rawLabel === '') {
                continue;
            }

            if ($key === '') {
                $errors[] = $this->trans('Custom URL key is required.', [], 'Modules.Brevocustom.Admin');
                continue;
            }

            if (in_array($key, $reservedKeys, true)) {
                $errors[] = sprintf('%s: %s', $key, $this->trans('Custom URL key is reserved.', [], 'Modules.Brevocustom.Admin'));
                continue;
            }

            if (isset($seenKeys[$key])) {
                $errors[] = sprintf('%s: %s', $key, $this->trans('Custom URL key is duplicated.', [], 'Modules.Brevocustom.Admin'));
                continue;
            }

            if (!$this->isValidUrl($rawUrl)) {
                $errors[] = sprintf('%s: %s', $key, $this->trans('Invalid URL.', [], 'Modules.Brevocustom.Admin'));
                continue;
            }

            $seenKeys[$key] = true;
            $items[] = [
                'key' => $key,
                'label' => $rawLabel,
                'url' => $rawUrl,
            ];
        }

        return $items;
    }

    private function getCustomUrlItems(): array
    {
        $json = (string) Configuration::get(self::CONFIG_CUSTOM_URLS);
        $items = json_decode($json, true);

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, function ($item): bool {
            return is_array($item)
                && !empty($item['key'])
                && !empty($item['url']);
        }));
    }

    private function getCustomUrlsForPayload(): array
    {
        $urls = [];
        foreach ($this->getCustomUrlItems() as $item) {
            $key = $this->normalizeIdentifier((string) $item['key']);
            $url = trim((string) $item['url']);
            if ($key !== '' && !in_array($key, $this->getReservedMiscUrlKeys(), true) && $this->isValidUrl($url)) {
                $urls[$key] = $url;
            }
        }

        return $urls;
    }

    private function getReservedMiscUrlKeys(): array
    {
        return [
            'shop_url',
            'shop_review_url',
            'contact_url',
            'reorder_url',
            'cart_url',
        ];
    }

    private function getStatusSummary(): array
    {
        $apiKey = (string) Configuration::get(self::CONFIG_BREVO_API_KEY);

        return [
            'enabled' => (bool) Configuration::get(self::CONFIG_ENABLED),
            'debug' => (bool) Configuration::get(self::CONFIG_DEBUG),
            'api_key_configured' => $apiKey !== '',
            'log_table' => $this->logTableExists(),
        ];
    }

    private function logTableExists(): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
                AND table_name = \'' . pSQL(_DB_PREFIX_ . 'brevocustom_event_log') . '\'';

        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    private function getRecentLogs(): array
    {
        if (!$this->logTableExists()) {
            return [];
        }

        $sql = 'SELECT event_name, object_type, object_id, email, status, http_code, date_add
            FROM `' . _DB_PREFIX_ . 'brevocustom_event_log`
            ORDER BY date_add DESC
            LIMIT 8';

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    private function isValidUrl(string $url): bool
    {
        return $url !== '' && (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}
