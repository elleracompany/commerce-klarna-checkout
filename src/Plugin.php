<?php

namespace ellera\commerce\klarna;

use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use ellera\commerce\klarna\gateways\Checkout;
use ellera\commerce\klarna\gateways\Hosted;
use ellera\commerce\klarna\gateways\KlarnaCheckout;
use yii\base\Event;


/**
 * Plugin represents the Klarna integration plugin.
 *
 * @author Ellera AS <support@ellera.no>
 * @since  1.0
 */
class Plugin extends \craft\base\Plugin
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES,  function(RegisterComponentTypesEvent $event) {
            $event->types[] = Checkout::class;
            $event->types[] = Hosted::class;
            $event->types[] = KlarnaCheckout::class;
        });
    }
}
