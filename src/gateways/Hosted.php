<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\elements\Asset;
use ellera\commerce\klarna\models\forms\HostedForm;
use craft\fields\data\MultiOptionsFieldData;
use yii\web\BadRequestHttpException;
use craft\helpers\UrlHelper;

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
     * Setting: Status Get Page
     *
     * @var string
     */
    public $status_page = 'shop/status';

    /**
     * Makes an authorize request.
     *
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
        /** @var $form HostedForm */
        if(!$form instanceof HostedForm) throw new BadRequestHttpException('Klarna HPP authorize only accepts HostedForm');

        if(
            isset($transaction->order->lastTransaction) &&
            strlen($transaction->order->lastTransaction->reference) > 10 &&
            $transaction->order->lastTransaction->type === 'authorize' &&
            $transaction->order->lastTransaction->status === 'redirect' &&
            $transaction->order->lastTransaction->gatewayId === $transaction->gatewayId
        ) {
            // This order is already created
            $transaction->note = 'Updated authorized Klarna Order';
            $transaction->parentId = $transaction->order->lastTransaction->message === 'Created' ? $transaction->order->lastTransaction->parentId : $transaction->order->lastTransaction->id;
            $form->populate($transaction, $this);
            $response = $form->updateOrder();
            if(!$response->getRedirectUrl() || empty($response->getRedirectUrl())) {
                $response = $form->createOrder();
                if($response->getRawResponse()->getStatusCode() >= 200 && $response->getRawResponse()->getStatusCode() < 300) $this->log('(Re)Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
            }
            else $this->log('Updated authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
        }
        else {
            $transaction->note = 'Created Klarna Order and HPP Session';
            $form->populate($transaction, $this);
            $response = $form->createOrder();
            if($response->getRawResponse()->getStatusCode() >= 200 && $response->getRawResponse()->getStatusCode() < 300) $this->log('Authorized order '.$transaction->order->number.' ('.$transaction->order->id.')');
        }

        return $response;
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
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->authorize($transaction, $form);
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
            'background_id' => 'Background Image',
            'back' => 'Go back URL'
        ]);
    }

    public function getLogoUrl()
    {
        $logo = $this->getLogoAsset();
        if(!$logo) return false;
        $url = UrlHelper::baseUrl().$logo->url;
        $parsed = parse_url($url, PHP_URL_SCHEME);

        if($parsed !== 'https')
        {
            $this->log("Klarna logo asset did not start with https ({$url})");
            return false;
        }
        if($logo instanceof Asset) return $url;
        return false;
    }

    public function getBackgroundUrl()
    {
        $background = $this->getBackgroundAsset();
        if(!$background) return false;

        if($background instanceof Asset) {
            // Background URLs does not work unless its on HTTPS
            $url = UrlHelper::baseUrl().$background->url;
            $parsed = parse_url($url, PHP_URL_SCHEME);

            if($parsed !== 'https')
            {
                $this->log("Klarna background asset did not start with https ({$url})");
                return false;
            }
            /*
            $small = [
                'mode' => 'fit',
                'name' => 'klarna_hpp_small',
                'width' => 400
            ];
            $medium = [
                'mode' => 'fit',
                'name' => 'klarna_hpp_small',
                'width' => 900
            ];
            $large = [
                'mode' => 'fit',
                'name' => 'klarna_hpp_small',
                'width' => 1600
            ];
            return [
                [
                    'url' => UrlHelper::baseUrl().$background->getUrl($small, true),
                    'width' => $background->getWidth($small)
                ],
                [
                    'url' => UrlHelper::baseUrl().$background->getUrl($medium, true),
                    'width' => $background->getWidth($medium)
                ],
                [
                    'url' => UrlHelper::baseUrl().$background->getUrl($large, true),
                    'width' => $background->getWidth($large)
                ]
            ];
            */
            return [
                [
                    'url' => UrlHelper::baseUrl().$background->url,
                    'width' => $background->getWidth()
                ]
            ];
        }
        $class = get_class($background);
        $this->log("Klarna asset was not an instance of \\Craft\\Elements\\Asset ({$class})");
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
                    'cancel',
                    'error',
                    'failure',
                    'privacy',
                    'status_page'
                ],
                'string'
            ],
        ];
        return array_merge(parent::rules(), $rules);
    }
}