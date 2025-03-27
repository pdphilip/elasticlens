<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Traits;

use Exception;
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Index\BuildResult;
use PDPhilip\ElasticLens\Index\LensBuilder;

trait IndexFieldMap
{
    // ----------------------------------------------------------------------
    // Field Map
    // ----------------------------------------------------------------------

    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map($this->baseModel);
    }

    public function getFieldSet()
    {
        return $this->fieldMap()->getFieldMap();
    }

    public function getRelationships()
    {
        return $this->fieldMap()->getRelationships();
    }

    public function getObserverSet()
    {
        $base = null;
        if (! empty($this->observeBase)) {
            $base = $this->getBaseModel();
        }
        $embedded = $this->fieldMap()->getObservers();
        if ($embedded) {
            $embedded = $this->mapUpstreamEmbeds($embedded);
        }

        return [
            'base' => $base,
            'embedded' => $embedded,
        ];
    }

    public function getObservedModels()
    {
        $set = $this->getObserverSet();
        $embedded = $set['embedded'];
        $embeddedModels = [];
        if ($embedded) {
            foreach ($embedded as $embed) {
                if ($embed['observe']) {
                    $embeddedModels[] = $embed['model'];
                }
            }
        }

        return [
            'base' => $set['base'],
            'embedded' => $embeddedModels,
        ];
    }

    // ----------------------------------------------------------------------
    // Build Index
    // ----------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public static function indexBuild($id, $source): BuildResult
    {
        return (new static)->getBuilder()->buildIndex($id, $source);
    }

    /**
     * @throws Exception
     */
    public function indexRebuild($source): BuildResult
    {
        return $this->getBuilder()->buildIndex($this->id, $source);
    }

    /**
     * @throws Exception
     */
    public function getBuilder(): LensBuilder
    {
        return new LensBuilder(get_class($this));
    }

    // ----------------------------------------------------------------------
    // Map any upstream embeds
    // ----------------------------------------------------------------------

    public function mapUpstreamEmbeds($embeds)
    {
        foreach ($embeds as $i => $embed) {
            $embeds[$i] = $this->_fetchUpstream($embed, $embeds);
        }

        return $embeds;
    }

    private function _fetchUpstream($embed, $embeds)
    {
        if ($embed['model'] !== $this->baseModel) {
            foreach ($embeds as $em) {
                if ($em['relation'] === $embed['model']) {
                    $embed['upstream'] = $this->_fetchUpstream($em, $embeds);
                }
            }
        }

        return $embed;
    }
}
