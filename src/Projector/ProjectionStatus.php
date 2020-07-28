<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use MabeEnum\Enum;

/**
 * @method static ProjectionStatus RUNNING()
 * @method static ProjectionStatus STOPPING()
 * @method static ProjectionStatus DELETING()
 * @method static ProjectionStatus DELETING_EMITTED_EVENTS()
 * @method static ProjectionStatus RESETTING()
 * @method static ProjectionStatus IDLE()
 */
final class ProjectionStatus extends Enum
{
    public const RUNNING = 'running';
    public const STOPPING = 'stopping';
    public const DELETING = 'deleting';
    public const DELETING_EMITTED_EVENTS = 'deleting emitted events';
    public const RESETTING = 'resetting';
    public const IDLE = 'idle';
}
