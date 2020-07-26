<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Query;

use Plexikon\Chronicle\Projector\Concerns\HasProjector;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;

final class QueryProjectorFactory implements ProjectorFactory
{
    use HasProjector, HasProjectorFactory;

    protected ProjectorContext $projectorContext;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;

    public function __construct(ProjectorContext $projectorContext,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias)
    {
        $this->projectorContext = $projectorContext;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
    }

    public function run(bool $keepRunning = true): void
    {
        $this->projector = new QueryProjector($this->projectorContext, $this->chronicler, $this->messageAlias);

        $this->projector->run($keepRunning);
    }
}
