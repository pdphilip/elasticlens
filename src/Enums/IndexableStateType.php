<?php

namespace PDPhilip\ElasticLens\Enums;

enum IndexableStateType: string
{
    case INIT = 'init';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function color(): string
    {
        return match ($this) {
            IndexableStateType::INIT => 'slate',
            IndexableStateType::SUCCESS => 'emerald',
            IndexableStateType::FAILED => 'rose',
        };
    }

    public function label(): string
    {
        return match ($this) {
            IndexableStateType::INIT => 'Build Initializing',
            IndexableStateType::SUCCESS => 'Index Build Successful',
            IndexableStateType::FAILED => 'Index Build Failed',
        };
    }
}
