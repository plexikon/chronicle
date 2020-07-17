<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

interface NamingReporter extends Reporter
{
    public function reporterName(): string;
}
