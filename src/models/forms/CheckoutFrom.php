<?php


namespace ellera\commerce\klarna\models\forms;

use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\models\KlarnaOrder;
use ellera\commerce\klarna\models\responses\OrderResponse;
use yii\base\InvalidConfigException;
use GuzzleHttp\Exception\ClientException;
use craft\commerce\models\Transaction;

class CheckoutFrom extends BasePaymentForm
{
    /**
     * Create a new Klarna Order
     *
     * @param Base $gateway
     * @param Transaction $transaction
     * @return KlarnaOrder
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\ErrorException
     */
    public function createOrder(Base $gateway, Transaction $transaction): KlarnaOrder
    {
        try {
            $response = $this->getKlarnaOrderResponse($gateway);
        } catch (ClientException $e) {
            $gateway->log($e->getCode() . ': ' . $e->getMessage());
            throw new InvalidConfigException('Klarna is expecting other values, make sure you\'ve added taxes as described in the documentation for the Klarna Checkout Plugin, and that you\'ve correctly set the Site Base URL. Klarna Response: '.$e->getMessage());
        }
    }

    /**
     * Get the response from Create Order
     *
     * @param Base $gateway
     * @return OrderResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\SiteNotFoundException
     */
    private function getKlarnaOrderResponse(Base $gateway) : OrderResponse
    {
        return new OrderResponse(
            'POST',
            $this->getStoreUrl(),
            '',
            $gateway->test_mode !== '1' ? $gateway->api_eu_uid : $gateway->api_eu_test_uid,
            $gateway->test_mode !== '1' ? $gateway->api_eu_password : $gateway->api_eu_test_password,
            $this->generateCreateOrderRequestBody()
        );
    }

    /**
     * Returns a Order Create Request Body
     *
     * @return array
     */
    private function generateCreateOrderRequestBody(): array
    {
        return [
            'purchase_country' => $this->purchase_country,
            'purchase_currency' => $this->purchase_currency,
            'locale' => $this->locale,
            'order_amount' => $this->order_amount,
            'order_tax_amount' => $this->order_tax_amount,
            'order_lines' => [],
            'merchant_reference1' => $this->merchant_reference1,
            'merchant_reference2' => $this->merchant_reference2,
            'options' => $this->options,
            'merchant_urls' => $this->merchant_urls
        ];
    }
}