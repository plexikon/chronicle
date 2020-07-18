<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

interface QueryFilter
{
    /**
     * @return callable
     */
    public function filterQuery(): callable;
}
