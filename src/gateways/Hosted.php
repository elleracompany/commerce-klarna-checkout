<?php


namespace ellera\commerce\klarna\gateways;

use Craft;
use craft\commerce\models\payments\BasePaymentForm;
use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\models\forms\HostedFrom;
use craft\fields\data\MultiOptionsFieldData;

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
     * Returns a list of the available payment methods
     * @return array
     */
    public function getAvailablePaymentMethods()
    {
        return $this->available_methods;
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
        return new HostedFrom();
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
}