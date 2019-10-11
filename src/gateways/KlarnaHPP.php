<?php

namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\models\KlarnaHPPResponse;
use ellera\commerce\klarna\models\KlarnaPaymentForm;
use Klarna\Rest\HostedPaymentPage\Sessions as HPPSession;
use Klarna\Rest\Payments\Sessions;
use Klarna\Rest\Transport\GuzzleConnector;
use yii\web\BadRequestHttpException;

/**
 * KlarnaCheckout represents the KCOv3 Hosted Payment Page gateway
 *
 * @author    Ellera AS, <support@ellera.no>
 * @since     1.2
 */
class KlarnaHPP extends BaseGateway
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var null|string
     */
    public $gateway_handle = 'klarna-hpp';

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
	public $title = 'Klarna Hosted Payment Page';

	/**
	 * Setting: Description
	 *
	 * @var string
	 */
	public $description = '';

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
	 * @inheritdoc
	 */
	public static function displayName(): string
	{
		return Craft::t('commerce', 'Klarna Hosted Payment Page');
	}

    /**
     * @param Transaction $transaction
     * @param BasePaymentForm $form
     * @return RequestResponseInterface
     * @throws BadRequestHttpException
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
	{
        /** @var $form KlarnaPaymentForm */
        if(!$form instanceof KlarnaPaymentForm) throw new BadRequestHttpException('Klarna authorize only accepts KlarnaPaymentForm');
        $form->populate($transaction, $this);

        $connector = GuzzleConnector::create(
            $this->getClientId(),
            $this->getClientSecret(),
            $this->getEndpoint()
        );

        try {
            $session = new Sessions($connector);
            $session->create($form->getSessionRequestBody());
        } catch (\Exception $e) {
            $this->log($e->getCode() . ': ' . ($e instanceof \GuzzleHttp\Exception\ClientException ? $e->getResponse()->getBody()->getContents() : $e->getMessage()));
            throw new \Exception('Session Error from Klarna. See log for more info');
        }

        try {
            $transaction->note = 'Created Klarna HPP';
            $transaction->order->returnUrl = $transaction->gateway->push.'?number='.$transaction->order->number;
            $transaction->order->cancelUrl = $transaction->gateway->checkout;

            $hpp = new HPPSession($connector);
            return new KlarnaHPPResponse($transaction, $form, $session, $hpp);
        } catch (\Exception $e) {
            $this->log($e->getCode() . ': ' . ($e instanceof \GuzzleHttp\Exception\ClientException ? $e->getResponse()->getBody()->getContents() : $e->getMessage()));
            throw new \Exception('HPP Error from Klarna. See log for more info');
        }
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

		$response = $this->getKlarnaOrderResponse('POST', "/ordermanagement/v1/orders/{$transaction->reference}/captures", $body);
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
