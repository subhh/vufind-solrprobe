<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SUBHH\VuFind\SolrProbe\LogEntry;
use SUBHH\VuFind\SolrProbe\LogEntryHandler;
use SUBHH\VuFind\SolrProbe\LogfileReader;

final class RRD implements LogEntryHandler
{
    /** @var int */
    private $accumulatorSize = 1;

    /** @var int */
    private $previousEpoch = 0;

    /** @var Accumulator */
    private $accumulator;

    /** @var string */
    private $filename;

    public function __construct (string $filename, int $accumulatorSize = 1)
    {
        $this->filename = $filename;
        $this->accumulatorSize = $accumulatorSize;
    }

    public function handle (LogEntry $logEntry) : void
    {
        $currentEpoch = intval($logEntry->timestamp->format('U'));
        if ($currentEpoch - $this->previousEpoch > $this->accumulatorSize) {
            $this->flush();
            $this->process($logEntry);
            $this->previousEpoch = $currentEpoch;
        } else {
            $this->process($logEntry);
        }
    }

    private function flush ()
    {
        if (is_null($this->accumulator) === false) {
            if (!file_exists($this->filename)) {
                rrd_create($this->filename, [
                    '--start', $this->previousEpoch,
                    '--step', '1',
                    'DS:rtime_min:GAUGE:300:0:U',
                    'DS:rtime_avg:GAUGE:300:0:U',
                    'DS:rtime_max:GAUGE:300:0:U',
                    'DS:qtime_min:GAUGE:300:0:U',
                    'DS:qtime_avg:GAUGE:300:0:U',
                    'DS:qtime_max:GAUGE:300:0:U',
                    'DS:cmd:GAUGE:300:0:U',
                    'DS:req:GAUGE:300:0:U',
                    'DS:ses:GAUGE:300:0:U',
                    'RRA:AVERAGE:0.5:1s:10d'
                ]) || die(rrd_error());
            }

            rrd_update($this->filename,
                       [
                           sprintf(
                               '%d:%F:%F:%F:%F:%F:%F:%d:%d:%d',
                               $this->previousEpoch,
                               $this->accumulator->requestTime->getMinimum(),
                               $this->accumulator->requestTime->getAverage(),
                               $this->accumulator->requestTime->getMaximum(),
                               $this->accumulator->queryTime->getMinimum(),
                               $this->accumulator->queryTime->getAverage(),
                               $this->accumulator->queryTime->getMaximum(),
                               $this->accumulator->commands,
                               count(array_unique($this->accumulator->requests)),
                               count(array_unique($this->accumulator->sessions))
                           )
                       ]
            );
        }
        $this->accumulator = new Accumulator();
    }

    private function process (LogEntry $logEntry) : void
    {
        $this->accumulator->commands++;
        $this->accumulator->requests[] = $logEntry->requestId;
        $this->accumulator->sessions[] = $logEntry->sessionId;
        $this->accumulator->requestTime->add($logEntry->solrRequestDuration ?? 0);
        $this->accumulator->queryTime->add($logEntry->solrInternalQueryTime ?? 0);
    }
}

final class Accumulator
{
    /** @var int */
    public $commands;

    /** @var int[] */
    public $requests = array();

    /** @var int */
    public $sessions = array();

    /** @var Average */
    public $requestTime;

    /** @var Average */
    public $queryTime;

    public function __construct ()
    {
        $this->requestTime = new Average();
        $this->queryTime = new Average();
    }

    public function reset () : void
    {
        $this->requests = array();
        $this->sessions = array();
    }

}

final class Average
{
    /** @var float[] */
    private $window;

    public function __construct ()
    {
        $this->reset();
    }

    public function add (float $value) : void
    {
        $this->window[] = $value;
    }

    public function reset () : void
    {
        $this->window = array();
    }

    public function getMinimum () : float
    {
        if (empty($this->window)) {
            return 0.0;
        }
        return min($this->window);
    }

    public function getMaximum () : float
    {
        if (empty($this->window)) {
            return 0.0;
        }
        return max($this->window);
    }

    public function getAverage () : float
    {
        if (empty($this->window)) {
            return 0.0;
        }
        return (array_sum($this->window) / count($this->window));
    }
}



if (count($argv) !== 2) {
    echo "Syntax: {$argv[0]} </path/to/logfile>" . PHP_EOL;
    exit(2);
}

$reader = new LogfileReader();
$reader->open($argv[1]);

$rrd01s = new RRD(__DIR__ . '/01s.rrd');
$rrd60s = new RRD(__DIR__ . '/60s.rrd', 60);

while ($reader->eof() === false) {
    if ($logEntry = $reader->read()) {
        $rrd01s->handle($logEntry);
        $rrd60s->handle($logEntry);
    }
}

$reader->close();
