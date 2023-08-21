<?php

/*
 * This file is part of SolrProbe.
 *
 * SolrProbe is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * SolrProbe is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SolrProbe. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <david.maus@sub.uni-amburg.de>
 * @copyright (c) 2022,2023 by Staats- und Universitätsbibliothek Hamburg
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

namespace SUBHH\VuFind\SolrProbe;

final class LogfileHandler implements LogEntryHandler
{
    /** @var resource */
    private $logfile;

    /**
     * @param resource $logfile
     */
    public function __construct ($logfile)
    {
        $this->logfile = $logfile;
    }

    public function handle (LogEntry $logEntry) : void
    {
        $payload = [
            'backendId' => $logEntry->backendId,
            'command' => $logEntry->command,
            'sessionId' => $logEntry->sessionId,
            'requestId' => $logEntry->requestId,
            'duration' => strval($logEntry->solrRequestEnd - $logEntry->solrRequestStart),
            'qtime' => $logEntry->solrInternalQueryTime,
            'status' => $logEntry->solrRequestStatus,
            'statuscode' => $logEntry->solrRequestStatusCode,
            'requestUri' => $logEntry->requestUri,
        ];
        $message = $logEntry->timestamp->format('c') . ' ' . json_encode($payload) . PHP_EOL;
        if (fwrite($this->logfile, $message) === false) {
            trigger_error('[SolrProbe] ERROR Unable to write to logfile');
        }

    }
}
