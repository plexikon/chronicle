<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface ReadModelProjector extends PersistentProjector
{
    /**
     * @return ReadModel
     */
    public function readModel(): ReadModel;
}
