<?php


namespace ellera\commerce\klarna\models\forms;

use craft\commerce\models\Transaction;
use craft\helpers\UrlHelper;
use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\klarna\order\Create;
use ellera\commerce\klarna\models\responses\OrderResponse;
use yii\base\InvalidConfigException;
use GuzzleHttp\Exception\ClientException;

class CheckoutForm extends BasePaymentForm
{
    /**
     * @param Transaction $transaction
     * @param Base $gateway
     * @throws InvalidConfigException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function populate(Transaction $transaction, Base $gateway)
    {
        parent::populate($transaction, $gateway);

        $this->merchant_urls = [
            'terms' => UrlHelper::siteUrl($gateway->terms),
            'confirmation' => UrlHelper::actionUrl('/commerce-klarna-checkout/klarna/confirmation', ['hash' => $transaction->hash]),
            'checkout' => UrlHelper::siteUrl($gateway->checkout),
            'push' => UrlHelper::siteUrl($gateway->success, ['number' => $transaction->order->number])
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
        return new Create($this->gateway, $this);
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
            $this->gateway->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna is expecting other values, make sure you\'ve added taxes as described in the documentation for the Klarna Checkout Plugin, and that you\'ve correctly set the Site Base URL. Klarna Response: '.$e->getMessage());
        }
        return $response;
    }
}