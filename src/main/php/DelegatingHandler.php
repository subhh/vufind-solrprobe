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

final class DelegatingHandler implements LogEntryHandler
{
    /** @var LogEntryHandler[] */
    private $handlers;

    public function __construct (LogEntryHandler ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle (LogEntry $logEntry) : void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($logEntry);
        }
    }
}
