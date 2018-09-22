<?php
class PluginAlignakMenu extends CommonGLPI
{
   static $rightname = 'plugin_alignak_configuration';

   /**
    * Get menu name
    *
    * @return string - menu name
    **/
   static function getMenuName() {
      return __("Alignak monitoring", "alignak");
   }

   /**
    * get menu content
    *
    * Do not use this function if you intend to have some breadcrumbs menu for the plugin objects
    * This function is onlyt interesting for some extra items in the Glpi main menu
    *
    * @return array for menu content
    **/

   /**
    * Get additional menu options and breadcrumb
    *
    * @global array $CFG_GLPI
    * @return array
    */
   static function getAdditionalMenuOptions() {
      global $CFG_GLPI;

      $elements = [
         'config' => 'PluginAlignakConfig',
         'alignak_entity' => 'PluginAlignakEntity',
         'alignak' => 'PluginAlignakAlignak',
         'mail_notification' => 'PluginAlignakMailNotification',
         'monitoring_template' => 'PluginAlignakMonitoringTemplate',
         'computer_counters_template' => 'PluginAlignakCountersTemplate',
         'counters_template' => 'PluginAlignakCountersTemplate',
         'counter' => 'PluginAlignakCounter'
      ];
      // List of the elements which must have some breadcrumb items
      $options = [];

      $options['menu']['title'] = self::getTypeName();
      $options['menu']['page']  = self::getSearchURL(false);
      if (Session::haveRight('plugin_alignak_configuration', READ)) {
         $options['menu']['links']['config']  = PluginAlignakConfig::getFormURL(false);
      }
      foreach ($elements as $type => $itemtype) {
         $options[$type]['title'] = $itemtype::getTypeName();
         $options[$type]['page']  = $itemtype::getSearchURL(false);
         $options[$type]['links']['search'] = $itemtype::getSearchURL(false);
         if ($itemtype::canCreate()) {
            $options[$type]['links']['add'] = $itemtype::getFormURL(false);
         }
         if (Session::haveRight('plugin_alignak_configuration', UPDATE)) {
            $options[$type]['links']['config']  = PluginAlignakConfig::getFormURL(false);
         }
      }
      // hack for config
      $options['config']['page'] = PluginAlignakConfig::getFormURL(false);

      // Add icon for documentation
      $img = Html::image($CFG_GLPI["root_doc"] . "/plugins/alignak/pics/books.png",
         ['alt' => __('Import', 'alignak')]);
      $options['menu']['links'][$img] = '/plugins/alignak/front/documentation.php';

      return $options;
   }

   /**
    * Display the menu page of the plugin
    *
    * @global array $CFG_GLPI
    * @param string $type
    */
   static function displayMenu($type = "big") {
      global $CFG_GLPI;

      $dbu = new DbUtils();

      echo '<div style="width: 100%; margin: 0 auto; border-bottom: 3px ridge">';
      echo '<table width="90%">';
      echo '<tr>';

      // First column - empty now !
      echo '<td>';
      echo '<div style="height: 64px">';
      echo '<a href="http://alignak.net">';
      echo '<img style="height: 64px" src="'. $CFG_GLPI['root_doc'] . '/plugins/alignak/pics/alignak_blue_logo.png">';
      echo '</a>';
      echo '</div>';
      echo '</td>';

      echo '<td>';
      echo '<h1>'. __("Plugin Alignak monitoring, version: ", "alignak") . PLUGIN_ALIGNAK_VERSION .'</h1>';
      if (PLUGIN_ALIGNAK_OFFICIAL_RELEASE == 0) {
         echo '<h2>'. __("Beta version!", "alignak") .'</h2>';
      }
      echo '</td>';

      echo '</tr>';
      echo '</table>';

      // Check if the entities configuration looks ok
      /*
      $pfEntity = new PluginAlignakEntity();
      if (strlen($pfEntity->getValue('agent_base_url', 0)) < 10
         && !strstr($_SERVER['PHP_SELF'], 'front/config.form.php')) {
         echo "<div class='msgboxmonit msgboxmonit-red'>";
         print "<center><a href=\"".$CFG_GLPI['root_doc']."/front/entity.form.php?id=0&forcetab=PluginFusioninventoryEntity$0\">";
         print __('The server needs to know the URL the agents use to access the server. Please '.
            'configure it in the General Configuration page.', 'fusioninventory');
         print "</a></center>";
         echo "</div>";
         exit;
      }*/

      // Check if cron tasks are running
      /*
      $cronTask = new CronTask();
      $cronTask->getFromDBbyName('PluginAlignakTask', 'taskscheduler');
      if ($cronTask
         AND ($cronTask->fields['lastrun'] == ''
            OR strtotime($cronTask->fields['lastrun']) < strtotime("-3 day"))) {
         $message = __('GLPI cron not running, see ', 'fusioninventory');
         $message .= " <a href='http://fusioninventory.org/documentation/fi4g/cron.html'>".__('documentation', 'fusioninventory')."</a>";
         Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
      }*/

      echo '</div>';

      echo '<div align="center" style="height: 35px; display: inline-block; width: 100%; margin: 0 auto;">';
      echo '<table width="90%"">';

      echo '<tr>';

      // First column - empty now !
      echo '<td width="30%">';
      echo '</td>';

      // Second column - link to plugin items!
      echo '<td>';

      if (Session::haveRight("plugin_alignak_alignak", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Alignak", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Configure Alignak entities", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         $nb = $dbu->countElementsInTable(PluginAlignakEntity::getTable());
         echo '<li>'. "<sup class='tab_nb'>$nb</sup>" .'<a href="entity.php">'.  __("Alignak entities", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';

         echo '<div style="margin-top: 5px;">';
         echo '<small><em>'. __("Configure Alignak instances", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         $nb = $dbu->countElementsInTable(PluginAlignakAlignak::getTable());
         echo '<li>'. "<sup class='tab_nb'>$nb</sup>" .'<a href="alignak.php">'.  __("Alignak instances", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';

         echo '<div style="margin-top: 5px;">';
         echo '<small><em>'. __("Configure monitoring templates", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         $nb = $dbu->countElementsInTable(PluginAlignakMonitoringTemplate::getTable());
         echo '<li>'. "<sup class='tab_nb'>$nb</sup>" .'<a href="monitoringtemplate.php">'.  __("Alignak monitoring templates", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';
         echo '<hr>';
      }

      if (Session::haveRight("plugin_alignak_counters", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Counters", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Configure counters and counters templates", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="counterstemplate.php">'.  __("Counters templates", "alignak") .'</a></li>';
         echo '<li><a href="counter.php">'.  __("Counters", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';
         echo '<hr>';
      }

      if (Session::haveRight("plugin_alignak_dashboard", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Dashboard!", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Dashboards", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="dashboard.php">'.  __("Dashboards", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';
         echo '<hr>';
      }

      if (Session::haveRight("plugin_alignak_mailnotification", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Notifications", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Configure counters mail notifications", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="mailnotification.php">'.  __("Mail notifications", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';
         echo '<hr>';
      }

      echo '</td>';
      echo '</tr>';
      echo '</table>';

      echo '</div>';
   }

   /**
    * Display list of hosts
    */
   function showHostsBoard($width='', $limit='') {
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
      $where .= " CONCAT_WS('', 
      `glpi_computers`.`entities_id`, 
      `glpi_printers`.`entities_id`, 
      `glpi_networkequipments`.`entities_id`) IN (".$_SESSION['glpiactiveentities_string'].")";

      if ($where != '') {
         $where = " WHERE ".$where;
         $where = str_replace("`".getTableForItemType("PluginMonitoringDisplay")."`.",
            "", $where);

      }

      $leftjoin = " 
         LEFT JOIN `glpi_computers`
            ON `glpi_plugin_alignak_computers`.`items_id` = `glpi_computers`.`id`
               AND `glpi_plugin_alignak_computers`.`itemtype`='Computer'
         LEFT JOIN `glpi_printers`
            ON `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_printers`.`id`
               AND `glpi_plugin_monitoring_hosts`.`itemtype`='Printer'
         LEFT JOIN `glpi_networkequipments`
            ON `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_networkequipments`.`id`
               AND `glpi_plugin_monitoring_hosts`.`itemtype`='NetworkEquipment'
         LEFT JOIN `glpi_entities`
            ON CONCAT_WS('', `glpi_computers`.`entities_id`, 
            `glpi_printers`.`entities_id`, 
            `glpi_networkequipments`.`entities_id`) = `glpi_entities`.`id`
               
      ";

      // * ORDER
      $ORDERQUERY = "ORDER BY entity_name ASC, host_name ASC";
      $toview = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
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
            $ORDERQUERY = Search::addOrderBy("PluginMonitoringHost", $_GET['sort'],
               $_GET['order'], $key);
            foreach ($toviewComplete as $keyi=>$vali) {
               $ORDERQUERY= str_replace($keyi, $vali, $ORDERQUERY);
            }
         }
      }

//            `glpi_computers`.*

      $query = "SELECT
            `glpi_entities`.`name` AS entity_name,
            CONCAT_WS('', `glpi_computers`.`id`, `glpi_printers`.`id`, `glpi_networkequipments`.`id`) AS idComputer, 
            CONCAT_WS('', `glpi_computers`.`name`, `glpi_printers`.`name`, `glpi_networkequipments`.`name`) AS host_name,
            `glpi_plugin_monitoring_hosts`.*,
            `glpi_plugin_monitoring_hosts`.`state` AS host_state, 
            `glpi_plugin_monitoring_hosts`.`is_acknowledged` AS host_acknowledged
         FROM `glpi_plugin_monitoring_hosts`
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
      Html::printPager($_GET['start'], $numrows, $CFG_GLPI['root_doc']."/plugins/monitoring/front/host.php", $parameters);

      $limit = $numrows;
      if ($_SESSION["glpilist_limit"] < $numrows) {
         $limit = $_SESSION["glpilist_limit"];
      }
      $query .= " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      // Toolbox::logInFile("pm", "Query hosts - $query\n");
      $result = $DB->query($query);

      echo '<div id="custom_date" style="display:none"></div>';
      echo '<div id="custom_time" style="display:none"></div>';

      if ($width == '') {
         echo "<table class='tab_cadrehov' style='width:100%;'>";
      } else {
         echo "<table class='tab_cadrehov' style='width:".$width."px;'>";
      }
      $num = 0;

      if (PluginMonitoringProfile::haveRight("host_command", 'r')) {
         // Host test command ...
         $pmCommand = new PluginMonitoringCommand();
         $a_commands = array();
         $a_list = $pmCommand->find("command_name LIKE 'host_action'");
         foreach ($a_list as $data) {
            $host_command_name = $data['name'];
            $host_command_command = $data['command_line'];
         }
      }

      echo "<tr class='tab_bg_1'>";
      $this->showHeaderItem(__('Entity'), 0, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Type'), 0, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Host', 'monitoring'), 1, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Host state'), 2, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      if (isset($host_command_name)) {
         echo '<th>'.__('Host action', 'monitoring').'</th>';
      }
      echo '<th>'.__('Host resources state', 'monitoring').'</th>';
      echo '<th>'.__('IP address', 'monitoring').'</th>';
      $this->showHeaderItem(__('Last check', 'monitoring'), 4, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Result details', 'monitoring'), 5, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Performance data', 'monitoring'), 6, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      $this->showHeaderItem(__('Acknowledge', 'monitoring'), 7, $num, $start, $globallinkto, 'host.php', 'PluginMonitoringHost');
      echo "</tr>";

      while ($data=$DB->fetch_array($result)) {
         // Reduced array or not ?
         if ($_SESSION['plugin_monitoring']['reduced_interface'] and $data['state'] == 'UP') continue;

         if (isset($host_command_name)) {
            $data['host_command_name'] = $host_command_name;
            $data['host_command_command'] = $host_command_command;
         }

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

         // Get host first IP address
         $data['ip'] = __('Unknown IP address', 'monitoring');
         $queryIp = "SELECT `glpi_ipaddresses`.`name` FROM `glpi_ipaddresses` LEFT JOIN `glpi_networknames` ON `glpi_ipaddresses`.`itemtype`='NetworkName' AND `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id` LEFT JOIN `glpi_networkports` ON `glpi_networknames`.`itemtype`='NetworkPort' AND `glpi_networknames`.`items_id`=`glpi_networkports`.`id` WHERE `glpi_networkports`.`itemtype`='Computer' AND `glpi_networkports`.`items_id`='".$data['idComputer']."' LIMIT 1";
         $resultIp = $DB->query($queryIp);
         if ($DB->numrows($resultIp) > 0) {
            $dataIp=$DB->fetch_array($resultIp);
            $data['ip'] = $dataIp['name'];
         }

         echo "<tr class='tab_bg_3'>";
         $this->displayHostLine($data);
         echo "</tr>";
      }
      echo "</table>";
      echo "<br/>";
      Html::printPager($start, $numrows, $CFG_GLPI['root_doc']."/plugins/monitoring/front/host.php", $parameters);
   }
}
