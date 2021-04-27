<?php

namespace ellera\commerce\klarna\events;

use craft\commerce\models\Address;
use yii\base\Event;

class FormatAddressEvent extends Event
{
    /**
     * @var array
     */
    public $address;

    /**
     * @var Address
     */
    public $sourceAddress;
}