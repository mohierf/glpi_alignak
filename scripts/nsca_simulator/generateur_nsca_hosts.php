<?php

# echo -e "ek3k-cnam-0010\tnsca_printer\t0\tOK|'Cut Pages'=5274c 'Retracted Pages'=1c 'Paper Reams'=6c 'Trash Empty'=1c 'Printer Replace'=1c" | send_nsca -H dev
require("generateur_nsca.php");

file_put_contents($hosts_pid, getmypid() . "\n" );

// Services counters storage
$service_counters = array();
if (file_exists($services_retention)) {
  $service_counters = unserialize(file_get_contents($services_retention));
}

while (true) {
   # Host checks
   for ($i = 1; $i <= $nb_hosts; $i++) {
      $host = $hostname.sprintf("%1$02d", $i);

      send_nsca($host, "0", "Host is alive ...");

      # cpu
      $nb_rand_1m = 10 + mt_rand(1, 5);
      $nb_rand_30s = 10 + mt_rand(1, 10);
      $output = "Ok CPU Load ok";
      $status = "0";
      send_nsca($host, $status, $output, "'total 1m'=".$nb_rand_1m."%;50;80 'total 30s'=".$nb_rand_30s."%;50;80", "nsca_cpu");

      # memory
      $nb_physical = 30 + mt_rand(1, 5);
      $nb_virtual = 5 + mt_rand(1, 5);
      $output = "Ok";
      $status = "0";
      send_nsca($host, $status, $output, "'physical'=0GB;1;1;0;1 'physical %'=".$nb_physical."%;75;89;0;100 'virtual'=0GB;1;1;0;1 'virtual %'=".$nb_virtual."%;75;89;0;100", "nsca_memory");

      # disk
      $nb_disk = 3 + mt_rand(1, 1);
      $nb_diskD = 5 + mt_rand(1, 4);
      $output = "OK: All drives within bounds.";
      $status = "0";
      send_nsca($host, $status, $output, "'C: used'=11GB;148;223;0;297 'C: used %'=".$nb_disk."%;50;75;0;100 'D: used %'=".$nb_diskD."%;50;75;0;100", "nsca_disk");

      # network
      $nb_received = 4000000 + mt_rand(1, 1000000);
      $nb_sent = 1500000 + mt_rand(1, 500000);
      $output = "Ok - found 1 devices : [LAN1 (Intel(R) 82579LM Gigabit Network Connection), status : 2 -> connected.";
      $status = "0";
      send_nsca($host, $status, $output, "'CurrentBandwidth'=100000000 'BytesReceivedPersec'=".$nb_received." 'BytesSentPersec'=".$nb_sent." 'BytesTotalPersec'=".($nb_received+$nb_sent), "nsca_network");
   }

   # Nombre de machines aleatoires
   $nb_rand_hosts = mt_rand(1, $nb_hosts);

   # Service checks
   for ($i = 1; $i <= $nb_rand_hosts; $i++) {

      # Machine Aleatoire
      $host = $hostname.sprintf("%1$02d", mt_rand(1, $nb_hosts));
      echon("Selected host: $host");

      # Compteur aleatoire
      $counter = $counters[mt_rand(0, count($counters)-1)];
      if (isset($service_counters[$host][$counter])) {
         $value = $service_counters[$host][$counter] + mt_rand(1, $max_counter_increase);
      } else {
         $value = mt_rand(1, $max_counter_increase);
      }
      echon("Selected counter: $application / $counter=$value");

      # Compteur aleatoire (bis)
      $counter2 = $counters2[mt_rand(0, count($counters)-1)];
      if (isset($service_counters[$host][$counter2])) {
         $value2 = $service_counters[$host][$counter2] + mt_rand(1, $max_counter_increase);
      } else {
         $value2 = mt_rand(1, $max_counter_increase);
      }
      echon("Selected counter: $application2 / $counter2=$value2");

      // Application counters
      $statuses = ["0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", "1", "1", "2"];
      $status = $statuses[mt_rand(0, count($statuses) - 1)];
//      $status = "0";
      $output = "Ok - $application - $counter=$value";
      if ($status == "1") {
         $output = "Warning - $application - $counter=$value";
      } else if ($status == "2") {
         $output = "Critical - $application - $counter=$value";
      }
      $perf_data = "'$counter'=".$value;
      foreach ($counters as $cpt) {
         if ($cpt == $counter) {
            continue;
         }
         if (isset($service_counters[$host][$cpt])) {
            $perf_data .= " '$cpt'=". $service_counters[$host][$cpt];
         }
      }
      send_nsca($host, $status, $output, $perf_data, "$application");
      $service_counters[$host][$counter] = $value;
      sleep(10);

      // Application counters (bis)
      $status = "0";
      $output = "Ok - $application2 - $counter2=$value2";
      $perf_data = "'$counter2'=".$value2;
      foreach ($counters2 as $cpt) {
         if ($cpt == $counter2) {
            continue;
         }
         if (isset($service_counters[$host][$cpt])) {
            $perf_data .= " '$cpt'=". $service_counters[$host][$cpt];
         }
      }
      send_nsca($host, $status, $output, $perf_data, "$application2");
      $service_counters[$host][$counter2] = $value2;
      sleep(10);

      // Retention des donnees
      file_put_contents($services_retention, serialize($service_counters));
   }

   // Check suivant aleatoire
   $sleep = mt_rand(1, $delay);
   echon("Next check in $sleep seconds");
   sleep($sleep);
}
