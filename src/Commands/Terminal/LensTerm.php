<?php

namespace PDPhilip\ElasticLens\Commands\Terminal;

use Symfony\Component\Console\Output\OutputInterface;

final class LensTerm
{
    public static function liveRender(string $html = '', int $options = OutputInterface::OUTPUT_NORMAL): LiveHtmlRenderer
    {
        return new LiveHtmlRenderer($html, $options);
    }

    public static function asyncFunction(callable $task, int $options = OutputInterface::OUTPUT_NORMAL): AsyncHtmlRenderer
    {
        return new AsyncHtmlRenderer($task, $options);
    }
}
