<?php


namespace ellera\commerce\klarna\models;


use craft\commerce\models\Transaction;
use ellera\commerce\klarna\gateways\BaseGateway;
use ellera\commerce\klarna\gateways\KlarnaHPP;

class KlarnaHppPaymentForm extends KlarnaBasePaymentForm
{
    /**
     * @param Transaction $transaction
     * @param BaseGateway $gateway
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function populate(Transaction $transaction, BaseGateway $gateway): void
    {
        parent::populate($transaction, $gateway);

        /** @var $gateway KlarnaHPP */
        $this->merchant_urls = [
            'back' => $this->getStoreUrl().$gateway->back.'?hppId={{session_id}}',
            'cancel' => $this->getStoreUrl().$gateway->cancel.'?hppId={{session_id}}',
            'error' => $this->getStoreUrl().$gateway->error.'?hppId={{session_id}}',
            'failure' => $this->getStoreUrl().$gateway->failure.'?hppId={{session_id}}',
            'privacy_policy' => $this->getStoreUrl().$gateway->privacy.'?hppId={{session_id}}',
            'success' => $this->getStoreUrl().$gateway->success.'?hppId={{session_id}}&auth_token={{authorization_token}}',
            'terms' => $this->getStoreUrl().$gateway->terms,
        ];
        $this->request_body = [
            'merchant_urls' => $this->merchant_urls,
            'options' => [
                'background_images' => [
                    [
                        'url' => 'https://ellera.no/images/mustbereplaced-main.jpg',
                        'width' => 1440
                    ]
                ],
                'logo_url' => 'https://ellera.no/images/ellera_black_transp.png',
                'page_title' => 'Complete your purchase',
                'payment_fallback' => true,
                'purchase_type' => 'buy'
            ],
            'payment_session_url' => null
        ];
        if(is_array($gateway->methods) && count($gateway->methods) == 1)
        {
            $this->request_body['options']['payment_method_category'] = $gateway->methods[0];
        }
        else {
            $methods = $gateway->methods;
            if(!$methods || empty($methods)) $methods = [];
            $this->request_body['options']['payment_method_categories'] = $methods;
        }
    }

    public function getSessionRequestBody() : array
    {
        $body = [
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

        if($this->billing_address) $body['billing_address'] = $this->billing_address;
        if($this->shipping_address) $body['shipping_address'] = $this->shipping_address;

        foreach ($this->order_lines as $order_line) $body['order_lines'][] = [
            'name' => $order_line->name,
            'quantity' => $order_line->quantity,
            'unit_price' => $order_line->unit_price,
            'tax_rate' => $order_line->tax_rate,
            'total_amount' => $order_line->total_amount,
            'total_tax_amount' => $order_line->total_tax_amount,
        ];

        return $body;
    }

    public function getHppSessionRequestBody(string $url) : array
    {
        $this->request_body['payment_session_url'] = $url;
        return $this->request_body;
    }
}