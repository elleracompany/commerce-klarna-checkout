<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\base\Gateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Address;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\commerce\records\Country;
use craft\web\Response as WebResponse;
use ellera\commerce\klarna\models\responses\OrderAcknowledgeResponse;
use ellera\commerce\klarna\models\responses\OrderResponse;
use GuzzleHttp\Exception\ClientException;
use Throwable;
use yii\base\InvalidConfigException;

/**
 * Class Base
 *
 * Klarna Base Gateway
 * This is a base for all Klarna Gateways to extend from
 *
 * @package ellera\commerce\klarna\gateways
 */
class Base extends Gateway
{
    // Public Variables
    // =========================================================================

    /**
     * Setting: Logging
     *
     * @var bool
     */
    public $log_debug_messages = true;

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
    protected $prod_url = 'https://api.klarna.com';

    /**
     * Test API URL
     * @var string
     */
    protected $test_url = 'https://api.playground.klarna.com';

    /**
     * Setting: Mandatory DOB
     *
     * @var string
     */
    public $mandatory_national_identification_number = false;

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
     * Gateway handle
     *
     * @var bool|string
     */
    public $gateway_handle = false;

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
     * Setting: Description
     *
     * @var string
     */
    public $description = '';

    /**
     * Setting: Payment Type
     *
     * @var string [authorize, purchase]
     */
    public $paymentType = 'authorize';

    /**
     * Setting: Terms Page
     *
     * @var string
     */
    public $terms = 'shop/terms';

    /**
     * Setting: Send Product Urls
     *
     * @var string
     */
    public $send_product_urls = true;

    // Public Methods
    // =========================================================================

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
     * Makes an authorize request.
     *
     * @param Transaction $transaction The authorize transaction
     * @param BasePaymentForm $form A form filled with payment info
     * @return RequestResponseInterface
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement authorize() method.
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
        // TODO: Implement capture() method.
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
        // TODO: Implement purchase() method.
    }

    /**
     * Complete the authorization for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completeAuthorize() method.
    }

    /**
     * Complete the purchase for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completePurchase() method.
    }

    /**
     * @param Transaction $transaction
     * @return RequestResponseInterface
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        $amount = Craft::$app->request->getBodyParam('amount');
        $note = Craft::$app->request->getBodyParam('note');

        if($amount == '') $amount = $transaction->order->totalPaid;

        $body = [
            'refunded_amount' => (int)$amount*100,
            'description' => $note
        ];

        try {
            $response = new OrderResponse(
                'POST',
                $this->getApiUrl(),
                "/ordermanagement/v1/orders/{$transaction->reference}/refunds",
                $this->getApiId(),
                $this->getApiPassword(),
                $body
            );
        } catch (ClientException $e) {
            $this->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Something went wrong. Klarna Response: '.$e->getMessage());
        }

        $response->setTransactionReference($transaction->reference);
        if($response->isSuccessful()) $this->log('Refunded '.$amount.' from order '.$transaction->order->number.' ('.$transaction->order->id.')');
        else $this->log('Failed to refund order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return $response;
    }

    /**
     * Processes a webhook and return a response
     *
     * @return WebResponse
     * @throws Throwable if something goes wrong
     */
    public function processWebHook(): WebResponse
    {
        // TODO: Implement processWebHook() method.
    }

    /**
     * Returns payment form model to use in payment forms.
     *
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        // TODO: Implement getPaymentFormModel() method.
    }

    public function getPaymentFormHtml(array $params)
    {
        // TODO: Implement getPaymentFormHtml() method.
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
        // TODO: Implement createPaymentSource() method.
    }

    /**
     * Deletes a payment source on the gateway by its token.
     *
     * @param string $token
     * @return bool
     */
    public function deletePaymentSource($token): bool
    {
        // TODO: Implement deletePaymentSource() method.
    }

    /**
     * Klarna Log
     *
     * @param $message
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

    public function getApiUrl()
    {
        return $this->test_mode !== '1' ? $this->prod_url : $this->test_url;
    }

    public function getApiId()
    {
        return $this->test_mode !== '1' ? $this->api_eu_uid : $this->api_eu_test_uid;
    }

    public function getApiPassword()
    {
        return $this->test_mode !== '1' ? $this->api_eu_password : $this->api_eu_test_password;
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
     * @return mixed
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function getHtml()
    {
        try {
            $response = new OrderResponse(
                'GET',
                $this->getApiUrl(),
                '/checkout/v3/orders/' . Craft::$app->session->get('klarna_order_id'),
                $this->getApiId(),
                $this->getApiPassword()
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log($$e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna responded with an error: '.$e->getMessage());
        }
        return $response->getData()->html_snippet;
    }

    /**
     * @param string $orderId
     * @return OrderAcknowledgeResponse
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function acknowledgeOrder(string $orderId)
    {
        try {
            $response = new OrderAcknowledgeResponse(
                'POST',
                $this->getApiUrl(),
                '/ordermanagement/v1/orders/'.$orderId.'/acknowledge',
                $this->getApiId(),
                $this->getApiPassword()
            );
        } catch (ClientException $e) {
            $this->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Something went wrong. Klarna Response: '.$e->getMessage());
        }
        return $response;
    }

    /**
     * Returns true if gateway supports completing purchase requests
     *
     * @return bool
     */
    public function supportsCompletePurchase(): bool
    {
        // TODO: Implement supportsCompletePurchase() method.
    }

    /**
     * Returns true if gateway supports purchase requests.
     *
     * @return bool
     */
    public function supportsPurchase(): bool
    {
        // TODO: Implement supportsPurchase() method.
    }

    /**
     * Returns true if gateway supports partial refund requests.
     *
     * @return bool
     */
    public function supportsPartialRefund(): bool
    {
        // TODO: Implement supportsPartialRefund() method.
    }

    /**
     * Returns true if gateway supports authorize requests.
     *
     * @return bool
     */
    public function supportsAuthorize(): bool
    {
        // TODO: Implement supportsAuthorize() method.
    }

    /**
     * Returns true if gateway supports refund requests.
     *
     * @return bool
     */
    public function supportsRefund(): bool
    {
        // TODO: Implement supportsRefund() method.
    }

    /**
     * Returns true if gateway supports capture requests.
     *
     * @return bool
     */
    public function supportsCapture(): bool
    {
        // TODO: Implement supportsCapture() method.
    }

    /**
     * Returns true if gateway supports payment sources
     *
     * @return bool
     */
    public function supportsPaymentSources(): bool
    {
        // TODO: Implement supportsPaymentSources() method.
    }

    /**
     * Returns true if gateway supports completing authorize requests
     *
     * @return bool
     */
    public function supportsCompleteAuthorize(): bool
    {
        // TODO: Implement supportsCompleteAuthorize() method.
    }

    /**
     * Returns true if gateway supports webhooks.
     *
     * @return bool
     */
    public function supportsWebhooks(): bool
    {
        // TODO: Implement supportsWebhooks() method.
    }
}