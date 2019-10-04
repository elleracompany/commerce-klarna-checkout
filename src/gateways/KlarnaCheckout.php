<?php

namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\models\Address;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\models\KlarnaOrder;
use ellera\commerce\klarna\models\KlarnaPaymentForm;
use ellera\commerce\klarna\models\KlarnaResponse;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

/**
 * KlarnaCheckout represents the KCOv3 checkout gateway
 *
 * @author    Ellera AS, <support@ellera.no>
 * @since     1.0
 */
class KlarnaCheckout extends BaseGateway
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var null|string
     */
    public $gateway_handle = 'klarna-checkout';

    /**
     * Setting: Logging
     *
     * @var bool
     */
    public $log_debug_messages = true;

	/**
	 * Setting: Title
	 *
	 * @var string
	 */
	public $title = 'Klarna Checkout';

	/**
	 * Setting: Description
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Setting: Send Product Urls
	 *
	 * @var string
	 */
	public $send_product_urls = true;

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
	 * Setting: Mandatory DOB
	 *
	 * @var string
	 */
	public $mandatory_national_identification_number = false;

	/**
	 * Setting: API User (Prod, EU)
	 *
	 * @var string
	 */
	public $api_eu_uid = '';

	/**
	 * Setting: API Password (Prod, EU)
	 *
	 * @var string
	 */
	public $api_eu_password = '';

	/**
	 * Setting:  API User (Test, EU)
	 *
	 * @var string
	 */
	public $api_eu_test_uid = '';

	/**
	 * Setting: API Password (Test, EU)
	 *
	 * @var string
	 */
	public $api_eu_test_password = '';

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
	 * Setting: API User (Prod, US)
	 *
	 * @var string
	 */
	public $api_us_uid = '';

	/**
	 * Setting: API Password (Prod, US)
	 *
	 * @var string
	 */
	public $api_us_password = '';

	/**
	 * Setting: API User (Test, US)
	 *
	 * @var string
	 */
	public $api_us_test_uid = '';

	/**
	 * Setting: API Password (Test, US)
	 *
	 * @var string
	 */
	public $api_us_test_password = '';

	/**
	 * Production API URL
	 * @var string
	 */
	private $prod_url = 'https://api.klarna.com';

	/**
	 * Test API URL
	 * @var string
	 */
	private $test_url = 'https://api.playground.klarna.com';

	/**
	 * Setting: Payment Type
	 *
	 * @var string [authorize, purchase]
	 */
	public $paymentType = 'authorize';

	/**
	 * Setting: Checkout Page
	 *
	 * @var string
	 */
	public $checkout = 'shop/checkout';

	/**
	 * Setting: Order Complete Page
	 *
	 * @var string
	 */
	public $push = 'shop/customer/order';

	/**
	 * Setting: Terms Page
	 *
	 * @var string
	 */
	public $terms = 'shop/terms';

	/**
	 * @inheritdoc
	 */
	public static function displayName(): string
	{
		return Craft::t('commerce', 'Klarna Checkout');
	}

	/**
	 * @param Transaction     $transaction
	 * @param BasePaymentForm $form
	 *
	 * @return RequestResponseInterface
	 * @throws BadRequestHttpException
	 * @throws InvalidConfigException
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \yii\base\ErrorException
	 */
	public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
	{
		if(!$form instanceof KlarnaPaymentForm) throw new BadRequestHttpException('Klarna authorize only accepts KlarnaPaymentForm');
		//die(json_encode($form->getRequestBody()));
		/** @var KlarnaResponse $response */
		try {
			$response = $this->getKlarnaResponse('POST', '/checkout/v3/orders', $form->getRequestBody());
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->log($e->getCode() . ': ' . $e->getMessage());
			throw new InvalidConfigException('Klarna is expecting other values, make sure you\'ve added taxes as described in the documentation for the Klarna Checkout Plugin, and that you\'ve correctly set the Site Base URL. Klarna Response: '.$e->getMessage());
		}
		$order = new KlarnaOrder($response);

		$transaction->note = 'Created Klarna Order';
		$transaction->response = $response->get();
		$transaction->order->returnUrl = $transaction->gateway->push.'?number='.$transaction->order->number;
		$transaction->order->cancelUrl = $transaction->gateway->checkout;

		$order->getOrderId() ? $transaction->status = 'redirect' : $transaction->status = 'failed';

		if($response->isSuccessful()) $this->log('Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
		else $this->log('Failed to Authorize order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

		return $response;
	}

	/**
	 * @param Transaction $transaction
	 * @param string      $reference
	 *
	 * @return RequestResponseInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \yii\base\ErrorException
	 */
	public function capture(Transaction $transaction, string $reference): RequestResponseInterface
	{
		$body = [
			'captured_amount' => (int)$transaction->paymentAmount * 100,
			'description' => $transaction->hash
		];

		$response = $this->getKlarnaResponse('POST', "/ordermanagement/v1/orders/{$transaction->reference}/captures", $body);
		$response->setTransactionReference($reference);
		if($response->isSuccessful()) $this->log('Captured order '.$transaction->order->number.' ('.$transaction->order->id.')');
		else $this->log('Failed to capture order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

		return $response;
	}

	/**
	 * @inheritdoc
	 */
	public function deletePaymentSource($token): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getPaymentFormModel(): BasePaymentForm
	{
		return new KlarnaPaymentForm();
	}

	/**
	 * @param Transaction     $transaction
	 * @param BasePaymentForm $form
	 *
	 * @return RequestResponseInterface
	 * @throws BadRequestHttpException
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \craft\commerce\errors\TransactionException
	 * @throws \yii\base\ErrorException
	 */
	public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
	{
		$response = $this->captureKlarnaOrder($transaction);
		$transaction->order->updateOrderPaidInformation();
		return $response;
	}

	/**
	 * @param Transaction $transaction
	 *
	 * @return RequestResponseInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \yii\base\ErrorException
	 */
	public function refund(Transaction $transaction): RequestResponseInterface
	{
		$amount = Craft::$app->request->getBodyParam('amount');
		$note = Craft::$app->request->getBodyParam('note');

		if($amount == '') $amount = $transaction->order->totalPaid;

		$response = $this->getKlarnaResponse('POST', "/ordermanagement/v1/orders/{$transaction->reference}/refunds", [
			'refunded_amount' => (int)$amount*100,
			'description' => $note
		]);
		$response->setTransactionReference($transaction->reference);
		if($response->isSuccessful()) $this->log('Refunded '.$amount.' from order '.$transaction->order->number.' ('.$transaction->order->id.')');
		else $this->log('Failed to refund order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

		return $response;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsAuthorize(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsCapture(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsCompleteAuthorize(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsCompletePurchase(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsPaymentSources(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsPurchase(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsRefund(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsPartialRefund(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function supportsWebhooks(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function availableForUseWithOrder(Order $order): bool
	{
		return parent::availableForUseWithOrder($order);
	}

	/**
	 * @param array $params
	 *
	 * @return null|string
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 */
	public function getPaymentFormHtml(array $params)
	{
		$order = $this->createCheckoutOrder();
		return $order->getHtmlSnippet();
	}

	/**
	 * @return KlarnaOrder
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 */
	private function createCheckoutOrder() : KlarnaOrder
	{
		$commerce = craft\commerce\Plugin::getInstance();
		$cart = $commerce->getCarts()->getCart();

		$transaction = $commerce->getTransactions()->createTransaction($cart, null, 'authorize');

		$form = new KlarnaPaymentForm();
		$form->populate($transaction, $this);

		/** @var $response KlarnaResponse */
		$response = $this->authorize($transaction, $form);
		$transaction->reference = $response->getTransactionReference();
		$transaction->code = $response->getCode();
		$transaction->message = $response->getMessage();
		$commerce->getTransactions()->saveTransaction($transaction);

		if($response->isSuccessful()) $this->log('Created order '.$transaction->order->number.' ('.$transaction->order->id.')');
		else $this->log('Failed to create order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

		return new KlarnaOrder($response);
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
	 * Settings validation rules
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			[['title'], 'required'],
			[
				[
					'title',
					'description',
					'api_eu_uid',
					'api_eu_password',
					'api_eu_test_uid',
					'api_eu_test_password',
					'api_us_uid',
					'api_us_password',
					'api_us_test_uid',
					'api_us_test_password',
					'checkout',
					'push',
					'terms'
				],
				'string'
			],
			[
				[
					'send_product_urls',
					'log_debug_messages',
					'test_mode',
					'mandatory_date_of_birth',
					'api_eu_title_mandatory',
					'api_eu_consent_notice',
					'mandatory_national_identification_number'
				],
				'boolean'
			]
		];
	}

	/**
	 * Settings Attribute Labels
	 *
	 * @return array
	 */
	public function attributeLabels()
	{
		return [
			'title' => 'Title',
			'description' => 'Description',
			'api_eu_uid' => 'Production Username (UID)',
			'api_eu_password' => 'Production Password',
			'api_eu_test_uid' => 'Test Username (UID)',
			'api_eu_test_password' => 'Test Password',
			'api_us_uid' => 'Production Username (UID)',
			'api_us_password' => 'Production Password',
			'api_us_test_uid' => 'Test Username (UID)',
			'api_us_test_password' => 'Test Password',
			'send_product_urls' => 'Send Product URLs',
			'log_debug_messages' => 'Logging',
			'test_mode' => 'Test Mode',
			'mandatory_date_of_birth' => 'Mandatory Date of Birth',
			'mandatory_national_identification_number' => 'Mandatory National Identification Number',
			'api_eu_title_mandatory' => 'Title mandatory (GB)',
			'api_eu_consent_notice' => 'Show prefill consent notice',
			'checkout' => 'Checkout Page',
			'push' => 'Order Complete Page',
			'terms' => 'Store Terms Page'
		];
	}
}
