<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    public function setCurrentPosition(int $position): void;
}
