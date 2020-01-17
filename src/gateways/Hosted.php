<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\elements\Asset;
use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\models\forms\HostedForm;
use craft\fields\data\MultiOptionsFieldData;
use ellera\commerce\klarna\models\forms\HostedFrom;
use Klarna\Rest\HostedPaymentPage\Sessions as HPPSession;
use Klarna\Rest\Payments\Sessions;
use Klarna\Rest\Transport\GuzzleConnector;
use yii\web\BadRequestHttpException;

/**
 * Class Hosted
 *
 * Hosted Payment Page gateway for Klarna
 * https://developers.klarna.com/documentation/klarna-payments/
 *
 * @package ellera\commerce\klarna\gateways
 * @author Ellera AS <support@ellera.no>
 * @since  2.0
 */
class Hosted extends Base
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var string
     */
    public $gateway_handle = 'klarna-hpp';

    /**
     * Setting: Title
     *
     * @var string
     */
    public $title = 'Klarna Hosted Payment Page';

    /**
     * Setting: Payment Methods
     *
     * @var MultiOptionsFieldData
     */
    public $methods;

    /**
     * Setting: Available Payment Methods
     *
     * @var array
     */
    public $available_methods = [
        'DIRECT_DEBIT' => 'Direct Debit',
        'DIRECT_BANK_TRANSFER' => 'Direct Bank Transfer',
        'PAY_NOW' => 'Pay Now',
        'PAY_LATER' => 'Pay Later',
        'PAY_OVER_TIME' => 'Pay over time',
    ];

    /**
     * Setting: Back Page
     *
     * @var string
     */
    public $back = 'shop';

    /**
     * Setting: Logo Asset ID
     *
     * @var array
     */
    public $logo_id = null;

    /**
     * Setting: Logo Asset
     *
     * @var Asset|null
     */
    public $logo_asset = null;

    /**
     * Setting: Background Asset ID
     *
     * @var array
     */
    public $background_id = null;

    /**
     * Setting: Background Asset
     *
     * @var Asset|null
     */
    public $background_asset = null;

    /**
     * Setting: Cancel Page
     *
     * @var string
     */
    public $cancel = 'shop/cancelled';

    /**
     * Setting: Error Page
     *
     * @var string
     */

    public $error = 'shop/error';

    /**
     * Setting: Failure Page
     *
     * @var string
     */
    public $failure = 'shop/failure';

    /**
     * Setting: Privacy policy Page
     *
     * @var string
     */
    public $privacy = 'shop/privacy';

    /**
     * Setting: Status Update Page
     *
     * @var string
     */
    public $status = 'shop/status';

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
        /** @var $form HostedFrom */
        if(!$form instanceof HostedFrom) throw new BadRequestHttpException('Klarna HPP authorize only accepts HostedFrom');
        $form->populate($transaction, $this);

        // TODO: Continue here
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
            $transaction->order->returnUrl = $transaction->gateway->success.'?number='.$transaction->order->number;
            $transaction->order->cancelUrl = $transaction->gateway->cancel;

            $hpp = new HPPSession($connector);
            return new KlarnaHPPResponse($transaction, $form, $session, $hpp);
        } catch (\Exception $e) {
            $this->log($e->getCode() . ': ' . ($e instanceof \GuzzleHttp\Exception\ClientException ? $e->getResponse()->getBody()->getContents() : $e->getMessage()));
            throw new \Exception('HPP Error from Klarna. See log for more info');
        }
    }

    /**
     * Returns a list of the available payment methods
     * @return array
     */
    public function getAvailablePaymentMethods()
    {
        return $this->available_methods;
    }

    /**
     * Return the Logo Asset
     * @return Asset|null
     */
    public function getLogoAsset()
    {
        if (!$this->logo_asset && is_array($this->logo_id) && is_numeric($this->logo_id[0])) $this->logo_asset = Craft::$app->assets->getAssetById($this->logo_id[0]);
        return $this->logo_asset;
    }

    /**
     * Return the Background Asset
     * @return Asset|null
     */
    public function getBackgroundAsset()
    {
        if (!$this->background_asset && is_array($this->background_id) && is_numeric($this->background_id[0])) $this->background_asset = Craft::$app->assets->getAssetById($this->background_id[0]);
        return $this->background_asset;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Klarna Hosted Payment Page');
    }

    /**
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new HostedForm();
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
     * Settings Attribute Labels
     *
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'logo_id' => 'Logo Image',
            'background_id' => 'Background Image'
        ]);
    }
}