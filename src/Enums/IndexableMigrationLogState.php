<?php

namespace PDPhilip\ElasticLens\Enums;

enum IndexableMigrationLogState: string
{
    case UNDEFINED = 'undefined';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function color(): string
    {
        return match ($this) {
            IndexableMigrationLogState::UNDEFINED => 'slate',
            IndexableMigrationLogState::SUCCESS => 'emerald',
            IndexableMigrationLogState::FAILED => 'rose',
        };
    }

    public function label(): string
    {
        return match ($this) {
            IndexableMigrationLogState::UNDEFINED => 'No Blueprint defined',
            IndexableMigrationLogState::SUCCESS => 'Migration Successful',
            IndexableMigrationLogState::FAILED => 'Migration Failed',
        };
    }
}
