<?php

namespace ellera\commerce\klarna\models\forms;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Address;
use craft\commerce\models\Country;
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
     * @param Transaction $transaction
     * @param Base $gateway
     * @throws InvalidConfigException
     */
    public function populate(Transaction $transaction, Base $gateway)
    {
        $this->transaction = $transaction;
        $this->gateway = $gateway;
        $this->commerce = Commerce::getInstance();

        $country = $this->commerce->getAddresses()->getStoreLocationAddress()->getCountry();
        if(!isset($country->iso) || $country->iso == null) throw new InvalidConfigException('Klarna requires Store Location Country to be set. Please visit Commerce -> Store Settings -> Store Location and update the information.');

        if(Craft::$app->plugins->getPlugin('commerce')->is(Commerce::EDITION_LITE)) $order_lines = $gateway->getOrderLinesLite($transaction->order, $gateway);
        else $order_lines = $gateway->getOrderLines($transaction->order, $gateway);

        $this->purchase_country = $country->iso;
        $this->purchase_currency = $transaction->order->paymentCurrency;
        $this->locale = $transaction->order->orderLanguage;
        $this->order_amount = $transaction->order->getTotalPrice()*100;
        $this->billing_address = $transaction->order->billingAddress instanceof Address ? $gateway->formatAddress($transaction->order->billingAddress, $transaction->order->email) : null;
        $this->shipping_address = $transaction->order->shippingAddress instanceof Address ? $gateway->formatAddress($transaction->order->shippingAddress, $transaction->order->email) : null;
        $this->order_lines = $order_lines;
        $this->merchant_reference1 = $transaction->order->shortNumber;
        $this->merchant_reference2 = $transaction->order->number;
        $this->options = [
            'date_of_birth_mandatory' => $gateway->mandatory_date_of_birth == '1',
            'national_identification_number_mandatory' => $gateway->mandatory_national_identification_number == '1',
            'allow_separate_shipping_address' => true,
            'title_mandatory' => $gateway->api_eu_title_mandatory == '1',
            'show_subtotal_detail' => true
        ];
    }
	/**
	 * Returns the full store URL
	 *
	 * @return string
	 * @throws \craft\errors\SiteNotFoundException
	 */
	public function getStoreUrl()
	{
		$siteUrl = UrlHelper::baseUrl();
		if(!UrlHelper::isAbsoluteUrl($siteUrl))
		{
			$myUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && !in_array(strtolower($_SERVER['HTTPS']),['off','no'])) ? 'https' : 'http';
			$myUrl .= '://'.$_SERVER['HTTP_HOST'];
			$siteUrl = $myUrl.$siteUrl;
		}

		return $siteUrl;
	}

    /**
     * Returns a Order Create Request Body
     *
     * @return array
     */
    public function generateCreateOrderRequestBody(): array
    {
        return [
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
    }
}