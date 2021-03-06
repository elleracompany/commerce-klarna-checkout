<?php


namespace ellera\commerce\klarna\models\forms;

use craft\commerce\models\Transaction;
use craft\errors\SiteNotFoundException;
use craft\helpers\UrlHelper;
use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\gateways\Hosted;
use ellera\commerce\klarna\klarna\order\Create as CreateOrder;
use ellera\commerce\klarna\klarna\session\Create;
use ellera\commerce\klarna\models\responses\OrderResponse;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use GuzzleHttp\Exception\ClientException;
use ellera\commerce\klarna\klarna\session\Update;

class HostedForm extends BasePaymentForm
{
    /**
     * @var array
     */
    public $merchant_session_urls;

    /**
     * @param Transaction $transaction
     * @param Base $gateway
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @throws Exception
     */
    public function populate(Transaction $transaction, Base $gateway)
    {
        parent::populate($transaction, $gateway);
        /** @var $gateway Hosted */
        $this->merchant_urls = [
            'terms' => UrlHelper::siteUrl($gateway->terms),
            'confirmation' => UrlHelper::actionUrl('/commerce-klarna-checkout/klarna/confirmation', ['hash' => $transaction->hash]),
            'checkout' => UrlHelper::siteUrl($gateway->checkout),
            'push' => UrlHelper::siteUrl($gateway->success, ['number' => $transaction->order->number])
        ];

        $this->merchant_session_urls = [
            'success' => UrlHelper::actionUrl('/commerce-klarna-checkout/klarna/confirmation', ['hash' => $transaction->hash]),
            'cancel' => UrlHelper::siteUrl($gateway->cancel, ['transaction_token' => $transaction->hash, 'sid' => '{{session_id}}']),
            'back' => UrlHelper::siteUrl($gateway->back, ['transaction_token' => $transaction->hash, 'sid' => '{{session_id}}']),
            'failure' => UrlHelper::siteUrl($gateway->failure, ['transaction_token' => $transaction->hash, 'sid' => '{{session_id}}']),
            'error' => UrlHelper::siteUrl($gateway->error, ['transaction_token' => $transaction->hash, 'sid' => '{{session_id}}']),
        ];
    }

    /**
     * Create a new Klarna Order
     *
     * @return Create
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    public function createOrder()
    {
        $order = new CreateOrder($this->gateway, $this);
        $payment_session_url = $this->gateway->getApiUrl().'/checkout/v3/orders/'.$order->getData()->order_id;
        return new Create($this->gateway, $this, $payment_session_url, $order->getTransactionReference());
    }

    /**
     * Create a new Klarna Order
     *
     * @return Update
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    public function updateOrder()
    {
        return new Update($this->gateway, $this);
    }

    /**
     * Get the response from Create Order
     *
     * @return OrderResponse
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     */
    public function getKlarnaOrderResponse() : OrderResponse
    {
        try {
            $response = new OrderResponse(
                'POST',
                $this->gateway->getApiUrl(),
                '/checkout/v3/orders',
                $this->gateway->getApiId(),
                $this->gateway->getApiPassword(),
                $this->generateCreateOrderRequestBody()
            );
        } catch (ClientException $e) {
            $this->gateway->log($e->getCode() . ': ' . $e->getResponse()->getBody()->getContents());
            throw new InvalidConfigException('Klarna is expecting other values, make sure you\'ve added taxes as described in the documentation for the Klarna Checkout Plugin, and that you\'ve correctly set the Site Base URL. Klarna Response: '.$e->getMessage());
        }
        return $response;
    }

    /**
     * Generate Create Session Body
     *
     * @param string $payment_session_url
     * @return array
     */
    public function generateCreateSessionRequestBody(string $payment_session_url)
    {
        $body = [
            'payment_session_url' => $payment_session_url,
            'merchant_urls' => $this->merchant_session_urls,
        ];
        if(is_array($this->gateway->methods) && !empty($this->gateway->methods))
        {
            if(count($this->gateway->methods) == 1) $body['options']['payment_method_category'] = $this->gateway->methods[0];
            else $body['options']['payment_method_categories'] = $this->gateway->methods;
        }
        /** @var $this->gateway Hosted */
        if($this->gateway->getLogoUrl()) $body['options']['logo_url'] = $this->gateway->getLogoUrl();
        if($this->gateway->getBackgroundUrl()) $body['options']['background_images'] = $this->gateway->getBackgroundUrl();
        /*
        if(is_array($this->gateway->methods) && !empty($this->gateway->methods))
        {
            if(count($this->gateway->methods) == 1) $body['options']['payment_method_category'] = $this->gateway->methods[0];
            elseif(count($this->gateway->methods) > 1) $body['options']['payment_method_categories'] = $this->gateway->methods;
        }
        */
        return $body;
    }
}