<?php


namespace ellera\commerce\klarna\models;

use craft\commerce\elements\Order;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\gateways\KlarnaCheckout;
use craft\commerce\models\LineItem;
use yii\base\InvalidConfigException;

class KlarnaPaymentForm extends BasePaymentForm
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
	 * Value Added Tax of order in $purchase_currency
	 * fractional denomination
	 *
	 * @var int
	 */
	public $order_tax_amount;

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
	 * @var KlarnaOrderLine[]
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
	 * Merchant URLs
	 * Redirect URLs passed to Klarna
	 *
	 * @var array
	 */
	public $merchant_urls;

	/**
	 * @param Transaction    $transaction
	 * @param KlarnaCheckout $gateway
	 *
	 * @throws InvalidConfigException
	 * @throws \craft\errors\SiteNotFoundException
	 */
	public function populate(Transaction $transaction, KlarnaCheckout $gateway) : void
	{
		$commerce = \craft\commerce\Plugin::getInstance();
		$country = $commerce->getAddresses()->getStoreLocationAddress()->getCountry();
		if($country->iso == null) throw new InvalidConfigException('Klarna requires Store Location Country to be set. Please visit Commerce -> Settings -> Store Location and update the information.');

		/** @var $item LineItem */

		$order_lines = $this->getOrderLines($transaction->order, $gateway);
		$this->purchase_country = $country->iso;
		$this->purchase_currency = $transaction->order->currency;
		$this->locale = $transaction->order->orderLanguage;
		$this->order_amount = $transaction->order->getTotalPrice()*100;
		$this->billing_address = $gateway->formatAddress($transaction->order->billingAddress, $transaction->order->email);
		$this->shipping_address = $gateway->formatAddress($transaction->order->shippingAddress, $transaction->order->email);
		$this->order_lines = $order_lines;
		$this->merchant_reference1 = $transaction->order->shortNumber;
		$this->merchant_reference2 = $transaction->order->number;
		$this->options = [
			'date_of_birth_mandatory' => $gateway->mandatory_date_of_birth == '1',
			'national_identification_number_mandatory' => $gateway->mandatory_national_identification_number == '1',
			'title_mandatory' => $gateway->api_eu_title_mandatory == '1',
			'show_subtotal_detail' => true
		];
		$this->merchant_urls = [
			'terms' => \craft\helpers\UrlHelper::baseUrl().$transaction->order->gateway->terms,
			'confirmation' => \craft\helpers\UrlHelper::baseUrl().'actions/commerce-klarna-checkout/klarna/confirmation?hash='.$transaction->hash,
			'checkout' => \craft\helpers\UrlHelper::baseUrl().$transaction->order->gateway->checkout,
			'push' => \craft\helpers\UrlHelper::baseUrl().$transaction->order->gateway->push.'?number='.$transaction->order->number
		];
	}

	private function getOrderLines(Order $order, KlarnaCheckout $gateway)
	{
		$total_tax = 0;
		$order_lines = [];
		foreach ($order->lineItems as $line) {
			$order_line = new KlarnaOrderLine();
			$order_line->populate($line);

			if($gateway->send_product_urls == '1') {
				$order_line->product_url = $line->purchasable->getUrl();
			}

			$order_lines[] = $order_line;
			$total_tax += $order_line->getLineTax();
		}
		$shipping_method = $order->shippingMethod;
		if($shipping_method->getPriceForOrder($order) > 0) {

			$order_line = new KlarnaOrderLine();
			$order_line->shipping($shipping_method, $order);
			$total_tax += $order_line->getLineTax();

			$order_lines[] = $order_line;
		}
		$this->order_tax_amount = $total_tax;
		return $order_lines;
	}

	public function getRequestBody() : array
	{
		$body = [
			'purchase_country' => $this->purchase_country,
			'purchase_currency' => $this->purchase_currency,
			'locale' => $this->locale,
			'order_amount' => $this->order_amount,
			'order_tax_amount' => $this->order_tax_amount,
			'billing_address' => $this->billing_address,
			'shipping_address' => $this->shipping_address,
			'order_lines' => [],
			'merchant_reference1' => $this->merchant_reference1,
			'merchant_reference2' => $this->merchant_reference2,
			'options' => $this->options,
			'merchant_urls' => $this->merchant_urls
		];
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
}