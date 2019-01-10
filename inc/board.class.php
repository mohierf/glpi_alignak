<?php
class PluginAlignakBoard extends CommonGLPI
{
   static $rightname = 'plugin_alignak_alignak';

   /**
    * Get menu name
    *
    * @return string - menu name
    **/
   static function getMenuName() {
      return __("Alignak boards", "alignak");
   }

   /**
    * Manage header of list
    */
   static function showHeaderItem($title, $numoption, &$num, $start, $globallinkto, $page, $itemtype) {
      global $CFG_GLPI;

      $order = "ASC";
      if (isset($_GET["order"])) {
         $order = $_GET["order"];
      }

      $linkto = $CFG_GLPI['root_doc']."/plugins/alignak/front/$page?".
         "itemtype=$itemtype&amp;sort=".$numoption."&amp;order=".
         ($order=="ASC"?"DESC":"ASC")."&amp;start=".$start.
         $globallinkto;
      $issort = false;
      if (isset($_GET['sort']) && $_GET['sort'] == $numoption) {
         $issort = true;
      }
      echo Search::showHeaderItem(0, $title, $num, $linkto, $issort, $order);
   }


   static function getState($state, $state_type, $event, $acknowledge = 0) {
      $shortstate = '';
      switch ($state) {

         case 'UP':
         case 'OK':
            $shortstate = 'green';
            break;

         case 'DOWN':
         case 'UNREACHABLE':
         case 'CRITICAL':
         case 'DOWNTIME':
            if ($acknowledge) {
               $shortstate = 'redblue';
            } else {
               $shortstate = 'red';
            }
            break;

         case 'WARNING':
         case 'RECOVERY':
         case 'FLAPPING':
            if ($acknowledge) {
               $shortstate = 'orangeblue';
            } else {
               $shortstate = 'orange';
            }
            break;

         case 'UNKNOWN':
         case '':
            if ($acknowledge) {
               $shortstate = 'yellowblue';
            } else {
               $shortstate = 'yellow';
            }
            break;

      }
      if ($state == 'WARNING'
         && $event == '') {
         if ($acknowledge) {
            $shortstate = 'yellowblue';
         } else {
            $shortstate = 'yellow';
         }
      }
      if ($state_type == 'SOFT') {
         $shortstate.= '_soft';
      }
      return $shortstate;
   }

   static function displayHostLine($data) {
      global $DB,$CFG_GLPI;

      $paHost = new PluginAlignakComputer();
      $paHost->getFromDB($data["id"]);

      $shortstate = self::getState($data['state'],
         $data['state_type'],
         $data['output'],
         $data['is_acknowledged']);
      /*
            $alt = __('Ok', 'monitoring');
            if ($shortstate == 'orange') {
               $alt = __('Warning (data)', 'monitoring');
            } else if ($shortstate == 'yellow') {
               $alt = __('Warning (connection)', 'monitoring');
            } else if ($shortstate == 'red') {
               $alt = __('Critical', 'monitoring');
            } else if ($shortstate == 'redblue'
                    || $shortstate == 'orangeblue'
                    || $shortstate == 'yellowblue') {
               $alt = __('Critical / Acknowledge', 'monitoring');
            }
      */

      echo "<td>";
      $entity = new Entity();
      $entity->getFromDB($data["entities_id"]);
      echo $entity = $entity->getName();
      echo "</td>";

      echo "<td>";
      $itemtype = $data['itemtype'];
      $item = new $itemtype();
      $item->getFromDB($data['items_id']);
      $link = $CFG_GLPI['root_doc'].
         "/plugins/alignak/front/service.php?hidesearch=1&reset=reset".
         "&field[0]=1&searchtype[0]=equals&contains[0]=".$data['items_id'].
         "&itemtype=PluginMonitoringService&start=0'";
      echo '<a href="'.$link.'" title="'.$item->getName().'">'.$item->getName()."</a>";
      echo "&nbsp;".$paHost->getComments();
      echo "</td>";

      echo "<td class='center'>";
      echo "<div class='page foldtl resource".$data['state']."'>";
      echo "<div style='vertical-align:middle;'>";
      echo "<span>";
      echo $data['state'];
      echo "</span>";
      if ($shortstate == 'red'
         || $shortstate == 'yellow'
         || $shortstate == 'orange') {
         if (Session::haveRight("plugin_alignak_actions", READ)) {
            echo "<span>&nbsp;";
            echo "<a href='".$CFG_GLPI['root_doc']."/plugins/alignak/front/acknowledge.form.php?host=".$data['host_name']."&id=".$data['idComputer']."'>"
               ."<img src='".$CFG_GLPI['root_doc']."/plugins/alignak/pics/acknowledge_checked.png'"
               ." alt='".htmlspecialchars(__('Add an acknowledge for the host and all faulty services of the host', 'monitoring'), ENT_QUOTES)."'"
               ." title='".htmlspecialchars(__('Add an acknowledge for the host and all faulty services of the host', 'monitoring'), ENT_QUOTES)."'/>"
               ."</a>";
            echo "</span>";
         }
      }
      echo "</div>";
      echo "</div>";
      echo "</td>";

      if (isset($data['host_command_name'])) {
         $scriptName=$CFG_GLPI['root_doc']."/plugins/alignak/scripts/".$data['host_command_command'];
         $scriptArgs=$data['host_name']." ".$data['ip'];

         echo "<td class='center'>";
         echo "<form name='form' method='post' 
            action='".$CFG_GLPI['root_doc']."/plugins/alignak/scripts/".$data['host_command_command'].".php'>";

         echo "<input type='hidden' name='host_id' value='".$data['idComputer']."' />";
         echo "<input type='hidden' name='host_name' value='".$data['host_name']."' />";
         echo "<input type='hidden' name='host_ip' value='".$data['ip']."' />";
         echo "<input type='hidden' name='host_state' value='".$data['state']."' />";
         echo "<input type='hidden' name='host_statetype' value='".$data['state_type']."' />";
         echo "<input type='hidden' name='host_event' value='".$data['event']."' />";
         echo "<input type='hidden' name='host_perfdata' value='".$data['perf_data']."' />";
         echo "<input type='hidden' name='host_last_check' value='".$data['last_check']."' />";
         echo "<input type='hidden' name='glpi_users_id' value='".$_SESSION['glpiID']."' />";

         echo "<input type='submit' name='host_command' value=\"".$data['host_command_name']."\" class='submit'>";
         Html::closeForm();

         echo "</td>";
      }

      echo "<td class='center'>";
      echo "<div class='page foldtl resource".$data['services_state']."'>";
      echo "<div style='vertical-align:middle;'>";
      echo "<span>";
      if (!empty($data['host_services_status'])) {
         $data['services_state'] = __('Ko', 'monitoring');
      } else {
         $data['services_state'] = __('Ok or Ack', 'monitoring');
      }
      if (Session::haveRight("dashboard_all_ressources", READ)) {
         $link = $CFG_GLPI['root_doc'].
            "/plugins/alignak/front/service.php?hidesearch=1&reset=reset".
            "&field[0]=1&searchtype[0]=equals&contains[0]=".$data['items_id'].
            "&itemtype=PluginMonitoringService&start=0'";

         echo '<a href="'.$link.'" title="'.$data['host_services_status'].'">'.$data['services_state']."</a>";
      } else {
         echo '<span title="'.$data['host_services_status'].'">'.$data['services_state']."</span>";
      }
      echo "</span>";
      if (!empty($data['host_services_status'])) {
         if (Session::haveRight("plugin_alignak_actions", READ)) {
            echo "<span>&nbsp;";
            echo "<a href='".$CFG_GLPI['root_doc']."/plugins/alignak/front/acknowledge.form.php?host=".$data['host_name']."&allServices&id=".$data['idComputer']."'>"
               ."<img src='".$CFG_GLPI['root_doc']."/plugins/alignak/pics/acknowledge_checked.png'"
               ." alt='".htmlspecialchars(__('Add an acknowledge for all faulty services of the host', 'monitoring'), ENT_QUOTES)."'"
               ." title='".htmlspecialchars(__('Add an acknowledge for all faulty services of the host', 'monitoring'), ENT_QUOTES)."'/>"
               ."</a>";
            echo "</span>";
         }
      }
      echo "</div>";
      echo "</div>";
      echo "</td>";

      echo "<td>";
      echo $data['ip'];
      echo "</td>";

      echo "<td>";
      echo Html::convDate($data['last_check']).' '. substr($data['last_check'], 11, 8);
      echo "</td>";

      echo "<td>";
      echo $data['output'];
      echo "</td>";

      echo "<td>";
      echo $data['perf_data'];
      echo "</td>";

      echo "<td>";
      if ($shortstate == 'redblue'
         || $shortstate == 'orangeblue'
         || $shortstate == 'yellowblue') {
         echo "<i>"._n('User', 'Users', 1)." : </i>";
         $user = new User();
         $user->getFromDB($data['acknowledge_users_id']);
         echo $user->getName(1);
         echo "<br/>";
         echo"<i>". __('Comments')." : </i>";
         if (Session::haveRight("plugin_alignak_actions", READ)) {
            echo "<a href='".$CFG_GLPI['root_doc']."/plugins/alignak/front/acknowledge.form.php?host=".$data['host_name']."&form=".$data['idComputer']."' title='".htmlspecialchars(__('Modify acknowledge comment for the host', 'monitoring'), ENT_QUOTES)."'>";
            echo $data['acknowledge_comment']."</a>";
         } else {
            echo $data['acknowledge_comment'];
         }
      }
      echo "</td>";
   }


   /**
    * Display list of hosts
    */
   static function showHostsBoard($width = '', $limit = '') {
      global $DB,$CFG_GLPI;

      if (! isset($_GET['order'])) {
         $_GET['order'] = "ASC";
      }
      if (! isset($_GET['sort'])) {
         $_GET['sort'] = "";
      }

      $order = "ASC";
      if (isset($_GET['order'])) {
         $order = $_GET['order'];
      }
      $where = '';
      if (isset($_GET['field'])) {
         foreach ($_GET['field'] as $key=>$value) {
            $wheretmp = '';
            if (isset($_GET['link'][$key])) {
               $wheretmp.= " ".$_GET['link'][$key]." ";
            }
            $wheretmp .= Search::addWhere(
               "",
               0,
               "v",
               $_GET['field'][$key],
               $_GET['searchtype'][$key],
               $_GET['contains'][$key]);
            if (!strstr($wheretmp, "``.``")) {
               if ($where != ''
                  AND !isset($_GET['link'][$key])) {
                  $where .= " AND ";
               }
               $where .= $wheretmp;
            }
         }
      }
      if ($where != '') {
         $where = "(".$where;
         $where .= ") AND ";
      }
      $where .= " `glpi_computers`.`entities_id` IN (".$_SESSION['glpiactiveentities_string'].")";

      if ($where != '') {
         $where = " WHERE ".$where;
         $where = str_replace("`".getTableForItemType("PluginAlignakBoard")."`.", "", $where);
      }

      $leftjoin = " 
         LEFT JOIN `glpi_computers`
            ON `glpi_plugin_alignak_computers`.`items_id` = `glpi_computers`.`id`
               AND `glpi_plugin_alignak_computers`.`itemtype`='Computer'
         LEFT JOIN `glpi_entities`
            ON `glpi_computers`.`entities_id` = `glpi_entities`.`id`";

      // * ORDER
      $ORDERQUERY = "ORDER BY entity_name ASC, host_name ASC";
      /*      $toview = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
      $toviewComplete = array(
         'ITEM_0' => 'entity_name',
         'ITEM_1' => 'host_name',
         'ITEM_2' => 'host_state',
         'ITEM_3' => 'service_state',
         'ITEM_4' => 'last_check',
         'ITEM_5' => 'event',
         'ITEM_6' => 'perf_data',
         'ITEM_7' => 'is_acknowledged'
      );
      foreach ($toview as $key => $val) {
         if ($_GET['sort']==$val) {
            $ORDERQUERY = Search::addOrderBy("PluginAlignakComputer", $_GET['sort'], $_GET['order'], $key);
            foreach ($toviewComplete as $keyi=>$vali) {
               $ORDERQUERY= str_replace($keyi, $vali, $ORDERQUERY);
            }
         }
      }*/

      //            `glpi_computers`.*

      $query = "SELECT
            `glpi_entities`.`name` AS entity_name,
            `glpi_computers`.`id` AS idComputer, 
            `glpi_computers`.`name` AS host_name,
            `glpi_plugin_alignak_computers`.*,
            `glpi_plugin_alignak_computers`.`state` AS host_state
         FROM `glpi_plugin_alignak_computers`
         ".$leftjoin."
         ".$where."
         ".$ORDERQUERY;
      // Toolbox::logInFile("pm", "Query hosts - $query\n");

      $result = $DB->query($query);

      if (! isset($_GET["start"])) {
         $_GET["start"]=0;
      }
      $start=$_GET['start'];
      if (! isset($_GET["order"])) {
         $_GET["order"]="ASC";
      }

      $numrows = $DB->numrows($result);
      $parameters = '';

      $globallinkto = '';

      $parameters = "sort=".$_GET['sort']."&amp;order=".$_GET['order'].$globallinkto;
      Html::printPager($_GET['start'], $numrows, $CFG_GLPI['root_doc']."/plugins/alignak/front/host.php", $parameters);

      $limit = $numrows;
      if ($_SESSION["glpilist_limit"] < $numrows) {
         $limit = $_SESSION["glpilist_limit"];
      }
      $query .= " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Query hosts - $query\n");
      $result = $DB->query($query);

      echo '<div id="custom_date" style="display:none"></div>';
      echo '<div id="custom_time" style="display:none"></div>';

      if ($width == '') {
         echo "<table class='tab_cadrehov' style='width:100%;'>";
      } else {
         echo "<table class='tab_cadrehov' style='width:".$width."px;'>";
      }
      $num = 0;

      /*
      if (Session::haveRight("plugin_alignak_alignak", READ)) {
         // Host test command ...
         $pmCommand = new PluginMonitoringCommand();
         $a_commands = array();
         $a_list = $pmCommand->find("command_name LIKE 'host_action'");
         foreach ($a_list as $data) {
            $host_command_name = $data['name'];
            $host_command_command = $data['command_line'];
         }
      }*/

      echo "<tr class='tab_bg_1'>";
      PluginAlignakBoard::showHeaderItem(__('Entity'), 0, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      //      PluginAlignakBoard::showHeaderItem(__('Type'), 0, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      PluginAlignakBoard::showHeaderItem(__('Host', 'monitoring'), 1, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      PluginAlignakBoard::showHeaderItem(__('Host state'), 2, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      if (isset($host_command_name)) {
         echo '<th>'.__('Host action', 'monitoring').'</th>';
      }
      echo '<th>'.__('Host resources state', 'monitoring').'</th>';
      echo '<th>'.__('IP address', 'monitoring').'</th>';
      PluginAlignakBoard::showHeaderItem(__('Last check', 'monitoring'), 4, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      PluginAlignakBoard::showHeaderItem(__('Result details', 'monitoring'), 5, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      PluginAlignakBoard::showHeaderItem(__('Performance data', 'monitoring'), 6, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      PluginAlignakBoard::showHeaderItem(__('Acknowledge', 'monitoring'), 7, $num, $start, $globallinkto, 'host.php', 'PluginAlignakComputer');
      echo "</tr>";

      while ($data=$DB->fetch_array($result)) {
         // Reduced array or not ?
         //         if ($_SESSION['plugin_monitoring']['reduced_interface'] and $data['state'] == 'UP') continue;

         /*
         if (isset($host_command_name)) {
            $data['host_command_name'] = $host_command_name;
            $data['host_command_command'] = $host_command_command;
         }
         */

         /*
         // Get all host services except if state is ok or is already acknowledged ...
         $data['host_services_status'] = '';
         $data['services_state'] = 'OK';
         $query2 = "SELECT
            `glpi_plugin_monitoring_services`.*
            FROM `glpi_plugin_monitoring_componentscatalogs_hosts`
            INNER JOIN `glpi_plugin_monitoring_services`
               ON (`glpi_plugin_monitoring_services`.`plugin_monitoring_componentscatalogs_hosts_id` = `glpi_plugin_monitoring_componentscatalogs_hosts`.`id`)
            WHERE  `glpi_plugin_monitoring_componentscatalogs_hosts`.`items_id` = '". $data['idComputer'] ."'
               AND `glpi_plugin_monitoring_componentscatalogs_hosts`.`itemtype` = 'Computer'
               AND `glpi_plugin_monitoring_services`.`state` != 'OK'
               AND `glpi_plugin_monitoring_services`.`is_acknowledged` = '0'
            ORDER BY `glpi_plugin_monitoring_services`.`name` ASC;";
         // Toolbox::logInFile("pm", "Query services for host : ".$data['idComputer']." : $query2\n");
         $result2 = $DB->query($query2);
         if ($DB->numrows($result2) > 0) {
            $data['host_services_status'] = '';
            while ($data2=$DB->fetch_array($result2)) {
               // Toolbox::logInFile("pm", "Service ".$data2['name']." is ".$data2['state'].", state : ".$data2['event']."\n");
               if (! empty($data['host_services_status'])) $data['host_services_status'] .= "\n";
               $data['host_services_status'] .= "Service ".$data2['name']." is ".$data2['state'].", event : ".$data2['event'];
               if ($data2['state'] == 'CRITICAL') {
                  // Do nothing
               } else {
                  $data['services_state'] = $data2['state'];
               }
            }
         }
         */
         $data['is_acknowledged'] = '0';
         $data['host_services_status'] = 'OK';
         $data['services_state'] = 'OK';

         // Get host first IP address
         $data['ip'] = __('Unknown IP address', 'monitoring');
         $queryIp = "SELECT `glpi_ipaddresses`.`name` 
                FROM `glpi_ipaddresses` 
                LEFT JOIN `glpi_networknames` ON `glpi_ipaddresses`.`itemtype`='NetworkName' 
                  AND `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id` 
                LEFT JOIN `glpi_networkports` ON `glpi_networknames`.`itemtype`='NetworkPort' 
                  AND `glpi_networknames`.`items_id`=`glpi_networkports`.`id` 
                WHERE `glpi_networkports`.`itemtype`='Computer' 
                  AND `glpi_networkports`.`items_id`='".$data['idComputer']."' LIMIT 1";
         $resultIp = $DB->query($queryIp);
         if ($DB->numrows($resultIp) > 0) {
            $dataIp=$DB->fetch_array($resultIp);
            $data['ip'] = $dataIp['name'];
         }

         echo "<tr class='tab_bg_3'>";
         PluginAlignakBoard::displayHostLine($data);
         echo "</tr>";
      }
      echo "</table>";
      echo "<br/>";
      Html::printPager($start, $numrows, $CFG_GLPI['root_doc']."/plugins/alignak/front/host.php", $parameters);
   }

   /**
    * Display list of counters
    */
   static function showCountersBoard($width = '', $limit = '') {
      global $DB,$CFG_GLPI;

   }
}
