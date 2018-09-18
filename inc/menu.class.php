<?php
class PluginAlignakMenu extends CommonGLPI
{
   static $rightname = 'plugin_alignak_config';

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
         'alignak' => 'PluginAlignakAlignak',
         'mail_notification' => 'PluginAlignakMailNotification',
         'monitoring_template' => 'PluginAlignakMonitoringTemplate',
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

      if (Session::haveRight("config", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Dashboard!", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Dashboards", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="dashboard.php">'.  __("Dashboards", "alignak") .'</a></li>';
         echo '</ul>';
         echo '</div>';
         echo '<hr>';
      }

      if (Session::haveRight("config", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Alignak", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Configure Alignak instances", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="alignak.php">'.  __("Alignak instances", "alignak") .'</a></li>';
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

      if (Session::haveRight("plugin_alignak_monitoring", READ)) {
         echo '<div style="margin-top: 5px;">';
         echo '<strong>'. __("Monitoring templates", "alignak") .'</strong><br/>';
         echo '<small><em>'. __("Configure monitoring templates", "alignak") .'</em></small><br/>';
         echo '<ul style="margin-left: 5px;">';
         echo '<li><a href="monitoringtemplate.php">'.  __("Monitoring templates", "alignak") .'</a></li>';
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
}
