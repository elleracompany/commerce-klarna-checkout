<?php

namespace ellera\commerce\klarna\models\forms;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Address;
use craft\commerce\models\Country;
use craft\commerce\records\Country as CountryRecord;
use craft\helpers\UrlHelper;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\payments\BasePaymentForm as CommerceBasePaymentForm;
use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\models\OrderLine;
use craft\commerce\models\Transaction;
use yii\base\InvalidConfigException;


class BasePaymentForm extends CommerceBasePaymentForm
{
	/**
	 * Country Short Code
	 *
	 * @var string
	 */
	public $purchase_country;

	/**
	 * Currency Acronym/Abbreviation
	 *
	 * @var string
	 */
	public $purchase_currency;

	/**
	 * User Locale
	 *
	 * @var string
	 */
	public $locale;

	/**
	 * Total price of order in $purchase_currency
	 * fractional denomination
	 *
	 * @var int
	 */
	public $order_amount;

	/**
	 * Klarna Formatted Address array
	 *
	 * @var array
	 */
	public $billing_address;

	/**
	 * Klarna Formatted Address array
	 *
	 * @var array
	 */
	public $shipping_address;

	/**
	 * Array of Order Lines
	 *
	 * @var OrderLine[]
	 */
	public $order_lines;

	/**
	 * Order Reference 1
	 * Use Order->shortNumber
	 *
	 * @var string
	 */
	public $merchant_reference1;

	/**
	 * Order Reference 2
	 * Use Order->number
	 *
	 * @var string
	 */
	public $merchant_reference2;

	/**
	 * Klarna Order Options Array
	 *
	 * @var array
	 */
	public $options;

    /**
     * Merchant Urls
     * @var array
     */
	public $merchant_urls = [];

    /**
     * Craft Commerce Object
     *
     * @var Commerce
     */
    public $commerce;

    /**
     * Craft Commerce Country
     *
     * @var Country
     */
    public $country;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * @var Base
     */
    public $gateway;

    /**
     * @var array
     */
    public $external_payment_methods;

    /**
     * @var array
     */
    public $external_checkouts;

    /**
     * @param Transaction $transaction
     * @param Base $gateway
     * @throws InvalidConfigException
     */
    public function populate(Transaction $transaction, Base $gateway)
    {
        $this->transaction = $transaction;
        $this->gateway = $gateway;
        $this->commerce = Commerce::getInstance();


        if(is_numeric($gateway->store_country) && $gateway->store_country > 0)
        {
            $country = CountryRecord::findOne($gateway->store_country);
            if(!$country) throw new InvalidConfigException("Invalid country selected for the gateway (ID:{$gateway->store_country}).");
        }
        else
        {
            $country = $this->commerce->getAddresses()->getStoreLocationAddress()->getCountry();
            if(!isset($country->iso) || $country->iso == null) throw new InvalidConfigException('Klarna requires Store Location Country to be set. Please visit Commerce -> Store Settings -> Store Location and update the information.');
        }

        if(Craft::$app->plugins->getPlugin('commerce')->is(Commerce::EDITION_LITE)) $order_lines = $gateway->getOrderLinesLite($transaction->order, $gateway);
        else $order_lines = $gateway->getOrderLines($transaction->order, $gateway);

        $this->purchase_country = $country->iso;
        $this->purchase_currency = $transaction->order->paymentCurrency;
        $this->locale = $transaction->order->orderLanguage;
        $this->order_amount = round($transaction->order->getTotalPrice()*100);
        $this->billing_address = $transaction->order->billingAddress instanceof Address ? $gateway->formatAddress($transaction->order->billingAddress, $transaction->order->email) : null;
        $this->shipping_address = $transaction->order->shippingAddress instanceof Address ? $gateway->formatAddress($transaction->order->shippingAddress, $transaction->order->email) : null;
        $this->order_lines = $order_lines;
        $this->merchant_reference1 = $transaction->order->shortNumber;
        $this->merchant_reference2 = $transaction->order->number;
        $this->external_payment_methods = $this->formatMethods($gateway->external_payment_methods);
        $this->external_checkouts = $this->formatMethods($gateway->external_checkouts);
        $this->options = [
            'date_of_birth_mandatory' => $gateway->mandatory_date_of_birth == '1',
            'national_identification_number_mandatory' => $gateway->mandatory_national_identification_number == '1',
            'allow_separate_shipping_address' => true,
            'title_mandatory' => $gateway->api_eu_title_mandatory == '1',
            'show_subtotal_detail' => true
        ];
    }

    public function formatMethods($methods)
    {
        $formatted = [];
        if(is_array($methods)) foreach ($methods as $method)
        {
            $clean = [];
            if(isset($method['name']) && strlen($method['name']) > 1) $clean['name'] = $method['name'];
            if(isset($method['redirect_url']) && strlen($method['redirect_url']) > 1) $clean['redirect_url'] = $method['redirect_url'];
            if(isset($method['image_url']) && strlen($method['image_url']) > 1) $clean['image_url'] = $method['image_url'];
            if(isset($method['fee']) && strlen($method['fee']) > 1) $clean['fee'] = $method['fee'];
            if(isset($method['description']) && strlen($method['description']) > 1) $clean['description'] = $method['description'];
            if(isset($method['countries']) && strlen($method['countries']) > 1) $clean['countries'] = $method['countries'];
            if(isset($method['label']) && strlen($method['label']) > 1) $clean['label'] = $method['label'];
            $formatted[] = $clean;
        }

        return $formatted;
    }

    /**
     * Returns a Order Create Request Body
     *
     * @return array
     */
    public function generateCreateOrderRequestBody(): array
    {
        $body = [
            'purchase_country' => $this->purchase_country,
            'purchase_currency' => $this->purchase_currency,
            'locale' => $this->locale,
            'order_amount' => $this->order_amount,
            'order_tax_amount' => $this->order_lines[0],
            'order_lines' => $this->order_lines[1],
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'merchant_reference1' => $this->merchant_reference1,
            'merchant_reference2' => $this->merchant_reference2,
            'options' => $this->options,
            'merchant_urls' => $this->merchant_urls
        ];
        if(is_array($this->external_payment_methods) && !empty($this->external_payment_methods)) $body['external_payment_methods'] = $this->external_payment_methods;
        if(is_array($this->external_checkouts) && !empty($this->external_checkouts)) $body['external_checkouts'] = $this->external_checkouts;
        return $body;
    }
}