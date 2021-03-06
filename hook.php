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
// Purpose of file: Plugin hooks
// ----------------------------------------------------------------------

/**
 * Plugin install process
 *
 * @return boolean True if success
 */
function plugin_alignak_install() {
   spl_autoload_register('plugin_alignak_autoload');

   $version   = plugin_version_alignak();
   $migration = new Migration($version['version']);
   require_once(__DIR__ . '/install/install.php');
   $install = new PluginAlignakInstall();
   if (! $install->isPluginInstalled()) {
      return $install->install($migration);
   }
   return $install->upgrade($migration);
}


/**
 * Plugin uninstall process
 */
function plugin_alignak_uninstall() {
   require_once(__DIR__ . '/install/install.php');
   $install = new PluginAlignakInstall();
   $install->uninstall(true);
}


// Hook called on profile change
// Good place to evaluate the user right on this plugin
// And to save it in the session
function plugin_change_profile_alignak() {
   // For example : same right of computer
   if (Session::haveRight('computer', UPDATE)) {
      $_SESSION["glpi_plugin_alignak_profile"] = ['alignak' => 'w'];

   } else if (Session::haveRight('computer', READ)) {
      $_SESSION["glpi_plugin_alignak_profile"] = ['alignak' => 'r'];

   } else {
      unset($_SESSION["glpi_plugin_alignak_profile"]);
   }
}

/*
 * Dropdowns management
 */
// Define dropdown relations
function plugin_alignak_getDatabaseRelations() {
   return ["glpi_plugin_alignak_dropdowns" => ["glpi_plugin_alignak" => "plugin_alignak_dropdowns_id"]];
}


// Define Dropdown tables to be managed in GLPI :
function plugin_alignak_getDropdown() {
   // Table => Name
   //   return ['PluginAlignakDropdown' => __("Plugin Alignak Dropdown", 'alignak')];
}


/*
 * Search functions
 */
// Define Additionnal search options for types (other than the plugin ones)
function plugin_alignak_getAddSearchOptions($itemtype) {
   $sopt = [];
   if ($itemtype == 'Computer') {
         // Just for example, not working...
         $sopt[1001]['table']     = 'glpi_plugin_alignak_dropdowns';
         $sopt[1001]['field']     = 'name';
         $sopt[1001]['linkfield'] = 'plugin_alignak_dropdowns_id';
         $sopt[1001]['name']      = __('Alignak plugin', 'alignak');
   }
   return $sopt;
}


function plugin_alignak_getAddSearchOptionsNew($itemtype) {
   $options = [];
   if ($itemtype == 'Computer') {
      //Just for example, not working
      $options[] = [
         'id'        => '1002',
         'table'     => 'glpi_plugin_alignak_dropdowns',
         'field'     => 'name',
         'linkfield' => 'plugin_alignak_dropdowns_id',
         'name'      => __('Alignak plugin new', 'alignak')
      ];
   }
   return $options;
}


// How to display specific search fields or dropdown ?
// options must contain at least itemtype and options array
// MUST Use a specific AddWhere & $tab[X]['searchtype'] = 'equals'; declaration
function plugin_alignak_searchOptionsValues($options = []) {
   $table = $options['searchoption']['table'];
   $field = $options['searchoption']['field'];

   // Table fields
   switch ($table.".".$field) {
      case "glpi_plugin_alignak_alignaks.serial" :
         echo __("Not really specific - Use your own dropdown - Just for example", 'alignak');
         Dropdown::show(getItemTypeForTable($options['searchoption']['table']),
            ['value'    => $options['value'],
               'name'     => $options['name'],
               'comments' => 0]);
         // Need to return true if specific display
         return true;
   }
   return false;
}


// See also PluginAlignakAlignak::getSpecificValueToDisplay()
function plugin_alignak_giveItem($type, $ID, $data, $num) {
   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_alignak_alignaks.name" :
         $out = "<a href='".Toolbox::getItemTypeFormURL('PluginAlignakAlignak')."?id=".$data['id']."'>";
         $out .= $data[$num][0]['name'];
         if ($_SESSION["glpiis_ids_visible"] || empty($data[$num][0]['name'])) {
            $out .= " (".$data["id"].")";
         }
         $out .= "</a>";
         return $out;
   }
   return "";
}


function plugin_alignak_displayConfigItem($type, $ID, $data, $num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Alignak of specific style options
   // No need of the function if you do not have specific cases
   switch ($table.'.'.$field) {
      case "glpi_plugin_alignak_alignaks.name" :
         return " style=\"background-color:#DDDDDD;\" ";
   }
   return "";
}


function plugin_alignak_addDefaultJoin($type, $ref_table, &$already_link_tables) {
   // Alignak of default JOIN clause
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginAlignakAlignak" :
      case "MyType" :
         return Search::addLeftJoin($type, $ref_table, $already_link_tables,
                                    "newtable", "linkfield");
   }
   return "";
}


function plugin_alignak_addDefaultSelect($type) {
   // Alignak of default SELECT item to be added
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginAlignakAlignak" :
      case "MyType" :
         return "`mytable`.`myfield` = 'myvalue' AS MYNAME, ";
   }
   return "";
}


function plugin_alignak_addDefaultWhere($type) {
   // Alignak of default WHERE item to be added
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginAlignakAlignak" :
      case "MyType" :
         return " `mytable`.`myfield` = 'myvalue' ";
   }
   return "";
}


function plugin_alignak_addLeftJoin($type, $ref_table, $new_table, $linkfield) {
   // Alignak of standard LEFT JOIN  clause but use it ONLY for specific LEFT JOIN
   // No need of the function if you do not have specific cases
   switch ($new_table) {
      case "glpi_plugin_alignak_dropdowns" :
         return " LEFT JOIN `$new_table` ON (`$ref_table`.`$linkfield` = `$new_table`.`id`) ";
   }
   return "";
}


function plugin_alignak_forceGroupBy($type) {
   switch ($type) {
      case 'PluginAlignakAlignak' :
         // Force add GROUP BY IN REQUEST
         return true;
   }
   return false;
}


function plugin_alignak_addWhere($link, $nott, $type, $ID, $val, $searchtype) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   $SEARCH = Search::makeTextSearch($val, $nott);

   // Alignak of standard Where clause but use it ONLY for specific Where
   // No need of the function if you do not have specific cases
   switch ($table.".".$field) {
      /*case "glpi_plugin_alignak.name" :
        $ADD = "";
        if ($nott && $val!="NULL") {
           $ADD = " OR `$table`.`$field` IS NULL";
        }
        return $link." (`$table`.`$field` $SEARCH ".$ADD." ) ";*/
      case "glpi_plugin_alignak_alignaks.serial" :
          return $link." `$table`.`$field` = '$val' ";
   }
   return "";
}


// This is not a real alignak because the use of Having condition in this case is not suitable
function plugin_alignak_addHaving($link, $nott, $type, $ID, $val, $num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   $SEARCH = Search::makeTextSearch($val, $nott);

   // Alignak of standard Having clause but use it ONLY for specific Having
   // No need of the function if you do not have specific cases
   switch ($table.".".$field) {
      case "glpi_plugin_alignak.serial" :
         $ADD = "";
         if (($nott && $val!="NULL")
             || $val == '^$') {
            $ADD = " OR ITEM_$num IS NULL";
         }
         return " $LINK ( ITEM_".$num.$SEARCH." $ADD ) ";
   }
   return "";
}


function plugin_alignak_addSelect($type, $ID, $num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Example of standard Select clause but use it ONLY for specific Select
   // No need of the function if you do not have specific cases
   // switch ($table.".".$field) {
   //    case "glpi_plugin_alignak.name" :
   //       return $table.".".$field." AS ITEM_$num, ";
   // }
   return "";
}


function plugin_alignak_addOrderBy($type, $ID, $order, $key = 0) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Example of standard OrderBy clause but use it ONLY for specific order by
   // No need of the function if you do not have specific cases
   // switch ($table.".".$field) {
   //    case "glpi_plugin_alignak.name" :
   //       return " ORDER BY $table.$field $order ";
   // }
   return "";
}


/*
 * Massive actions
 */
// Define actions :
function plugin_alignak_MassiveActions($type) {
   switch ($type) {
      // New action for core and other plugin types : name = plugin_PLUGINNAME_actionname
      case 'Computer' :
         return ['PluginAlignakAlignak'.MassiveAction::CLASS_ACTION_SEPARATOR.'DoIt' =>
                                                              __("plugin_alignak_DoIt", 'alignak')];

      // Actions for types provided by the plugin are included inside the classes
   }
   return [];
}


// How to display specific update fields ?
// options must contain at least itemtype and options array
function plugin_alignak_MassiveActionsFieldsDisplay($options = []) {
   //$type,$table,$field,$linkfield

   $table     = $options['options']['table'];
   $field     = $options['options']['field'];
   $linkfield = $options['options']['linkfield'];

   if ($table == getTableForItemType($options['itemtype'])) {
      // Table fields
      switch ($table.".".$field) {
         case 'glpi_plugin_alignak_alignaks.serial' :
            echo __("Not really specific - Just for example !", 'alignak');
            //Html::autocompletionTextField($linkfield,$table,$field);
            // Dropdown::showYesNo($linkfield);
            // Need to return true if specific display
            return true;
      }

   } else {
      // Linked Fields
      switch ($table.".".$field) {
         case "glpi_plugin_alignak_dropdowns.name" :
            echo __("Not really specific - Just for example !!!", 'alignak');
            // Need to return true if specific display
            return true;
      }
   }
   // Need to return false on non display item
   return false;
}


/*
 * Item events
 */
// Hook done on before update item case
function plugin_pre_item_update_alignak($item) {
   /* Manipulate data if needed
   if (!isset($item->input['comment'])) {
      $item->input['comment'] = addslashes($item->fields['comment']);
   }
   $item->input['comment'] .= addslashes("\nUpdate: ".date('r'));
   */
   Session::addMessageAfterRedirect(__("Pre Update Computer Hook", 'alignak'), true);
}


// Hook done on update item case
function plugin_item_update_alignak($item) {
   Session::addMessageAfterRedirect(sprintf(__("Update Computer Hook (%s)", 'alignak'), implode(',', $item->updates)), true);
   return true;
}


// Hook done on get empty item case
function plugin_item_empty_alignak($item) {
   if (empty($_SESSION['Already displayed "Empty Computer Hook"'])) {
      // Session::addMessageAfterRedirect(__("Empty Computer Hook", 'alignak'), true);
      $_SESSION['Already displayed "Empty Computer Hook"'] = true;
   }
   return true;
}


// Hook done on before delete item case
function plugin_pre_item_delete_alignak($object) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Delete Computer Hook", 'alignak'), true);
}


// Hook done on delete item case
function plugin_item_delete_alignak($object) {
   Session::addMessageAfterRedirect(__("Delete Computer Hook", 'alignak'), true);
   return true;
}


// Hook done on before purge item case
function plugin_pre_item_purge_alignak($object) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Purge Computer Hook", 'alignak'), true);
}


// Hook done on purge item case
function plugin_item_purge_alignak($object) {
   Session::addMessageAfterRedirect(__("Purge Computer Hook", 'alignak'), true);
   return true;
}


// Hook done on before restore item case
function plugin_pre_item_restore_alignak($item) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Restore Computer Hook", 'alignak'));
}


// Hook done on restore item case
function plugin_item_restore_alignak($item) {
   Session::addMessageAfterRedirect(__("Restore Computer Hook", 'alignak'));
   return true;
}


// Hook done on restore item case
function plugin_item_transfer_alignak($parm) {
   //TRANS: %1$s is the source type, %2$d is the source ID, %3$d is the destination ID
   Session::addMessageAfterRedirect(sprintf(__('Transfer Computer Hook %1$s %2$d -> %3$d', 'alignak'), $parm['type'], $parm['id'],
                                     $parm['newID']));

   return false;
}


/*
 * Reports
 */
// Do special actions for dynamic report
function plugin_alignak_dynamicReport($parm) {
   if ($parm["item_type"] == 'PluginAlignakAlignak') {
      // Do all what you want for export depending on $parm
      echo "Personalized export for type ".$parm["display_type"];
      echo 'with additional datas : <br>';
      echo "Single data : add1 <br>";
      print $parm['add1'].'<br>';
      echo "Array data : add2 <br>";
      Html::printCleanArray($parm['add2']);
      // Return true if personalized display is done
      return true;
   }
   // Return false if no specific display is done, then use standard display
   return false;
}


// Add parameters to Html::printPager in search system
function plugin_alignak_addParamFordynamicReport($itemtype) {
   if ($itemtype == 'PluginAlignakAlignak') {
      // Return array data containing all params to add : may be single data or array data
      // Search config are available from session variable
      return ['add1' => $_SESSION['glpisearch'][$itemtype]['order'],
              'add2' => ['tutu' => 'Second Add',
                         'Other Data']];
   }
   // Return false or a non array data if not needed
   return false;
}


function plugin_alignak_AssignToTicket($types) {
   $types['PluginAlignakAlignak'] = "Alignak";
   return $types;
}


function plugin_alignak_get_events(NotificationTargetTicket $target) {
   $target->events['plugin_alignak'] = __("Alignak event", 'alignak');
}


function plugin_alignak_get_datas(NotificationTargetTicket $target) {
   $target->data['##ticket.alignak##'] = __("Alignak datas", 'alignak');
}


/**
 * Called when all plugins are initialized
 *
 * @return boolean
 */
function plugin_alignak_postinit() {
   global $CFG_GLPI;

   // All plugins are initialized, so all types are registered
   //foreach (Infocom::getItemtypesThatCanHave() as $type) {
      // do something
   //}
}


/**
 * Add information to the status page
 *
 * @param $param   array
 *
 * @return array
 **/
// Check to add to status page
function plugin_alignak_status($param) {
   // Do checks (no check for alignak)
   $ok = true;
   echo "alignak plugin: alignak";
   if ($ok) {
      echo "_OK";
   } else {
      echo "_PROBLEM";
      // Only set ok to false if trouble (global status)
      $param['ok'] = false;
   }
   echo "\n";
   return $param;
}


/**
 * Display information on the central home page
 *
 **/
function plugin_alignak_display_central() {
   global $CFG_GLPI;

   if (Session::getCurrentInterface() == "helpdesk") {
      PluginAlignakToolbox::log("On the central page!");

      // Button
      $paEntity = PluginAlignakEntity::getForEntity($_SESSION['glpidefault_entity']);
      echo '<tr>';
      echo '<div style="clear: both">';
      echo '<a href="'. $paEntity->getLinkURL() .'&helpdesk=1&hosts_board=1">';
      echo '<img src="'. $CFG_GLPI['root_doc'] . '/pics/menu_show.png">&nbsp;&nbsp;';
      echo __("Hosts board", "alignak");
      echo '</a>';
      echo '</div>';
      echo '</tr>';

      echo '<tr>';
      echo '<div style="clear: both">';
      echo '<a href="'. $paEntity->getLinkURL() .'&helpdesk=1&configuration=1">';
      echo '<img src="'. $CFG_GLPI['root_doc'] . '/pics/menu_add.png">&nbsp;&nbsp;';
      echo __("View my configuration", "alignak");
      echo '</a>';
      echo '</div>';
      echo '</tr>';
   }
}


/**
 * Display information on the login page
 *
 **/
function plugin_alignak_display_login() {
   PluginAlignakToolbox::log("On the login page!");
   echo "<div style='text-align:center; font-size:2em'>";
   echo __("Plugin alignak displays on login page", "alignak");
   echo "</div>";
}


/**
 * Display information on the infocom page
 *
 * @param $param   array
 *
 * @return array
 **/
function plugin_alignak_infocom_hook($params) {
   echo "<tr><th colspan='4'>";
   echo __("Plugin alignak displays on central page", "alignak");
   echo "</th></tr>";
}


/**
 * Register plugin Web services
 **/
function plugin_alignak_registerMethods() {
   global $WEBSERVICES_METHOD;

   Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Register Web services...");
   $WEBSERVICES_METHOD['kiosks.getGeoloc'] = ['PluginAlignakWebservice', 'methodGetGeoloc'];
   /*
   $WEBSERVICES_METHOD['kiosks.getCounter'] = array('PluginKiosksWebservice', 'methodGetCounter');
   $WEBSERVICES_METHOD['kiosks.getDashboard'] = array('PluginKiosksWebservice', 'methodGetDashboard');
   $WEBSERVICES_METHOD['kiosks.getDatatable'] = array('PluginKiosksWebservice', 'methodGetDatatable');
   $WEBSERVICES_METHOD['kiosks.getHosts'] = array('PluginKiosksWebservice', 'methodGetHosts');
   $WEBSERVICES_METHOD['kiosks.getServices'] = array('PluginKiosksWebservice', 'methodGetServices');
   $WEBSERVICES_METHOD['kiosks.getLocations'] = array('PluginKiosksWebservice', 'methodGetLocations');
   $WEBSERVICES_METHOD['kiosks.getHelpdeskConfiguration'] = array('PluginKiosksWebservice', 'methodGetHelpdeskConfiguration');
   $WEBSERVICES_METHOD['kiosks.listTickets'] = array('PluginKiosksWebservice', 'methodListTickets');
   $WEBSERVICES_METHOD['kiosks.createTicket'] = array('PluginKiosksWebservice', 'methodCreateTicket');
   $WEBSERVICES_METHOD['kiosks.addTicketFollowUp'] = array('PluginKiosksWebservice', 'methodAddTicketFollowUp');
   $WEBSERVICES_METHOD['kiosks.getTicket'] = array('PluginKiosksWebservice', 'methodGetTicket');
   $WEBSERVICES_METHOD['kiosks.ackService'] = array('PluginKiosksWebservice', 'methodAckService');
   $WEBSERVICES_METHOD['kiosks.downtimeHost'] = array('PluginKiosksWebservice', 'methodDowntimeHost');
   $WEBSERVICES_METHOD['kiosks.getKioskConfiguration'] = array('PluginKiosksWebservice', 'methodGetKioskConfiguration');
   $WEBSERVICES_METHOD['kiosks.getCounters'] = array('PluginKiosksWebservice', 'methodGetCounters');
   $WEBSERVICES_METHOD['kiosks.getCountersOld'] = array('PluginKiosksWebservice', 'methodGetCountersOld');
   $WEBSERVICES_METHOD['kiosks.getComponentCounterTable'] = array('PluginKiosksWebservice', 'methodGetComponentCounterTable');
   $WEBSERVICES_METHOD['kiosks.getSspTableData'] = array('PluginKiosksWebservice', 'methodGetSspTableData');
   $WEBSERVICES_METHOD['kiosks.pushCondCounterValue'] = array('PluginKiosksWebservice', 'methodpushCondCounterValue');
   */
}
