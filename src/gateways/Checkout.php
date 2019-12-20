<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use ellera\commerce\klarna\klarna\Capture;
use ellera\commerce\klarna\klarna\Update;
use ellera\commerce\klarna\models\Order;
use craft\commerce\elements\Order as CraftOrder;
use ellera\commerce\klarna\models\responses\OrderResponse;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\models\forms\CheckoutFrom;
use yii\base\InvalidConfigException;
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
     * @param \craft\commerce\elements\Order $order
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function updateOrder(CraftOrder $order)
    {
        $response = new Update($this, $order);

        if($response->isSuccessful()) $this->log('Updated order '.$order->number.' ('.$order->id.')');

        if($response->getData()->shipping_address) {
            $order->setShippingAddress($this->createAddressFromResponse($response->getData()->shipping_address));
            if($response->getData()->shipping_address->email) $order->setEmail($response->getData()->shipping_address->email);
        }
        if($response->getData()->billing_address) {
            $order->setBillingAddress($this->createAddressFromResponse($response->getData()->billing_address));
            if($response->getData()->billing_address->email) $order->setEmail($response->getData()->billing_address->email);
        }
    }

    /**
     * @param Transaction $transaction
     * @param BasePaymentForm $form
     * @return RequestResponseInterface
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\ErrorException
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // Check if the received form is of the right type
        if(!$form instanceof CheckoutFrom)
            throw new BadRequestHttpException('Klarna Checkout only accepts CheckoutForm');

        // Populate the form
        $form->populate($transaction, $this);

        $response = $form->createOrder();

        if($response->isSuccessful()) $this->log('Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');

        return $response;
    }

    /**
     * @param Transaction $transaction
     * @param string $reference
     * @return RequestResponseInterface
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        $response = new Capture($this, $transaction);

        $response->setTransactionReference($reference);

        if($response->isSuccessful()) $this->log('Captured order '.$transaction->order->number.' ('.$transaction->order->id.')');

        else $this->log('Failed to capture order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return $response;
    }

    /**
     * @param Transaction $transaction
     * @param BasePaymentForm $form
     * @return RequestResponseInterface
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\commerce\errors\TransactionException
     * @throws \yii\base\ErrorException
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        $response = $this->captureKlarnaOrder($transaction);

        if($response->isSuccessful()) $this->log('Purchased order '.$transaction->order->number.' ('.$transaction->order->id.')');

        $transaction->order->updateOrderPaidInformation();
        return $response;
    }

    /**
     * @param Transaction $transaction
     * @return Capture
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \craft\commerce\errors\TransactionException
     * @throws \yii\base\ErrorException
     */
    protected function captureKlarnaOrder(Transaction $transaction) : Capture
    {
        $plugin = \craft\commerce\Plugin::getInstance();

        $response = new Capture($this, $transaction);

        $transaction->status = $response->isSuccessful() ? 'success' : 'failed';
        $transaction->code = $response->getCode();
        $transaction->message = $response->getMessage();
        $transaction->note = 'Automatic capture';
        $transaction->response = $response->getDecodedResponse();

        if(!$plugin->getTransactions()->saveTransaction($transaction)) throw new BadRequestHttpException('Could not save capture transaction');

        if($response->isSuccessful()) $this->log('Captured order '.$transaction->order->number.' ('.$transaction->order->id.')');

        return $response;
    }

    /**
     * @param array $params
     *
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function getPaymentFormHtml(array $params)
    {
        $order = $this->createCheckoutOrder();
        return $order->getHtmlSnippet();
    }

    /**
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new CheckoutFrom();
    }

    /**
     * @return Order
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    private function createCheckoutOrder() : Order
    {
        $commerce = craft\commerce\Plugin::getInstance();
        $cart = $commerce->getCarts()->getCart();

        $transaction = $commerce->getTransactions()->createTransaction($cart, null, 'authorize');

        $form = new CheckoutFrom();
        $form->populate($transaction, $this);

        /** @var $response OrderResponse */
        $response = $this->authorize($transaction, $form);
        $transaction->reference = $response->getTransactionReference();
        $transaction->code = $response->getCode();
        $transaction->message = $response->getMessage();
        $commerce->getTransactions()->saveTransaction($transaction);

        if($response->isSuccessful()) $this->log('Created order '.$transaction->order->number.' ('.$transaction->order->id.')');
        else $this->log('Failed to create order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return new Order($response);
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