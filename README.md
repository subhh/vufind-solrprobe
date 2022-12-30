# VuFind SolrProbe

SolrProbe is copyright (c) 2022 by Staats- und Universitätsbibliothek Hamburg and released under the terms of the GNU
General Public License v3.

## Description

SolrProbe hooks into VuFind's search system and collects information about the interaction with the search server. It
gathers the following information:

- Backend identifier
- Name of the search command class
- Session identifier
- Request identifier
- Time when the ```.pre``` event triggered
- Time when the ```.post``` or ```.error``` event triggered
- Solr query time if available
- Request status ("OK" or the exception class name)
- Request status code ("200" or the exception code)

## Usage

Attach the SolrProbe to the shared event manager during the Laminas MVC ```bootstrap``` event.

```php
class Module {

    ...

    public function onBootStrap (MvcEvent $event) : void
    {
        $filename = sprintf('probe.log.%s', date('Y-m-d'));
        $logfile = fopen(__DIR__ . '/log/' . $filename, 'a');
        if ($logfile !== false) {
            $services = $event->getApplication()->getServiceManager();
            $events = $services->get('SharedEventManager');
            $handler = new SUBHH\VuFind\SolrProbe\LogfileHandler($logfile);
            $probe = new SUBHH\VuFind\SolrProbe\SolrProbe($handler);
            $probe->attach($events);
            register_shutdown_function([$probe, 'onEngineShutdown']);
    }

    ...
}
```

## Authors

David Maus &lt;david.maus@sub.uni-hamburg.de&gt;
