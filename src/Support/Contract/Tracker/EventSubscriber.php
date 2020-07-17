<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;

interface EventSubscriber extends Subscriber
{
    /**
     * @param Chronicler $chronicle
     */
    public function attachToChronicler(Chronicler $chronicle): void;
}
