<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Enums;

enum IndexableBuildState: string
{
    case INIT = 'init';
    case SUCCESS = 'success';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';

    public function color(): string
    {
        return match ($this) {
            IndexableBuildState::INIT => 'slate',
            IndexableBuildState::SUCCESS => 'emerald',
            IndexableBuildState::SKIPPED => 'emerald',
            IndexableBuildState::FAILED => 'rose',
        };
    }

    public function label(): string
    {
        return match ($this) {
            IndexableBuildState::INIT => 'Build Initializing',
            IndexableBuildState::SUCCESS => 'Index Build Successful',
            IndexableBuildState::SKIPPED => 'Index Build Skipped',
            IndexableBuildState::FAILED => 'Index Build Failed',
        };
    }
}
