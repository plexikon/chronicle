<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionModel
{
    public const TABLE = 'projections';

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string
     */
    public function position(): string;

    /**
     * @return string
     */
    public function state(): string;

    /**
     * @return string
     */
    public function status(): string;

    /**
     * @return string|null
     */
    public function lockedUntil(): ?string;
}
