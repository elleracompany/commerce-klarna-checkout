<?php

namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\web\Response as WebResponse;

/**
 * Klarna represents the KCOv3 gateway
 *
 * @author      Ellera AS, <support@ellera.no>
 * @since       1.0
 * @deprecated  Deprecated in release 3.0
 */
class KlarnaCheckout extends BaseGateway
{
    /**
     * Setting: Title
     *
     * @var string
     */
    public $title = 'Klarna';

    /**
     * Setting: Description
     *
     * @var string
     */
    public $description = '';

    /**
     * Setting: Send Product Urls
     *
     * @var string
     */
    public $send_product_urls = true;

    /**
     * Setting: Logging
     *
     * @var string
     */
    public $log_debug_messages = true;

    /**
     * Setting: Test Mode
     *
     * @var string
     */
    public $test_mode = true;

    /**
     * Setting: Mandatory DOB
     *
     * @var string
     */
    public $mandatory_date_of_birth = false;

    /**
     * Setting: Mandatory DOB
     *
     * @var string
     */
    public $mandatory_national_identification_number = false;

    /**
     * Setting: API User (Prod, EU)
     *
     * @var string
     */
    public $api_eu_uid = '';

    /**
     * Setting: API Password (Prod, EU)
     *
     * @var string
     */
    public $api_eu_password = '';

    /**
     * Setting:  API User (Test, EU)
     *
     * @var string
     */
    public $api_eu_test_uid = '';

    /**
     * Setting: API Password (Test, EU)
     *
     * @var string
     */
    public $api_eu_test_password = '';

    /**
     * Setting: Mandatory Title
     *
     * @var string
     */
    public $api_eu_title_mandatory = false;

    /**
     * Setting: Consent Notice
     *
     * @var string
     */
    public $api_eu_consent_notice = false;

    /**
     * Setting: API User (Prod, US)
     *
     * @var string
     */
    public $api_us_uid = '';

    /**
     * Setting: API Password (Prod, US)
     *
     * @var string
     */
    public $api_us_password = '';

    /**
     * Setting: API User (Test, US)
     *
     * @var string
     */
    public $api_us_test_uid = '';

    /**
     * Setting: API Password (Test, US)
     *
     * @var string
     */
    public $api_us_test_password = '';

    /**
     * Production API URL
     * @var string
     */
    private $prod_url = 'https://api.klarna.com';

    /**
     * Test API URL
     * @var string
     */
    private $test_url = 'https://api.playground.klarna.com';

    /**
     * Setting: Payment Type
     *
     * @var string [authorize, purchase]
     */
    public $paymentType = 'authorize';

    /**
     * Setting: Checkout Page
     *
     * @var string
     */
    public $checkout = 'shop/checkout';

    /**
     * Setting: Order Complete Page
     *
     * @var string
     */
    public $push = 'shop/customer/order';

    /**
     * Setting: Terms Page
     *
     * @var string
     */
    public $terms = 'shop/terms';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'DEPRICATED - Klarna Checkout');
    }

    /**
     * @param Transaction     $transaction
     * @param BasePaymentForm $form
     *
     * @return RequestResponseInterface
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return null;
    }

    /**
     * @return null
     */
    public function getHtml()
    {
        return null;
    }

    /**
     * @param Transaction $transaction
     * @param string      $reference
     *
     * @return RequestResponseInterface
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completeAuthorize() method.
    }

    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completePurchase() method.
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        // TODO: Implement createPaymentSource() method.
    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource($token): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return null;
    }

    /**
     * @param Transaction     $transaction
     * @param BasePaymentForm $form
     *
     * @return RequestResponseInterface
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return null;
    }

    /**
     * @param Transaction $transaction
     *
     * @return RequestResponseInterface
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function processWebHook(): WebResponse
    {
        // TODO: Implement processWebHook() method.
    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function availableForUseWithOrder(Order $order): bool
    {
        return parent::availableForUseWithOrder($order);
    }

    /**
     * @param array $params
     *
     * @return null|string
     */
    public function getPaymentFormHtml(array $params)
    {
        return null;
    }

    /**
     * Render Settings HTML
     *
     * @return null|string
     */
    public function getSettingsHtml()
    {
        return null;
    }

    /**
     * Settings validation rules
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [
                [
                    'title',
                    'description',
                    'api_eu_uid',
                    'api_eu_password',
                    'api_eu_test_uid',
                    'api_eu_test_password',
                    'api_us_uid',
                    'api_us_password',
                    'api_us_test_uid',
                    'api_us_test_password',
                    'checkout',
                    'push',
                    'terms'
                ],
                'string'
            ],
            [
                [
                    'send_product_urls',
                    'log_debug_messages',
                    'test_mode',
                    'mandatory_date_of_birth',
                    'api_eu_title_mandatory',
                    'api_eu_consent_notice',
                    'mandatory_national_identification_number'
                ],
                'boolean'
            ]
        ];
    }

    /**
     * Settings Attribute Labels
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'api_eu_uid' => 'Production Username (UID)',
            'api_eu_password' => 'Production Password',
            'api_eu_test_uid' => 'Test Username (UID)',
            'api_eu_test_password' => 'Test Password',
            'api_us_uid' => 'Production Username (UID)',
            'api_us_password' => 'Production Password',
            'api_us_test_uid' => 'Test Username (UID)',
            'api_us_test_password' => 'Test Password',
            'send_product_urls' => 'Send Product URLs',
            'log_debug_messages' => 'Logging',
            'test_mode' => 'Test Mode',
            'mandatory_date_of_birth' => 'Mandatory Date of Birth',
            'mandatory_national_identification_number' => 'Mandatory National Identification Number',
            'api_eu_title_mandatory' => 'Title mandatory (GB)',
            'api_eu_consent_notice' => 'Show prefill consent notice',
            'checkout' => 'Checkout Page',
            'push' => 'Order Complete Page',
            'terms' => 'Store Terms Page'
        ];
    }
}
