<?php

namespace ellera\commerce\klarna\models;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Country;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\gateways\BaseGateway;
use ellera\commerce\klarna\gateways\KlarnaCheckout;
use ellera\commerce\klarna\gateways\KlarnaHPP;
use yii\base\InvalidConfigException;
use craft\helpers\UrlHelper;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\Address;

class KlarnaBasePaymentForm extends BasePaymentForm
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
	 * Request Body
	 *
	 * @var array
	 */
	public $request_body;

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
	 * @param Transaction    $transaction
	 * @param BaseGateway $gateway
	 *
	 * @throws InvalidConfigException
	 */
	public function populate(Transaction $transaction, BaseGateway $gateway) : void
	{
		$this->commerce = Commerce::getInstance();
		$country = $this->commerce->getAddresses()->getStoreLocationAddress()->getCountry();
		if(!isset($country->iso) || $country->iso == null) throw new InvalidConfigException('Klarna requires Store Location Country to be set. Please visit Commerce -> Store Settings -> Store Location and update the information.');

		if(Craft::$app->plugins->getPlugin('commerce')->is(Commerce::EDITION_LITE)) $order_lines = $this->getOrderLinesLite($transaction->order, $gateway);
		else $order_lines = $this->getOrderLines($transaction->order, $gateway);

		$this->purchase_country = $country->iso;
		$this->purchase_currency = $transaction->order->currency;
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

	private function getOrderLines(Order $order, BaseGateway $gateway)
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
		if($shipping_method && $shipping_method->getPriceForOrder($order) > 0) {

			$order_line = new KlarnaOrderLine();
			$order_line->shipping($shipping_method, $order);
			$total_tax += $order_line->getLineTax();

			$order_lines[] = $order_line;
		}
		$this->order_tax_amount = $total_tax;
		return $order_lines;
	}

	private function getOrderLinesLite(Order $order, KlarnaCheckout $gateway)
	{
		$line_tax = 0;
		$tax_included = false;
		$shipping = 0;
		$order_lines = [];
		$order_tax_amount = 0;

		foreach ($order->getAdjustments() as $adjustment)
		{
			if($adjustment->type == 'tax')
			{
				$order_tax_amount += $adjustment->amount;
				$tax_included = $adjustment->included == 1;
			}
			elseif($adjustment->type == 'shipping')
			{
				$shipping+= $adjustment->amount;
			}
		}
		if($shipping > 0)
		{
			if($tax_included) {
				$shipping_tax = ($shipping/$order->totalPrice)*$order_tax_amount;
			}
			else {
				$shipping_tax = ($shipping/($order->totalPrice-$order_tax_amount))*$order_tax_amount;
			}
			$order_line = new KlarnaOrderLine();

			$order_line->name = 'Shipping';
			$order_line->quantity = 1;
			$order_line->unit_price = $tax_included ? (int)($shipping*100) : (int)(($shipping+$shipping_tax)*100);
			$order_line->tax_rate = $tax_included ? round(($shipping_tax/($shipping-$shipping_tax))*10000) : round(($shipping_tax/$shipping)*10000);
			$order_line->total_amount = $tax_included ? (int)($shipping*100*$order_line->quantity) : (int)(($shipping+$shipping_tax)*100*$order_line->quantity);
			$order_line->total_tax_amount = (int)($shipping_tax*100);

			$order_lines[] = $order_line;
		}
		else $shipping_tax = 0;

		foreach ($order->lineItems as $line) {
			$line_tax = $order_tax_amount-$shipping_tax;
			$order_line = new KlarnaOrderLine();

			$order_line->name = $line->purchasable->title;
			$order_line->quantity = $line->qty;
			$order_line->unit_price = $tax_included ? (int)(($line->price)*100) : (int)(($line->price+($line_tax/$line->qty))*100);
			$order_line->tax_rate = $tax_included ? round((($line_tax/$line->qty)/($line->price-($line_tax/$line->qty)))*10000) : round((($line_tax/$line->qty)/$line->price)*10000);
			$order_line->total_amount = $tax_included ? (int)(($line->price)*100*$line->qty) : (int)((($line->price*$line->qty)+$line_tax)*100);
			$order_line->total_tax_amount = (int)($line_tax*100);

			if($gateway->send_product_urls == '1') {
				$order_line->product_url = $line->purchasable->getUrl();
			}

			$order_lines[] = $order_line;
		}
		$this->order_tax_amount = $order_tax_amount*100;
		return $order_lines;
	}

	public function getOrderRequestBody() : array
	{
        return $this->request_body;
	}
}