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

declare(strict_types=1);

namespace SUBHH\VuFind\SolrProbe;

use DateTimeImmutable;
use RuntimeException;

final class LogfileReader
{
    /** @var resource */
    private $handle;

    public function eof () : bool
    {
        return feof($this->handle);
    }

    public function open (string $filename) : void
    {
        $handle = fopen($filename, 'r');
        if (!is_resource($handle)) {
            throw new RuntimeException("Unable to open logfile for reading: {$filename}");
        }
        $this->handle = $handle;
    }

    public function close () : void
    {
        fclose($this->handle);
    }

    public function read () : ?LogEntry
    {
        if (feof($this->handle) === false) {
            if ($line = fgets($this->handle)) {
                $line = trim($line);
                
                list($timestamp, $payload) = explode(' ', $line, 2);
                $data = json_decode($payload, true);

                $timestamp = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $timestamp);

                if ($timestamp && $data) {

                    $logEntry = new LogEntry();
                    $logEntry->timestamp = $timestamp;
                    $logEntry->command = $data['command'] ?? null;
                    $logEntry->backendId = $data['backendId'] ?? null;
                    $logEntry->sessionId = $data['sessionId'] ?? null;
                    $logEntry->requestId = $data['requestId'] ?? null;
                    $logEntry->solrRequestDuration = $data['duration'] ?? null;
                    $logEntry->solrInternalQueryTime = $data['qtime'] ?? null;
                    $logEntry->solrRequestStatus = $data['status'] ?? null;
                    $logEntry->solrRequestStatusCode = $data['statuscode'] ?? null;
                    $logEntry->requestUri = $data['requestUri'] ?? null;
                    return $logEntry;
                }
            }
        }
        return null;
    }

}
