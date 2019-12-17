<?php


namespace ellera\commerce\klarna\gateways;

use ellera\commerce\klarna\gateways\Base;

/**
 * Class Payments
 *
 * Payments gateway for Klarna
 * https://developers.klarna.com/documentation/hpp/
 *
 * @package ellera\commerce\klarna\gateways
 */
class Payments extends Base
{
    // Public Variables
    // =========================================================================

    /**
     * Gateway handle
     *
     * @var string
     */
    public $gateway_handle = 'klarna-payments';
}