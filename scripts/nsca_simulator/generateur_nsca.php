<?php
include("./docopt.php");

$doc = <<<DOC
generateur_nsca_hosts.php

Usage:
   generateur_nsca_hosts.php [--server SERVER] [--port PORT] [--count=count] [--delay=delay]

Options:
   --server SERVER      Monitoring server address (default: 127.0.0.1)
   --port PORT          Monitoring server NSCA port (default: 25667)
   --count=count        Hosts count (default: 1)
   --delay=delay        Delay between two simulation loops (default: 15)
   
Set the count value to define the number of simulated hosts.

The delay between two simulation loops defaults to 15 minutes.
DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

# Monitoring server address
$sh_host = "127.0.0.1";
if (! is_null($args['--server'])) {
   $sh_host = $args['--server'];
}
echo "NSCA server address: $sh_host\n";

$sh_port = 5667;
if (! is_null($args['--port'])) {
   $sh_port = $args['--port'];
}
echo "NSCA server port: $sh_port\n";
$sh_conf_file = "./send_nsca.cfg";

# Configuration des noms de machines
$hostname = "sim-00";
$nb_hosts = 1;
if (! is_null($args['--count'])) {
   $nb_hosts = $args['--count'];
}
echo "Hosts count: $nb_hosts\n";

# Configuration de l'application et des compteurs
$application = "nsca_printer";
$counters = array("Cut Pages", "Retracted pages", "Paper Reams");
# Configuration de l'application et des compteurs (bis)
$application2 = "nsca_reader";
$counters2 = array("MuteCards", "PoweredCards", "NotRemoved");
$max_counter_increase = 3;

# delai max entre deux checks
$delay = 15;
if (! is_null($args['--delay'])) {
   $delay = $args['--delay'];
}
$delay = $delay * 60;
echo "Simulation loops delay: $delay seconds\n";

# Binaire "send_nsca"
$send_nsca = "/usr/local/sbin/send_nsca";
if (! file_exists($send_nsca)) {
   // Debian:
   $send_nsca = "/usr/sbin/send_nsca";
   if (! file_exists($send_nsca)) {
      die ("Unable to locate send_nsca executable!\n");
   }
}
$log = "/tmp/generateur_nsca.log";

# Retention data
$services_retention = "/tmp/generateur_nsca_services_retention.dat";

# PID files
$hosts_pid = "/tmp/generateur_nsca_hosts.pid";
$services_pid = "/tmp/generateur_nsca_services.pid";

# Always send or only during day
$always = true;

function echon($string) {
   global $log;
   echo date("Y-m-d H:i:s")." ".$string."\n";
   file_put_contents($log, date("Y-m-d H:i:s")." ".$string."\n", FILE_APPEND);
}

function send_nsca($hostname, $status, $output, $perf_data = "", $service = "" ) {
   global $always;

   if (! $always) {
      $now = new DateTime("now");
      $day_start = new DateTime($now->format("Y-m-d 08:30:00"));
      $day_end   = new DateTime($now->format("Y-m-d 19:00:00"));

      if (($now < $day_start) || ($now > $day_end)) {
         return(1);
      }
   }

   global $send_nsca, $sh_host, $sh_port, $sh_conf_file;
   if ($service=="") {
      $cmd = 'echo "'.$hostname.";".$status.";".$output.$perf_data.'" | '.$send_nsca.' -H '.$sh_host.' -p '.$sh_port.' -c '.$sh_conf_file." -d ';'";
      echon("Host check :    $cmd");
   } else {
      if ($perf_data!="") {
         $perf_data = "|".$perf_data;
      }
      $cmd = 'echo "'.$hostname.";".$service.";".$status.";".$output.$perf_data.'" | '.$send_nsca.' -H '.$sh_host.' -p '.$sh_port.' -c '.$sh_conf_file." -d ';'";
      echon("Service check : $cmd");
   }
   exec($cmd . " > /dev/null &");
}
