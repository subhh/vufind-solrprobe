<?php

require_once __DIR__ . '/../vendor/autoload.php';

$db01s = __DIR__ . '/01s.rrd';

foreach (['01w', '04w', '12w'] as $interval) {

    rrd_graph("rtime-{$interval}.png", [
        "--title", "Request duration in ms",
        "--start", "now-{$interval}",
        "--width", "962", "--height", "300",
        "DEF:rtime_min={$db01s}:rtime_min:MIN",
        "DEF:rtime_avg={$db01s}:rtime_min:AVERAGE",
        "DEF:rtime_max={$db01s}:rtime_min:MAX",
        "LINE1:rtime_min#00cc00:min",
        "LINE1:rtime_avg#000000:avg",
        "LINE1:rtime_max#cc0000:max"
    ]) || die(rrd_error());

    rrd_graph("qtime-{$interval}.png", [
        "--title", "Query duration in ms",
        "--start", "now-{$interval}",
        "--width", "962", "--height", "300",
        "DEF:qtime_min={$db01s}:qtime_min:MIN",
        "DEF:qtime_avg={$db01s}:qtime_min:AVERAGE",
        "DEF:qtime_max={$db01s}:qtime_min:MAX",
        "LINE1:qtime_min#00cc00:min",
        "LINE1:qtime_avg#000000:avg",
        "LINE1:qtime_max#cc0000:max"
    ]) || die(rrd_error());


    $db60s = __DIR__ . '/60s.rrd';

    rrd_graph("commands-{$interval}.png", [
        "--title", "Commands per Minute",
        "--start", "now-{$interval}",
        "--width", "962", "--height", "300",
        "DEF:cmd_avg={$db60s}:cmd:AVERAGE",
        "LINE1:cmd_avg#000000"
    ]) || die(rrd_error());

    rrd_graph("sessions-{$interval}.png", [
        "--title", "Sessions per Minute",
        "--start", "now-{$interval}",
        "--width", "962", "--height", "300",
        "DEF:ses_avg={$db60s}:ses:AVERAGE",
        "LINE1:ses_avg#000000"
    ]) || die(rrd_error());

    rrd_graph("requests-{$interval}.png", [
        "--title", "Requests per Minute",
        "--start", "now-{$interval}",
        "--width", "962", "--height", "300",
        "DEF:req_avg={$db60s}:req:AVERAGE",
        "LINE1:req_avg#000000"
    ]) || die(rrd_error());

}
