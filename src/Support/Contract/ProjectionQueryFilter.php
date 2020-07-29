<?php

namespace Plexikon\Chronicle\Support\Contract;

use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    public function setCurrentPosition(int $position): void;
}
