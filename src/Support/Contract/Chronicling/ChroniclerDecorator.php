<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

interface ChroniclerDecorator extends Chronicler
{
    /**
     * @return Chronicler
     */
    public function innerChronicler(): Chronicler;
}
