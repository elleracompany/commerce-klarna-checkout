<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use ellera\commerce\klarna\klarna\order\Create;
use ellera\commerce\klarna\models\Order;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\models\forms\CheckoutForm;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

/**
 * Class Checkout
 *
 * Checkout gateway for Klarna
 * https://developers.klarna.com/documentation/klarna-checkout/
 *
 * @package ellera\commerce\klarna\gateways
 */
class Checkout extends Base
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var string
     */
    public $gateway_handle = 'klarna-checkout';

    /**
     * Setting: Title
     *
     * @var string
     */
    public $title = 'Klarna Checkout';

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
        return Craft::t('commerce', 'Klarna Checkout');
    }

    /**
     * @param Transaction $transaction
     * @param BasePaymentForm $form
     * @return RequestResponseInterface
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\ErrorException
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // Check if the received form is of the right type
        if(!$form instanceof CheckoutForm)
            throw new BadRequestHttpException('Klarna Checkout only accepts CheckoutForm');

        if(
            isset($transaction->order->lastTransaction) &&
            strlen($transaction->order->lastTransaction->reference) > 10 &&
            $transaction->order->lastTransaction->type === 'authorize' &&
            $transaction->order->lastTransaction->status === 'pending' &&
            $transaction->order->lastTransaction->gatewayId === $transaction->gatewayId
        ) {
            // This order is already created
            $transaction->note = 'Updated authorized Klarna Order';
            $transaction->parentId = $transaction->order->lastTransaction->message === 'OK' ? $transaction->order->lastTransaction->parentId : $transaction->order->lastTransaction->id;
            $form->populate($transaction, $this);
            $response = $form->updateOrder();

            if($response->isSuccessful()) $this->log('Updated authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
        }
        else {
            $transaction->note = 'Created Klarna Order';
            $form->populate($transaction, $this);
            $response = $form->createOrder();

            if($response->isSuccessful()) $this->log('Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
        }

        return $response;
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
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new CheckoutForm();
    }

    /**
     * @return Order
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    private function createCheckoutOrder() : Order
    {
        $commerce = craft\commerce\Plugin::getInstance();
        $cart = $commerce->getCarts()->getCart();

        $transaction = $commerce->getTransactions()->createTransaction($cart, null, 'authorize');

        $form = new CheckoutForm();
        $form->populate($transaction, $this);

        /** @var $response Create */
        $response = $this->authorize($transaction, $form);
        $transaction->reference = $response->getData()->order_id;
        $transaction->code = $response->getCode();
        $transaction->message = $response->getMessage();
        $commerce->getTransactions()->saveTransaction($transaction);

        if(!$response->isSuccessful()) $this->log('Failed to create order '.$transaction->order->id.'. Klarna responded with '.$response->getCode().': '.$response->getMessage());

        return new Order($response);
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
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
    public function supportsPartialRefund(): bool
    {
        return true;
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
    public function supportsRefund(): bool
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
    public function supportsPaymentSources(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
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
     * Settings validation rules
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            [
                [
                    'push'
                ],
                'string'
            ],
        ];
        return array_merge(parent::rules(), $rules);
    }
}