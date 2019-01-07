<?php

/*
   ------------------------------------------------------------------------
   Glpi-Alignak
   Copyright (c) 2018 by the Alignak Team (http://alignak.net/)
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Glpi-Alignak project.

   Glpi-Alignak is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Glpi-Alignak is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Glpi-Alignak . If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Alignak
   @author    Frederic Mohier
   @copyright Copyright (c) 2018 Alignak team
   @license   AGPLv3 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://alignak.net/
   @link      http://alignak.net/
   @since     2018

   ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

// Create a default Alignak instance
$paObject = new PluginAlignakAlignak();
if (! $paObject->getFromDBByCrit(['name' => "Alignak-test"])) {
   $paObject->getEmpty();
   $paObject->fields['name'] = "Alignak-test";
   $alignakInstance = $paObject->addToDB();
} else {
   $alignakInstance = $paObject->getID();
}
echo "Instance: $alignakInstance\n";

// Create a default Alignak monitoring template
$paObject = new PluginAlignakMonitoringTemplate();
if (! $paObject->getFromDBByCrit(['name' => "Alignak-test-template"])) {
   $paObject->getEmpty();
   $paObject->fields['name'] = "Alignak-test-template";
   $monitoringTemplate = $paObject->addToDB();
} else {
   $monitoringTemplate = $paObject->getID();
}
echo "Template: $monitoringTemplate\n";

// Create a default Alignak counters template
$paObject = new PluginAlignakCountersTemplate();
if (! $paObject->getFromDBByCrit(['name' => "Alignak-test-template"])) {
   $paObject->getEmpty();
   $paObject->fields['name'] = "Alignak-test-template";
   $countersTemplate = $paObject->addToDB();
} else {
   $countersTemplate = $paObject->getID();
}
echo "Template: $countersTemplate\n";

// Create a default Alignak entity
$paObject = new PluginAlignakEntity();
if (! $paObject->getFromDBByCrit(['name' => "Alignak-test"])) {
   $paObject->getEmpty();
   $paObject->fields['name'] = "Alignak-test";
   $paObject->fields['plugin_alignak_entitites_id'] = 0; // Root!
   $paObject->fields['plugin_alignak_alignak_id'] = $alignakInstance;
   $paObject->fields['plugin_alignak_monitoring_template_id'] = $monitoringTemplate;
   $paObject->fields['plugin_alignak_counters_template_id'] = $countersTemplate;
   $entity = $paObject->addToDB();
} else {
   $entity = $paObject->getID();
}
echo "Entity: $entity\n";

// Create 10 computers
$paObject = new PluginAlignakComputer();
for ($i = 0; $i < 10; $i++) {
   $paObject = new Computer();
   $name = sprintf(__('Host-%1$s'), $i);
   if (! $paObject->getFromDBByCrit(['name' => $name])) {
      $paObject->getEmpty();
      $paObject->fields['name'] = $name;
      $paObject->fields['items_id'] = $computerId;
      $computerId = $paObject->addToDB();
   } else {
      $computerId = $paObject->getID();
   }
   echo "Computer: $computerId - ";

   $paObject = new PluginAlignakComputer();
   if (! $paObject->getFromDBByCrit(['items_id' => $computerId])) {
      $paObject->getEmpty();
      $paObject->fields['itemtype'] = 'Computer';
      $paObject->fields['items_id'] = $computerId;
      $paObject->fields['name'] = $name;
      $hostId = $paObject->addToDB();
   } else {
      $hostId = $paObject->getID();
   }
   $updates = [
      'id' => $hostId,
      'itemtype' => 'Computer',
      'items_id' => $computerId,
      'name'=> $name,
      'last_check'=> date('Y-m-d H:i:s'),
      'state'=> 'OK',
      'state_type'=> 'HARD',
      'output'=> $name . " is alive!",
      $perf = "inUsage=0.00%,85,98 outUsage=0.00%,85,98 inBandwidth=".rand(1, 100000).".00bps outBandwidth=".rand(1, 100000).".00bps inAbsolut=0 outAbsolut=12665653",
      'perf_data'=> $perf
   ];
   $cr = $paObject->update($updates);
//   $paObject->updateInDB($updates);
   echo "Computer: $hostId, update: ". serialize($cr) ."\n";

   $rand = rand(0, 100);
   if ($rand < 40) {
      sleep(1);
   }
}
