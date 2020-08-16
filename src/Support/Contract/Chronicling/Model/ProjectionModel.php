<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionModel
{
    public const TABLE = 'projections';

    /**
     * Query projection name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Query projection position
     *
     * @return string
     */
    public function position(): string;

    /**
     * Query projection state
     *
     * @return string
     */
    public function state(): string;

    /**
     * Query projection status
     *
     * @return string
     */
    public function status(): string;

    /**
     * Query projection time lock
     *
     * @return string|null
     */
    public function lockedUntil(): ?string;
}
