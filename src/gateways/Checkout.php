<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use Exception;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\models\forms\CheckoutFrom;
use ellera\commerce\klarna\models\KlarnaBasePaymentForm;
use ellera\commerce\klarna\models\KlarnaOrder;
use ellera\commerce\klarna\models\KlarnaOrderResponse;
use yii\web\BadRequestHttpException;

/**
 * Class Checkout
 *
 * Checkout gateway for Klarna
 * https://developers.klarna.com/documentation/klarna-checkout/
 *
 * @package ellera\commerce\klarna\gateways
 */
class Checkout extends Base
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var string
     */
    public $gateway_handle = 'klarna-checkout';

    /**
     * Setting: Title
     *
     * @var string
     */
    public $title = 'Klarna Checkout';

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
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Klarna Checkout');
    }

    /**
     * @inheritdoc
     * @throws Exception When shit goes south
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // Check if the received form is of the right type
        if(!$form instanceof CheckoutFrom)
            throw new BadRequestHttpException('Klarna Checkout only accepts CheckoutForm');

        // Create the order
        $order = $form->createOrder($this, $transaction);


        /** @var KlarnaOrderResponse $response */
        try {
            $response = $this->getKlarnaOrderResponse('POST', '/checkout/v3/orders', $form->getOrderRequestBody());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna is expecting other values, make sure you\'ve added taxes as described in the documentation for the Klarna Checkout Plugin, and that you\'ve correctly set the Site Base URL. Klarna Response: '.$e->getMessage());
        }
        $order = new KlarnaOrder($response);

        $transaction->note = 'Created Klarna Order';
        $transaction->response = $response->get();
        $transaction->order->returnUrl = $transaction->gateway->push.'?number='.$transaction->order->number;
        $transaction->order->cancelUrl = $transaction->gateway->checkout;

        $order->getOrderId() ? $transaction->status = 'redirect' : $transaction->status = 'failed';

        if($response->isSuccessful()) $this->log('Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
        else $this->log('Failed to Authorize order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new CheckoutFrom();
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

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return true;
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
    public function supportsCompleteAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }
}