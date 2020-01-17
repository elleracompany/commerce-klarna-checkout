<?php


namespace ellera\commerce\klarna\gateways;

use ellera\commerce\klarna\gateways\Base;

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
}