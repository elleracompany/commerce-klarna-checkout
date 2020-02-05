<?php

namespace ellera\commerce\klarna\gateways;

use Craft;

/**
 * Class KlarnaCheckout
 *
 * @deprecated as of v2.0
 *
 * @since v1.0
 * @package ellera\commerce\klarna\gateways
 */
class KlarnaCheckout extends Checkout
{
    /**
     * Gateway handle
     *
     * @var string
     */
    public $gateway_handle = 'klarna-checkout-deprecated';

    /**
     * Setting: Title
     *
     * @var string
     */
    public $title = 'Klarna Checkout (Deprecated)';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Klarna Checkout (Deprecated)');
    }
}