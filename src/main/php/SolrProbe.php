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

use VuFindSearch\Backend\Solr\Response\Json\RecordCollection;
use VuFindSearch\Command\CommandInterface;
use VuFindSearch\Service;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;

use DateTimeImmutable;
use SplObjectStorage;

final class SolrProbe
{

    /**
     * @var ?string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $requestId;

    /**
     * @var SplObjectStorage<CommandInterface,LogEntry>
     */
    private $requests;

    /**
     * @var LogEntryHandler
     */
    private $logger;

    public function __construct (LogEntryHandler $logger)
    {
        $this->logger = $logger;
        $this->requestId = $this->createRequestId();
        $this->sessionId = $this->createSessionId();
        $this->requests = new SplObjectStorage();
    }

    public function attach (SharedEventManagerInterface $events) : void
    {
        $events->attach('VuFindSearch', Service::EVENT_PRE, [$this, 'onSearchPre']);
        $events->attach('VuFindSearch', Service::EVENT_POST, [$this, 'onSearchPost']);
        $events->attach('VuFindSearch', Service::EVENT_ERROR, [$this, 'onSearchError']);
    }

    public function onSearchPre (EventInterface $event) : void
    {
        $command = $event->getParam('command');
        if ($command) {
            $log = new LogEntry();
            $log->timestamp = new DateTimeImmutable();
            $log->backendId = $command->getTargetIdentifier();
            $log->command = (string)get_class($command);
            $log->requestId = $this->requestId;
            $log->sessionId = $this->sessionId;
            $log->solrRequestStart = intval(hrtime(true) / 1000000);
            if (array_key_exists('REQUEST_URI', $_SERVER)) {
                $urlParts = explode('?', $_SERVER['REQUEST_URI'], 2);
                $log->requestUri = $urlParts[0];
            }
            $this->requests->attach($command, $log);
        } else {
            $this->warn('Received a VuFindSearch .pre event without a command');
        }
    }

    public function onSearchPost (EventInterface $event) : void
    {
        $command = $event->getParam('command');
        if ($command) {
            if ($this->requests->contains($command)) {
                $log = $this->requests->offsetGet($command);
                $log->solrRequestEnd = intval(hrtime(true) / 1000000);
                $log->solrRequestStatus = 'OK';
                $log->solrRequestStatusCode = 200;

                if ($command->isExecuted()) {
                    $result = $command->getResult();
                    if ($result instanceOf RecordCollection) {
                        if (method_exists($result, 'getResponseHeader')) {
                            // @phan-suppress-next-line PhanUndeclaredMethod
                            $header = $result->getResponseHeader();
                            if (array_key_exists('QTime', $header)) {
                                $log->solrInternalQueryTime = $header['QTime'];
                            }
                        }
                    }
                }

                $this->requests->detach($command);
                $this->log($log);
            } else {
                $this->warn('Received a VuFindSearch .post event for an unknown command');
            }
        } else {
            $this->warn('Received a VuFindSearch .post event without a command');
        }
    }

    public function onSearchError (EventInterface $event) : void
    {
        $command = $event->getParam('command');
        if ($command) {
            if ($this->requests->contains($command)) {
                $log = $this->requests->offsetGet($command);
                $log->solrRequestEnd = intval(hrtime(true) / 1000000);

                $error = $event->getParam('error');
                if ($error) {
                    if ($error instanceOf \Exception) {
                        $log->solrRequestStatus = get_class($error);
                        $log->solrRequestStatusCode = $error->getCode();
                    } else {
                        $this->warn('Received a VuFindSearch .error event with an invalid error object');
                    }
                } else {
                    $this->warn('Received a VuFindSearch .error event without an error object');
                }
                $this->requests->detach($command);
                $this->log($log);
            } else {
                $this->warn('Received a VuFindSearch .error event for an unknown command');
            }
        } else {
            $this->warn('Received a VuFindSearch .error event without a command');
        }
    }

    public function onEngineShutdown () : void
    {
        if ($this->requests->count() > 0) {
            $message = sprintf('[SolrProbe] Terminating script with still %d searches in progress', $this->requests->count());
            trigger_error($message);
        }
    }

    private function warn (string $message) : void
    {
        trigger_error('[SolrProbe] WARNING ' . $message, E_USER_WARNING);
    }

    private function log (LogEntry $entry) : void
    {
        $this->logger->handle($entry);
    }

    private function createSessionId () : ?string
    {
        return session_id() ?: null;
    }

    private function createRequestId () : string
    {
        $id = [
            $_SERVER['REQUEST_TIME_FLOAT'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['REMOTE_PORT']
        ];
        return sprintf('%x', crc32(implode(' ', $id)));
    }
}
