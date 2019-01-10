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
   @since     2018

   ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/*
 * Class used to manage the relation between a computer and the plugin data
 */
class PluginAlignakComputer extends CommonDBTM
{

    /**
     * Check if an item is monitored
     *
     * Monitored if item type and item id are found in the table. Returns true or false.
     * If the item exists, the caller object is initialized with the DB content.
     *
     * @param $item CommonGLPI
     *
     * @return true/false
     **/
   function exists(CommonGLPI $item) {
      $paHost = new PluginAlignakComputer();
      //      PluginAlignakToolbox::logIfDebug("Check if monitored: " . $item->getType() . " / "  . $item->getName());
      PluginAlignakToolbox::log("Check if monitored: " . $item->getType() . " / "  . $item->getName());
      if ($this->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $item->getID()])) {
         return true;
      }
      return false;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $array_ret = [];
      if ($item->getID() > -1) {
         // If the item is monitored, add some more tabs...
         $pmHost = new self();
         if ($pmHost->exists($item)) {
            array_push($array_ret, self::createTabEntry(__('Monitoring live state', 'monitoring')));
            array_push($array_ret, self::createTabEntry(__('Monitoring history', 'monitoring')));
            array_push($array_ret, self::createTabEntry(__('Daily counters', 'monitoring')));
         }

         if (Session::haveRight('plugin_alignak_configuration', READ)) {
            array_push($array_ret, self::createTabEntry(__('Monitoring configuration', 'monitoring')));
         }
      }
      return $array_ret;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if (Session::haveRight('plugin_alignak_configuration', READ)) {
         $tabnum = $tabnum + 1;
      }
      $pmHost = new self();
      if ($pmHost->exists($item)) {
         switch ($tabnum) {
            case 0:
               $pmHost->showLiveState();
               break;
            case 1:
               $pmHost->showHistory();
               break;
            case 2:
               $pmHost->showCounters();
               break;
            case 3:
               $pmHost->showConfiguration();
               break;
         }
      }
      return true;
   }

   function showConfiguration() {

      // Call the show form for the Alignak entity
      // todo: get the Alignak entity for the self host
      $paEntity = new PluginAlignakEntity();
      $paEntity->getFromDBByCrit(["plugin_alignak_entitites_id" => 0]);
      $paEntity->showForm();
   }

   /**
    * Display the events history of a computer
    **/
   function showHistory() {

      echo '<table class="tab_cadre_fixe">';
      echo '<tr>';
      echo '<th>';
      echo __('Date', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Level', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Event', 'alignak');
      echo '</th>';
      echo '</tr>';

      $events = [
         "a" => [
            "date" => "20-09-2018 20:47:50",
            "level" => "INFO",
            "event" => "CURRENT HOST STATE: PC-entity-2;state;state_type;current_attempt;output"
         ],
         "b" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "CURRENT SERVICE STATE: PC-entity-2;service1;state;state_type;current_attempt;output",
         ],
         "c" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "PASSIVE HOST CHECK: PC-entity-2;status;output;long_output;perf_data",
         ],
         "d" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "WARNING",
            "event" => "PASSIVE SERVICE CHECK: PC-entity-2;service1;status;output;long_output;perf_data",
         ],
         "e" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "WARNING",
            "event" => "PASSIVE SERVICE CHECK: PC-entity-2;service2;status;output;long_output;perf_data",
         ],
         "f" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "SERVICE COMMENT: PC-entity-2;service;author;comment",
         ],
         "g" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "WARNING",
            "event" => "SERVICE ALERT: PC-entity-2;service1;WARNING;HARD;current_attempt;service is raising warning!",
         ],
         "h" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "SERVICE ALERT: PC-entity-2;service;OK;HARD;current_attempt;Service is now OK again",
         ],
         "i" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "SERVICE ACKNOWLEDGE ALERT: PC-entity-2;service1;STARTED; Service problem has been acknowledged",
         ],
         "j" => [
            "date" => ", 20-09-2018 17:43:58",
            "level" => "INFO",
            "event" => "SERVICE NOTIFICATION: PC-entity-2;service1;state;command;output",
         ],
      ];

      foreach ($events as $id => $event) {
         echo '<tr>';
         echo '<td>';
         echo $event['date'];
         echo '</td>';
         echo '<td class="'. $event['level'] .'">';
         echo $event['level'];
         echo '</td>';
         echo '<td>';
         echo $event['event'];
         echo '</td>';
         echo '</tr>';
      }

      echo "</table>";

      return true;
   }

   /**
     * Display the live state of a computer
     **/
   function showLiveState() {
      echo '<table class="tab_cadre_fixe">';
      echo '<tr>';
      echo '<th>';
      echo __('Service', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Last check', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Status', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Output', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Problem', 'alignak');
      echo '</th>';
      echo '</tr>';

      $services = [
         "a" => [
            "name" => "Autre matériel",
            "last_check" => "20-09-2018 20:47:50",
            "status" => "OK",
            "output" => "Ok - List (status) : NetworkLan (0);NetworkWifi (0);Son (0);ioKiosk (0);Clavier (0);dalletactile (0);Ecran (0);', u\"----- NetworkLan found 1 devices : [LAN1 (Intel(R) 82579LM Gigabit Network Connection), status : 2 -> connected. NetworkWifi found 1 devices : [WLAN (Intel(R) Centrino(R) Advanced-N 6205), status : 4 -> Hardware not present !. Son found 1 devices : Realtek High Definition Audio (Realtek High Definition Audio), status : OK (), service : IntcAzAudAddService - Driver : IntcAzAudAddService (Service for Realtek HD Audio (WDM)), started : True, status : OK, Running ioKiosk Package 'ioKiosk' status (not checked) is 0. Clavier found keyboard(s) : Enhanced (101- or 102-key) (USB Input Device), status : OK - Driver : HidUsb (Microsoft HID Class Driver), started : True, status : OK, Running dalletactile found 1 devices : Microsoft Input Configuration Device (Microsoft Input Configuration Device), status : OK (), service : MTConfig - Driver : MTConfig (Microsoft Input Configuration Driver), started : True, status : OK, Running Ecran found 1 video controller(s) : Intel(R) HD Graphics 4000 (Intel(R) HD Graphics 4000), status : OK, found 1 display(s) : Generic PnP Monitor (Generic PnP Monitor), status : OK, resolution : 1280x768\")",
            "problem" => ""
         ],
         "b" => [
            "name" => "Imprimante",
            "last_check" => ", 20-09-2018 17:43:58",
            "status" => "OK",
            "output" => "Online (J: -8) (sn: EK3K-CNAM-022002)",
            "problem" => ""
         ],
         "c" => [
            "name" => "Lecteur de cartes",
            "last_check" => ", 20-09-2018 17:43:58",
            "status" => "WARNING",
            "output" => "OK (card: Mute)",
            "problem" => ""
         ],
         "d" => [
            "name" => "Onduleur",
            "last_check" => ", 20-09-2018 17:43:58",
            "status" => "OK",
            "output" => "OK (charge: 100 %)",
            "problem" => ""
         ],
         "e" => [
            "name" => "Papier",
            "last_check" => ", 20-09-2018 17:43:58",
            "status" => "OK",
            "output" => "DayPrintedPages (3: 0, avg: 55) (J: -8)",
            "problem" => ""
         ],
      ];

      foreach ($services as $id => $service) {
         echo '<tr>';
         echo '<td>';
         echo $service['name'];
         echo '</td>';
         echo '<td>';
         echo $service['last_check'];
         echo '</td>';
         echo '<td class="'. $service['status'] .'">';
         echo $service['status'];
         echo '</td>';
         echo '<td>';
         echo $service['output'];
         echo '</td>';
         echo '<td>';
         echo $service['problem'];
         echo '</td>';
         echo '</tr>';
      }

      echo "</table>";

      return true;
   }

   /**
    * Display the counters of a computer
    **/
   function showCounters() {

      $entity = new Entity();
      $entity->getFromDB($this->fields["entities_id"]);

      $itemtype = $this->fields['itemtype'];
      $item = new $itemtype();
      $item->getFromDB($this->fields['items_id']);

      echo '<table class="tab_cadre_fixe">';
      echo '<tr>';
      echo '<th>';
      echo __('Day', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Computer', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Entity', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Pages imprimées (total)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Pages imprimées (jour)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Pages restantes', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Pages rétractées (total)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Pages rétractées (jour)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Papier rechargé', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes insérées (jour)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes insérées (total)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes incorrectes (jour)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes incorrectes (total)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes retirées (jour)', 'alignak');
      echo '</th>';
      echo '<th>';
      echo __('Cartes retirées (total)', 'alignak');
      echo '</th>';
      echo '</tr>';

      $events = [
         [
            "day" => "21-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508, 0, 1503, 120,0,0,0,99408,0,9992,0,107849]
         ],
         [
            "day" => "20-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508, 0, 1503,120, 0, 0, 0,99408,112, 9992, 1, 107849]
         ],
         [
            "day" => "19-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 , 0, 1503 , 120 , 0, 0, 0, 99408 , 44 , 9879 , 1 , 107847]
         ],
         [
            "day" => "18-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 ,0,1503 ,120 ,0,0,0,99407 ,0,9805 ,0,107846]
         ],
         [
            "day" => "17-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 ,0,1503 ,120 ,0,0,1 ,99407 ,17 ,9805 ,1 ,107846]
         ],
         [
            "day" => "16-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 ,0,1503 ,120 ,0,0,0,99406 ,150 ,9787 ,3 ,107845]
         ],
         [
            "day" => "15-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 ,0,1503 ,120 ,0,0,0,99406 ,156 ,9636 ,8 ,107842]
         ],
         [
            "day" => "14-09-2018",
            "computer" => $item->getName(),
            "entity" => $entity->getName(),
            "counters" => [29508 ,0,1503 ,120 ,0,0,1 ,99406 ,110 ,9479 ,5 ,107834]
         ],
      ];

      foreach ($events as $event) {
         echo '<tr>';
         echo '<td>';
         echo $event['day'];
         echo '</td>';
         echo '<td class="OK">';
         echo $event['computer'];
         echo '</td>';
         echo '<td>';
         echo $event['entity'];
         echo '</td>';
         foreach ($event['counters'] as $value) {
            echo '<td>';
            if ($value == 0) {
               echo '-';
            } else {
               echo $value;
            }
            echo '</td>';
         }
         echo '</tr>';
      }

      echo "</table>";

      return true;
   }

   /**
     * Display the configuration of the computer
     **/
   function showForm($item, $options = []) {
       global $DB,$CFG_GLPI;

      //       PluginAlignakToolbox::logIfDebug("Show form for: " . $item->getName());
       /*
       if ($items_id!='') {
        $this->getFromDB($items_id);
       } else {
        $this->getEmpty();
       }
       */

       // $this->showTabs($options);
       $this->showFormHeader($options);

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Tag', 'monitoring')." :</td>";
       echo "<td>";
       echo $this->fields["tag"];
       echo "</td>";
       echo "<td>".__('Username (Shinken webservice)', 'monitoring')."&nbsp;:</td>";
       echo "<td>";
       echo "<input type='text' name='username' value='".$this->fields["username"]."' size='30'/>";
       echo "</td>";
       echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Shinken IP address', 'monitoring')." :</td>";
       echo "<td>";
       echo "<input type='text' name='ip' value='".$this->fields["ip"]."' size='30'/>";
       echo "</td>";
       echo "<td>".__('Password (Shinken webservice)', 'monitoring')."&nbsp;:</td>";
       echo "<td>";
       echo "<input type='text' name='password' value='".$this->fields["password"]."' size='30'/>";
       echo "</td>";
       echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Lock shinken IP', 'monitoring')." :</td>";
       echo "<td>";
       Dropdown::showYesNo('iplock', $this->fields["iplock"]);
       echo "</td>";
       echo "<td colspan='2'>";
       echo "</td>";
       echo "</tr>";

       $this->showFormButtons($options);

       return true;
   }

   /*
    * Get comments for the monitored host
    */
   function getComments() {
      global $CFG_GLPI;

      $comment = "";
      $toadd   = [];

      // The associated computer ...
      $item = new $this->fields['itemtype'];
      $item->getFromDB($this->fields['items_id']);

      if ($this->fields['itemtype'] == 'Computer') {
         if ($item->isField('completename')) {
            $toadd[] = ['name'  => __('Complete name'),
               'value' => nl2br($item->getField('completename'))];
         }

         $type = new ComputerType();
         if ($item->getField("computertypes_id")) {
            $type->getFromDB($item->getField("computertypes_id"));
            $type = $type->getName();
            if (! empty($type)) {
               $toadd[] = ['name'  => __('Type'),
                  'value' => nl2br($type)];
            }
         } else {
            return $comment;
         }

         $model = new ComputerModel();
         if ($item->getField("computermodels_id")) {
            $model->getFromDB($item->getField("computermodels_id"));
            $model = $model->getName();
            if (! empty($model)) {
               $toadd[] = ['name'  => __('Model'),
                  'value' => nl2br($model)];
            }
         }

         $state = new State();
         $state->getFromDB($item->fields["states_id"]);
         $state = $state->getName();
         if (! empty($state)) {
            $toadd[] = ['name'  => __('State'),
               'value' => nl2br($state)];
         }

         $entity = new Entity();
         $entity->getFromDB($item->fields["entities_id"]);
         $entity = $entity->getName();
         if (! empty($entity)) {
            $toadd[] = ['name'  => __('Entity'),
               'value' => nl2br($entity)];
         }

         $location = new Location();
         $location->getFromDB($item->fields["locations_id"]);
         $location = $location->getName(['complete'  => true]);
         if (! empty($location)) {
            $toadd[] = ['name'  => __('Location'),
               'value' => nl2br($location)];
         }

         if (! empty($item->fields["serial"])) {
            $toadd[] = ['name'  => __('Serial'),
               'value' => nl2br($item->fields["serial"])];
         }
         if (! empty($item->fields["otherserial"])) {
            $toadd[] = ['name'  => __('Inventory number'),
               'value' => nl2br($item->fields["otherserial"])];
         }

         if (($this instanceof CommonDropdown)
            && $this->isField('comment')) {
            $toadd[] = ['name'  => __('Comments'),
               'value' => nl2br($this->getField('comment'))];
         }

         if (count($toadd)) {
            foreach ($toadd as $data) {
               $comment .= sprintf(__('%1$s: %2$s')."<br>",
                  "<span class='b'>".$data['name'], "</span>".$data['value']);
            }
         }
      } else {
         $toadd[] = ['name'  => __('Host type'),
            'value' => nl2br($item->getTypeName())];

         if ($item->isField('completename')) {
            $toadd[] = ['name'  => __('Complete name'),
               'value' => nl2br($item->getField('completename'))];
         }
      }

      if (!empty($comment)) {
         return Html::showToolTip($comment, ['display' => false]);
      }
   }

   /*
    * Search options, see: https://glpi-developer-documentation.readthedocs.io/en/master/devapi/search.html#search-options
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Alignak computer')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comment'),
      ];

      //      $tab[] = [
      //         'id'                 => '3',
      //         'table'              => $this->getTable(),
      //         'field'              => 'plugin_alignak_entitites_id',
      //         'name'               => __('Related entity', 'alignak'),
      //      ];
      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_entities',
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'entities_id',
         'name'               => __('Related entity', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_computers',
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'items_id',
         'name'               => __('Related computer', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'last_check',
         'datatype'           => 'datetime',
         'name'               => __('Last check time', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'datatype'           => 'string',
         'name'               => __('State', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'state_type',
         'datatype'           => 'string',
         'name'               => __('State type', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'output',
         'datatype'           => 'string',
         'name'               => __('Check result', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'perf_data',
         'datatype'           => 'string',
         'name'               => __('Performance data', 'alignak'),
      ];

      /*
       * Include other fields here
       */

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'usehaving'          => true,
         'searchtype'         => 'equals',
      ];

      return $tab;
   }

   static function add_default_where($in) {

       list($itemtype, $condition) = $in;
      if ($itemtype == 'Computer') {
          $table = getTableForItemType($itemtype);
          $condition .= " (".$table.".groups_id NOT IN (".implode(',', $_SESSION["glpigroups"])."))";
      }
         return [$itemtype, $condition];
   }
}

