<?php


namespace ellera\commerce\klarna\models;


use craft\commerce\models\Transaction;
use ellera\commerce\klarna\gateways\BaseGateway;
use ellera\commerce\klarna\gateways\KlarnaCheckout;
use ellera\commerce\klarna\models\forms\BasePaymentForm;

class KlarnaCheckoutPaymentForm extends BasePaymentForm
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
        /** @var $gateway KlarnaCheckout */
        $this->merchant_urls = [
            'terms' => $this->getStoreUrl().$gateway->terms,
            'confirmation' => $this->getStoreUrl().'actions/commerce-klarna-checkout/klarna/confirmation?hash='.$transaction->hash,
            'checkout' => $this->getStoreUrl().$gateway->checkout,
            'push' => $this->getStoreUrl().$gateway->push.'?number='.$transaction->order->number
        ];

        $this->request_body = [
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

        if($this->billing_address) $this->request_body['billing_address'] = $this->billing_address;
        if($this->shipping_address) $this->request_body['shipping_address'] = $this->shipping_address;

        foreach ($this->order_lines as $order_line) $this->request_body['order_lines'][] = [
            'name' => $order_line->name,
            'quantity' => $order_line->quantity,
            'unit_price' => $order_line->unit_price,
            'tax_rate' => $order_line->tax_rate,
            'total_amount' => $order_line->total_amount,
            'total_tax_amount' => $order_line->total_tax_amount,
        ];
    }
}