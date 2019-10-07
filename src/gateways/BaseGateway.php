<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\web\Response as WebResponse;
use craft\commerce\elements\Order;
use craft\commerce\base\Gateway;
use craft\commerce\records\Country;
use craft\commerce\models\Address;
use craft\commerce\models\Transaction;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\PaymentSource;
use ellera\commerce\klarna\models\KlarnaOrderResponse;
use ellera\commerce\klarna\models\KlarnaSessionResponse;
use ellera\commerce\klarna\models\KlarnaHppSessionResponse;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

class BaseGateway extends Gateway
{
    // Public Variables
    // =========================================================================

    /**
     * Setting: API User (Prod, EU)
     *
     * @var string
     */
    public $api_eu_uid;

    /**
     * Setting: API Password (Prod, EU)
     *
     * @var string
     */
    public $api_eu_password;

    /**
     * Setting:  API User (Test, EU)
     *
     * @var string
     */
    public $api_eu_test_uid;

    /**
     * Setting: API Password (Test, EU)
     *
     * @var string
     */
    public $api_eu_test_password;

    /**
     * Setting: API User (Prod, US)
     *
     * @var string
     */
    public $api_us_uid;

    /**
     * Setting: API Password (Prod, US)
     *
     * @var string
     */
    public $api_us_password;

    /**
     * Setting: API User (Test, US)
     *
     * @var string
     */
    public $api_us_test_uid;

    /**
     * Setting: API Password (Test, US)
     *
     * @var string
     */
    public $api_us_test_password;

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
     * Gateway handle
     *
     * @var bool|string
     */
    public $gateway_handle = false;

    /**
     * Setting: Logging
     *
     * @var bool
     */
    public $log_debug_messages = true;

    /**
     * Setting: Test Mode
     *
     * @var string
     */
    public $test_mode = true;


    /**
     * Setting: Send Product Urls
     *
     * @var string
     */
    public $send_product_urls = true;

    /**
     * Setting: Terms Page
     *
     * @var string
     */
    public $terms = 'shop/terms';

    // Public Methods
    // =========================================================================

    /**
     * @param $method
     * @param $endpoint
     * @param $body
     *
     * @return KlarnaResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKlarnaOrderResponse($method, $endpoint, $body = []) : KlarnaOrderResponse
    {
        return new KlarnaOrderResponse(
            $method,
            $this->test_mode !== '1' ? $this->prod_url : $this->test_url,
            $endpoint,
            $this->test_mode !== '1' ? $this->api_eu_uid : $this->api_eu_test_uid,
            $this->test_mode !== '1' ? $this->api_eu_password : $this->api_eu_test_password,
            $body
        );
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $body
     * @return KlarnaHppSessionResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKlarnaSessionResponse($method, $endpoint, $body = []) : KlarnaSessionResponse
    {
        return new KlarnaSessionResponse(
            $method,
            $this->test_mode !== '1' ? $this->prod_url : $this->test_url,
            $endpoint,
            $this->test_mode !== '1' ? $this->api_eu_uid : $this->api_eu_test_uid,
            $this->test_mode !== '1' ? $this->api_eu_password : $this->api_eu_test_password,
            $body
        );
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $body
     * @return KlarnaHppSessionResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKlarnaHppSessionResponse($method, $endpoint, $body = []) : KlarnaHppSessionResponse
    {
        return new KlarnaHppSessionResponse(
            $method,
            $this->test_mode !== '1' ? $this->prod_url : $this->test_url,
            $endpoint,
            $this->test_mode !== '1' ? $this->api_eu_uid : $this->api_eu_test_uid,
            $this->test_mode !== '1' ? $this->api_eu_password : $this->api_eu_test_password,
            $body
        );
    }

    /**
     * @param Address $address
     * @param string  $email
     *
     * @return array
     */
    public function formatAddress(Address $address, string $email = '') : array
    {
        return [
            "organization_name" => $address->businessName,
            "given_name" => $address->firstName,
            "family_name" => $address->lastName,
            "title" => $address->title,
            "email" => $email,
            "street_address" => $address->address1,
            "street_address2" => $address->address2,
            "postal_code" => $address->zipCode,
            "city" => $address->city,
            "region" => $address->stateName,
            "phone" => $address->phone,
            "country" => $address->country->iso,
        ];
    }

    /**
     * @return mixed
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function getHtml()
    {
        try {
            $response = $this->getKlarnaOrderResponse('GET', '/checkout/v3/orders/' . Craft::$app->session->get('klarna_order_id'));
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log($$e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna responded with an error: '.$e->getMessage());
        }
        return $response->getData()->html_snippet;
    }

    /**
     * @param Order $order
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function updateOrder(Order $order)
    {
        try {
            $response = $this->getKlarnaOrderResponse('GET', '/checkout/v3/orders/' . $order->getLastTransaction()->reference);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna responded with an error: '.$e->getMessage());
        }
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
     * Render Settings HTML
     *
     * @return string|null
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getSettingsHtml()
    {
        if(!$this->gateway_handle) return null;
        return Craft::$app->getView()->renderTemplate('commerce-klarna-checkout/settings/'.$this->gateway_handle, ['gateway' => $this]);
    }

    /**
     * @return bool
     */
    public function hasHtml()
    {
        if(!Craft::$app->session->get('klarna_order_id') || strlen(Craft::$app->session->get('klarna_order_id')) < 20) return false;
        return true;
    }

    /**
     * @param $message
     *
     * @throws \yii\base\ErrorException
     */
    public function log($message)
    {
        if($this->log_debug_messages == '1' && $this->gateway_handle) {
            $file = Craft::getAlias('@storage/logs/'.$this->gateway_handle.'.log');
            $log = date('Y-m-d H:i:s').' '.$message."\n";
            \craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
        }
    }

    /**
     * @param Object $addr
     *
     * @return Address
     */
    protected function createAddressFromResponse(Object $addr)
    {
        $address = new Address();
        $country = Country::findOne(['iso' => strtoupper($addr->country)]);
        $address->firstName = $addr->given_name;
        $address->lastName = $addr->family_name;
        $address->address1 = $addr->street_address;
        $address->zipCode = $addr->postal_code;
        $address->city = $addr->city;
        $address->phone = $addr->phone;
        if($country) $address->countryId = $country->id;

        return $address;
    }

    /**
     * @param Transaction $transaction
     * @return KlarnaResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\commerce\errors\TransactionException
     * @throws \yii\base\ErrorException
     */
    protected function captureKlarnaOrder(Transaction $transaction) : KlarnaResponse
    {
        $plugin = \craft\commerce\Plugin::getInstance();
        $body = [
            'captured_amount' => (int)$transaction->paymentAmount * 100,
            'description' => $transaction->hash
        ];

        $response = $this->getKlarnaOrderResponse('POST', "/ordermanagement/v1/orders/{$transaction->reference}/captures", $body);

        $transaction->status = $response->isSuccessful() ? 'success' : 'failed';
        $transaction->code = $response->getCode();
        $transaction->message = $response->getMessage();
        $transaction->note = 'Automatic capture';
        $transaction->response = $response->get();

        if(!$plugin->getTransactions()->saveTransaction($transaction)) throw new BadRequestHttpException('Could not save capture transaction');

        if($response->isSuccessful()) $this->log('Captured order '.$transaction->order->number.' ('.$transaction->order->id.')');
        else $this->log('Failed to capture order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return $response;
    }

    /**
     * Makes an authorize request.
     *
     * @param Transaction $transaction The authorize transaction
     * @param BasePaymentForm $form A form filled with payment info
     * @return RequestResponseInterface
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {

    }

    /**
     * Makes a capture request.
     *
     * @param Transaction $transaction The capture transaction
     * @param string $reference Reference for the transaction being captured.
     * @return RequestResponseInterface
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {

    }

    /**
     * Complete the authorization for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {

    }

    /**
     * Complete the purchase for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {

    }

    /**
     * Creates a payment source from source data and user id.
     *
     * @param BasePaymentForm $sourceData
     * @param int $userId
     * @return PaymentSource
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {

    }

    /**
     * Deletes a payment source on the gateway by its token.
     *
     * @param string $token
     * @return bool
     */
    public function deletePaymentSource($token): bool
    {

    }

    /**
     * Returns payment form model to use in payment forms.
     *
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {

    }

    /**
     * Makes a purchase request.
     *
     * @param Transaction $transaction The purchase transaction
     * @param BasePaymentForm $form A form filled with payment info
     * @return RequestResponseInterface
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {

    }

    /**
     * Makes an refund request.
     *
     * @param Transaction $transaction The refund transaction
     * @return RequestResponseInterface
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {

    }

    /**
     * Processes a webhook and return a response
     *
     * @return WebResponse
     * @throws Throwable if something goes wrong
     */
    public function processWebHook(): WebResponse
    {

    }

    /**
     * Returns true if gateway supports authorize requests.
     *
     * @return bool
     */
    public function supportsAuthorize(): bool
    {

    }

    /**
     * Returns true if gateway supports capture requests.
     *
     * @return bool
     */
    public function supportsCapture(): bool
    {

    }

    /**
     * Returns true if gateway supports completing authorize requests
     *
     * @return bool
     */
    public function supportsCompleteAuthorize(): bool
    {

    }

    /**
     * Returns true if gateway supports completing purchase requests
     *
     * @return bool
     */
    public function supportsCompletePurchase(): bool
    {

    }

    /**
     * Returns true if gateway supports payment sources
     *
     * @return bool
     */
    public function supportsPaymentSources(): bool
    {

    }

    /**
     * Returns true if gateway supports purchase requests.
     *
     * @return bool
     */
    public function supportsPurchase(): bool
    {

    }

    /**
     * Returns true if gateway supports refund requests.
     *
     * @return bool
     */
    public function supportsRefund(): bool
    {

    }

    /**
     * Returns true if gateway supports partial refund requests.
     *
     * @return bool
     */
    public function supportsPartialRefund(): bool
    {

    }

    /**
     * Returns true if gateway supports webhooks.
     *
     * @return bool
     */
    public function supportsWebhooks(): bool
    {

    }

    /**
     * Returns true if gateway supports payments for the supplied Order.
     *
     * @param $order Order The order this gateway can or can not be available for payment with.
     * @return bool
     */
    public function availableForUseWithOrder(Order $order): bool
    {
        return true;
    }

    /**
     * Return payment form HTML
     *
     * @param array $params
     * @return string|void|null
     */
    public function getPaymentFormHtml(array $params)
    {

    }
}