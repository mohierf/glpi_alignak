<?php

/*
   ------------------------------------------------------------------------
   Plugin Kiosks for GLPI
   Copyright (C) 2011-2015 IPM France SAS

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAlignakDashboard extends CommonDBTM {

   static $rightname = 'plugin_alignak_dashboard';

   static function getTypeName($nb = 0) {
      return _n('Dashboard configuration', 'Dashboard configurations', $nb, 'alignak');
   }

   function getSearchOptions() {
      $tab = [];
      $i = 1;

      $tab['common'] = self::getTypeName();

      $i=1;
      $tab[$i]['table']           = $this->getTable();
      $tab[$i]['field']           = 'name';
      $tab[$i]['linkfield']       = 'name';
      $tab[$i]['name']            = __('Name');
      $tab[$i]['datatype']        = 'itemlink';

      $i++;
      $tab[$i]['table']          = $this->getTable();
      $tab[$i]['field']          = 'clients_id';
      $tab[$i]['name']           = __('Client', 'alignak');
      $tab[$i]['datatype']       = 'specific';

      $i++;
      $tab[$i]['table']          = $this->getTable();
      $tab[$i]['field']          = 'plugin_alignak_mail_notifications_id';
      $tab[$i]['name']           = __('Mail notification', 'alignak');
      $tab[$i]['datatype']       = 'specific';

      $i++;
      $tab[$i]['table']          = 'glpi_entities';
      $tab[$i]['field']          = 'name';
      $tab[$i]['name']           = __('Client entity', 'alignak');
      $tab[$i]['datatype']       = 'specific';

      $i++;
      $tab[$i]['table']           = $this->getTable();
      $tab[$i]['field']           = 'is_active';
      $tab[$i]['name']            = __('Is active ?', 'alignak');
      $tab[$i]['datatype']        = 'bool';

      return $tab;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      if ($field == 'clients_id') {
         if ($values[$field] != -1) {
            $item = new Entity();
            $item->getFromDB($values[$field]);
            return $item->getLink();
         } else {
            return " ";
         }
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   function defineTabs($options = []) {
      $ong = [];
      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            if (self::canView()) {
               return [1 => __('DashKiosk', 'alignak')];
            } else {
               return '';
            }
            break;

         case 'Central' :
            break;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            $paDashboard = new PluginAlignakDashboard();
            // Show form from entity Id
            $paDashboard->showForm(-1, $item->getID(), ['canedit'=>self::canUpdate(), 'colspan'=>4 ]);
            break;
      }
      return true;
   }

   /**
   * Set default content
   */
   function setDefaultContent($users_id = -1) {
      // $this->fields["id"]             = -1;
      $this->fields["name"]           = "";

      // Pages
      $this->fields["page_counters"]                = 1;
      $this->fields["page_counters_refresh"]        = 0;
      $this->fields["page_monitoring"]              = 1;
      $this->fields["page_monitoring_refresh"]      = 0;
      $this->fields["page_map"]                     = 1;
      $this->fields["page_map_refresh"]             = 0;
      $this->fields["page_tree"]                    = 1;
      $this->fields["page_tree_refresh"]            = 0;
      $this->fields["page_tickets"]                 = 1;
      $this->fields["page_tickets_refresh"]         = 0;
      $this->fields["page_groups"]                  = 1;
      $this->fields["page_groups_refresh"]          = 0;
      $this->fields["page_alignak"]                  = 1;
      $this->fields["page_alignak_refresh"]          = 0;
      $this->fields["page_services"]                = 1;
      $this->fields["page_services_refresh"]        = 0;
      $this->fields["page_daily_counters"]          = 1;
      $this->fields["page_daily_counters_refresh"]  = 0;
      $this->fields["page_availability"]            = 1;
      $this->fields["page_availability_refresh"]    = 0;
      $this->fields["page_payments"]                = 1;
      $this->fields["page_payments_refresh"]        = 0;

      // Main counters page
      $this->fields["page_counters_main"]                   = 1;
      $this->fields["page_counters_main_refresh"]           = 0;
      $this->fields["page_counters_main_collapsed"]         = 0;
      $this->fields["page_counters_barcharts"]              = 1;
      $this->fields["page_counters_barcharts_refresh"]      = 0;
      $this->fields["page_counters_barcharts_collapsed"]    = 0;
      $this->fields["page_counters_helpdesk"]               = 1;
      $this->fields["page_counters_helpdesk_refresh"]       = 0;
      $this->fields["page_counters_helpdesk_collapsed"]     = 0;
      $this->fields["page_counters_geotraffic"]             = 1;
      $this->fields["page_counters_geotraffic_refresh"]     = 0;
      $this->fields["page_counters_geotraffic_collapsed"]   = 0;

      // Monitoring counters page
      $this->fields["page_monitoring_minemap"]              = 1;
      $this->fields["page_monitoring_minemap_refresh"]      = 0;
      $this->fields["page_monitoring_minemap_collapsed"]    = 0;
      $this->fields["page_monitoring_alignak"]               = 1;
      $this->fields["page_monitoring_alignak_refresh"]       = 0;
      $this->fields["page_monitoring_alignak_collapsed"]     = 0;
      $this->fields["page_monitoring_services"]             = 1;
      $this->fields["page_monitoring_services_refresh"]     = 0;
      $this->fields["page_monitoring_services_collapsed"]   = 0;
   }

   /**
     * Get value of config
     *
     * @global object $DB
     * @param value $name field name
     * @param integer $clients_id
     *
     * @return value of field
     */
   function getValueAncestor($name, $clients_id) {
      global $DB;

      $entities_ancestors = getAncestorsOf("glpi_entities", $clients_id);

      $nbentities = count($entities_ancestors);
      for ($i=0; $i<$nbentities; $i++) {
         $entity = array_pop($entities_ancestors);
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `clients_id`='".$entity."'
               AND `".$name."` IS NOT NULL
            LIMIT 1";
         $result = $DB->query($query);
         if ($DB->numrows($result) != 0) {
            $data = $DB->fetch_assoc($result);
            return $data[$name];
         }
      }
      // $this->getFromDB(1);
      return $this->getField($name);
   }

   /**
    * Get the value (of this entity or parent entity or in general config
    *
    * @global object $DB
    * @param value $name field name
    * @param integet $clients_id
    *
    * @return value value of this field
    */
   function getValue($name, $clients_id) {
      global $DB;

      $where = '';
      if ($name == 'agent_base_url') {
         $where = "AND `".$name."` != ''";
      }

      $query = "SELECT `".$name."` FROM `".$this->getTable()."`
         WHERE `clients_id`='".$clients_id."'
            AND `".$name."` IS NOT NULL
            ".$where."
         LIMIT 1";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_assoc($result);
         return $data[$name];
      }
      return $this->getValueAncestor($name, $clients_id);
   }

   function getFieldHtml($name, $label, $clients_id = -1, $type = 'text', $unit = '') {
      echo "<td>";
      $value = $this->fields[$name];
      $inheritedValue = $this->getValueAncestor($name, $clients_id);
      echo $label.'&nbsp;';
      echo "</td>";
      echo "<td>";

      switch ($type) {

         case 'boolean':
            if (is_null($value) && empty($inheritedValue)) {
               $value = $this->fields[$name] = '0';
            }
            if (! is_null($value)) {
               if (self::canUpdate()) {
                  Dropdown::showYesNo($name, $this->fields[$name]);
               } else {
                  echo Dropdown::getYesNo($this->fields[$name]);
               }
            } else if (! empty($inheritedValue)) {
               echo '<i class="green">&nbsp;'.__('Inherited from parent entity')."&nbsp;:&nbsp;".$inheritedValue.'</i>';
            }
            break;

         case 'integer':
            if (is_null($value) && empty($inheritedValue)) {
               $value = $this->fields[$name] = '0';
            }
            if (! is_null($value)) {
               if (self::canUpdate()) {
                  echo "<input type='text' name='$name' value='".$value."' size='10'/>";
               } else {
                  echo "<input type='text' name='$name' value='".$value."' size='10' readonly='1' disabled='1' />";
               }
            } else if (! empty($inheritedValue)) {
               echo '<i class="green">&nbsp;'.__('Inherited from parent entity')."&nbsp;:&nbsp;".$inheritedValue.'</i>';
            }
            break;

         default:
            if (is_null($value) && empty($inheritedValue)) {
               $value = $this->fields[$name] = '0';
            }
            if (! is_null($value)) {
               if (self::canUpdate()) {
                  echo "<input type='text' name='$name' value='".$value."' size='80'/>";
               } else {
                  echo "<input type='text' name='$name' value='".$value."' size='80' readonly='1' disabled='1' />";
               }
            } else if (! empty($inheritedValue)) {
               echo '<i class="green">&nbsp;'.__('Inherited from parent entity')."&nbsp;:&nbsp;".$inheritedValue.'</i>';
            }
            break;
      }
      if (! empty($unit)) {
         echo "&nbsp;".$unit;
      }
      echo "</td>";
   }

   function showForm($items_id = -1, $clients_id = -1, $options = [], $copy = []) {
      global $DB,$CFG_GLPI;

      // echo "Dashkiosk id: $items_id, for client: $clients_id\n";

      if ($items_id != -1) {
         // Show dashboard by ID (not in entity tab form !)
         $this->getFromDB($items_id);
      } else {
         // Show dashboard parameters in entity tab form ...
         $a_confs = $this->find("`clients_id`='".$clients_id."'", "", 1);
         if (count($a_confs) > 0) {
            $a_conf = current($a_confs);
            $items_id = $a_conf['id'];
            $this->getFromDB($items_id);
         } else {
            // Add item
            $this->getEmpty();
            $this->setDefaultContent();
            $this->fields['clients_id'] = $clients_id;
         }
      }

      // echo "Dashkiosk id: $items_id, for client: $clients_id\n";

      if (count($copy) > 0) {
         // Copy item
         foreach ($copy as $key=>$value) {
            $this->fields[$key] = stripslashes($value);
         }
      }

      // $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr>";
      echo "<td>".__('Client', "alignak")."</td>";
      echo "<td colspan='7'>";
      $item = new Entity();
      if ($item->getFromDB($this->fields['clients_id'])) {
         echo $item->fields["name"];
      } else {
         echo __('No client defined for this Dashkisok configuration', 'alignak');
      }
      echo "<input type='hidden' name='clients_id' value='".$clients_id."' size='20'/>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>";
      echo __('Is active?', 'alignak').'&nbsp;';
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
      } else {
         echo Dropdown::getYesNo($this->fields['is_active']);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>".__('Name')."</td>";
      echo "<td colspan='7'>";
      if (! empty($this->fields["name"])) {
         echo "<input type='text' name='name' value='".$this->fields["name"]."' size='20'/>";
      } else {
         echo "<input type='text' name='name' value='". __('Dashboard ', 'alignak') . $this->fields["name"] ."' size='20'/>";
      }
      echo "</td>";
      echo "</tr>";

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="40" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      echo "<tr><td colspan=\"8\">";
      echo "<hr/>";
      echo "</td></tr>";

      // Navigation bar
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Navigation bar menu configuration: ', 'alignak')."</strong>";
      echo "</td></tr>";

      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('navbar_config', __('Application configuration', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('navbar_notif', __('Support notification', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('navbar_select', __('Kiosks selection', 'alignak'), $clients_id, 'boolean');
      echo "</tr>";

      echo "<tr><td colspan=\"8\">";
      echo "<hr/>";
      echo "</td></tr>";

      // Main pages
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Dashboard menu main pages configuration: ', 'alignak')."</strong>";
      echo "</td></tr>";

      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_counters', __('Counters page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      PluginAlignakDashboard::getFieldHtml('page_monitoring', __('Monitoring page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_map', __('Map page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_map_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      PluginAlignakDashboard::getFieldHtml('page_tree', __('Tree page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_tree_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_tickets', __('Tickets page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_tickets_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      PluginAlignakDashboard::getFieldHtml('page_groups', __('Groups page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_groups_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_alignak', __('Kiosks page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_alignak_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      PluginAlignakDashboard::getFieldHtml('page_services', __('Services page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_services_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_daily_counters', __('Daily counters page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_daily_counters_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      PluginAlignakDashboard::getFieldHtml('page_availability', __('Availability page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_availability_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_easyshare', __('Easyshare page', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_easyshare_refresh', __(', refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";

      // Kiosks services pages
      echo "<tr><td colspan=\"4\">";
      echo "<strong>".__('Dashboard menu services pages configuration: ', 'alignak')."</strong>";
      echo "</td></tr>";
      PluginAlignakDashboard::getFieldHtml(
            'page_payments',
            __('Payments page', 'alignak'),
            $clients_id,
            'boolean');
      // PluginAlignakDashboard::getFieldHtml('page_printing',       __('Printing page', 'alignak'), $clients_id, 'boolean');
      // PluginAlignakDashboard::getFieldHtml('page_rfid',           __('RFID page', 'alignak'), $clients_id, 'boolean');

      echo "<tr><td colspan=\"8\">";
      echo "<hr/>";
      echo "</td></tr>";

      echo "<tr><td colspan=\"8\">";

      echo "</td></tr>";

      // Pages configuration
      echo "<tr><td colspan=\"8\">";
      echo "<h1>".__('Pages configuration: ', 'alignak')."</strong>";
      echo "</td></tr>";

      // Monitoring counters page
      echo "<tr><td colspan=\"8\">";
      echo "<i>".__('Monitoring counters page configuration: ', 'alignak')."</i>";
      echo "</td></tr>";

      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_monitoring_minemap', __('Minemap', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_minemap_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_minemap_refresh', __('Minemap refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_monitoring_alignak', __('Kiosks', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_alignak_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_alignak_refresh', __('Kiosks refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_monitoring_services', __('Services', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_services_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_monitoring_services_refresh', __('Services refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";

      // Main counters page
      echo "<tr><td colspan=\"8\">";
      echo "<i>".__('Main counters page configuration: ', 'alignak')."</i>";
      echo "</td></tr>";

      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_counters_main', __('Main counters', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_main_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_main_refresh', __('Main counters refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_counters_barcharts', __('Barcharts', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_barcharts_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_barcharts_refresh', __('Barcharts refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_counters_helpdesk', __('Helpdesk', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_helpdesk_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_helpdesk_refresh', __('Helpdesk refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";
      echo "<tr>";
      PluginAlignakDashboard::getFieldHtml('page_counters_geotraffic', __('Geotraffic', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_geotraffic_collapsed', __('Panel collapsed', 'alignak'), $clients_id, 'boolean');
      PluginAlignakDashboard::getFieldHtml('page_counters_geotraffic_refresh', __('Geotraffic refresh period', 'alignak'), $clients_id, 'integer', __('seconds', 'alignak'));
      echo "</tr>";

      /*
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Main counters page components: ', 'alignak')."</strong>";
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>";
      echo "</td>";
      echo "<td>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_1',
                              'value'=>$this->fields['component_1']));
      echo "</td>";

      echo "<td>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_2',
                              'value'=>$this->fields['component_2']));
      echo "</td>";

      echo "<td>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_3',
                              'value'=>$this->fields['component_3']));
      echo "</td>";

      echo "<td>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_4',
                              'value'=>$this->fields['component_4']));
      echo "</td>";

      echo "<td>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_5',
                              'value'=>$this->fields['component_5']));
      echo "</td>";
      echo "<td>";
      echo "</td>";
      echo "</tr>";
      */

      $this->showFormButtons($options);

      Html::closeForm();

      // Dashboard counters
      $pkDashboardCounters = new PluginAlignakDashboardCounter();
      // $pkDashboardCounters->showCounters($items_id, $options);
      $pkDashboardCounters->showForm(-1, $items_id, $options);

      return true;
   }

   function convertPostdata($data) {
      $a_arguments = [];
      foreach ($data as $name=>$value) {
         if (strstr($name, "argument_")) {
            $name = str_replace("argument_", "", $name);
            $a_arguments[$name] = $value;
         }
      }
      $data['arguments'] = exportArrayToDB($a_arguments);

      $where = "`name`='".$data['name']."'";
      if (isset($data['id'])) {
         $where .= " AND `id` != '".$data['id']."'";
      }
      $num_com = countElementsInTable(PluginAlignakDashboard::getTable(), $where);
      if ($num_com > 0) {
         $data['counter_name'] = $data['counter_name'].mt_rand();
      }

      return $data;
   }
}