<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Traits;

trait IndexBaseModel
{
    public function guessBaseModelName(): string
    {
        $indexClass = get_class($this);
        $base = str_replace('Indexes\Indexed', '', $indexClass);

        return $base;
    }

    public function getBaseModel()
    {
        if (! $this->baseModel) {
            return $this->guessBaseModelName();
        }

        return $this->baseModel;
    }

    public function isBaseModelDefined(): bool
    {
        return ! empty($this->baseModel);
    }
}
