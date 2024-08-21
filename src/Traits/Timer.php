<?php

namespace PDPhilip\ElasticLens\Traits;

trait Timer
{
    private array $timer = [];

    public function startTimer()
    {
        $this->timer['start'] = microtime(true);

        return $this->timer;
    }

    private function _endTimer()
    {
        if (! empty($this->timer['start'])) {
            $this->timer['end'] = microtime(true);
            $this->timer['took'] = round(($this->timer['end'] - $this->timer['start']) * 1000, 0);
            $this->timer['time']['ms'] = $this->timer['took'];
            $this->timer['time']['sec'] = round($this->timer['took'] / 1000, 2);
            $this->timer['time']['min'] = round($this->timer['took'] / 60000, 2);
        } else {
            $this->timer['time'] = 'Time was not initialized';
        }

        return $this->timer;
    }

    public function getTime()
    {
        $this->_endTimer();

        return $this->timer['time'];

    }
}
