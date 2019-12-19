<?php

namespace ellera\commerce\klarna\controllers;

use Craft;
use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\Plugin;
use ellera\commerce\klarna\gateways\BaseGateway;
use ellera\commerce\klarna\gateways\Checkout;
use ellera\commerce\klarna\gateways\KlarnaCheckout;
use ellera\commerce\klarna\gateways\KlarnaHPP;
use ellera\commerce\klarna\models\forms\BasePaymentForm;
use ellera\commerce\klarna\models\KlarnaBasePaymentForm;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class Checkout Controller
 *
 * @author Ellera AS <support@ellera.no>
 * @since 1.0
 */
class KlarnaController extends BaseFrontEndController
{
	protected $allowAnonymous = true;

    /**
     * @param $hash
     * @param null|string $hppId
     *
     * @return \yii\web\Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\commerce\errors\TransactionException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function actionConfirmation($hash, $hppId = null)
	{
		$plugin = Plugin::getInstance();

		$request = Craft::$app->getRequest();
		$session = Craft::$app->getSession();

		$last_transaction = $plugin->getTransactions()->getTransactionByHash($hash);

		/** @var $gateway BaseGateway */
		$gateway = $plugin->getGateways()->getGatewayById($last_transaction->gatewayId);

		if (!$last_transaction || !$gateway) {
			$error = Craft::t('commerce', 'Can not find an order to pay.');

			if ($request->getAcceptsJson()) {
				return $this->asErrorJson($error);
			}

			$session->setError($error);

			return null;
		}

		$klarna_order_id = $last_transaction->reference;

		// Check if Klarna Order ID looks valid
		if(strlen($klarna_order_id) <20) throw new BadRequestHttpException('Something went wrong with our connection to Klarna');

		$order = $last_transaction->order;

		Craft::$app->session->set('klarna_order_id', $klarna_order_id);

		if($gateway instanceof Checkout)
        {
            /** @var $gateway Checkout */
            $gateway->updateOrder($order);
        }
		elseif($gateway instanceof KlarnaHPP && $hppId)
        {
            /** @var $gateway KlarnaHPP */
            // TODO: Create an order with https://developers.klarna.com/api/#payments-api-create-a-new-order
            $gateway->updateOrder($order);
        }
		else throw new NotFoundHttpException();


		if(isset($gateway->paymentTypeOptions[$gateway->paymentType])) $paymentType = $gateway->paymentType;
		else $paymentType = 'authorize';

		$transaction = $plugin->getTransactions()->createTransaction($order, $last_transaction, $paymentType);
		$transaction->reference = $last_transaction->reference;
		$transaction->paymentAmount = $last_transaction->paymentAmount;

		if($paymentType == 'purchase') {

			// Get the gateway's payment form
			$paymentForm = $gateway->getPaymentFormModel();

			if(!$paymentForm instanceof BasePaymentForm) throw new BadRequestHttpException('Klarna authorize only accepts KlarnaPaymentForm');
			$paymentForm->populate($transaction, $gateway);
			$gateway->purchase($transaction, $paymentForm);
		}
		else {
			// Acknowledge the order
			$response = $gateway->acknowledgeOrder($klarna_order_id);

			// Create Acknowledge Transaction
			$transaction->status = 'success';
			$transaction->code = $response->getCode();
			$transaction->message = $response->getMessage();
			$transaction->note = 'Order Acknowledged';
			if(!$plugin->getTransactions()->saveTransaction($transaction)) throw new BadRequestHttpException('Could not save acknowledge transaction');

			// Check status code
			if($response->getCode() !== '204') throw new BadRequestHttpException('Could not acknowledge order');
		}

		return $this->redirect('/'.$transaction->gateway->push.'?number='.$transaction->order->number);
	}
}