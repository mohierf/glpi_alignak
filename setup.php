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
   @co-author David Durieux
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
// Purpose of file: Plugin setup and configuration
// ----------------------------------------------------------------------

define ('PLUGIN_ALIGNAK_VERSION', '9.3 + 0.1');
define ('PLUGIN_ALIGNAK_GLPI_MIN_VERSION', '9.1');
define ('PLUGIN_ALIGNAK_NAME', 'Alignak monitoring plugin');
define ('PLUGIN_ALIGNAK_LOG', 'plugin-alignak');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_alignak() {
   global $PLUGIN_HOOKS,$CFG_GLPI;

   Toolbox::logInFile("alignak", "test");

   // Params : plugin name - string type - ID - Array of attributes
   // No specific information passed so not needed
   //Plugin::registerClass('PluginAlignakAlignak',
   //                      array('classname'              => 'PluginAlignakAlignak',
   //                        ));

   // Plugin configuration class
   Plugin::registerClass('PluginAlignakConfig', ['addtabon' => 'Config']);

   // Params : plugin name - string type - ID - Array of attributes
   Plugin::registerClass('PluginAlignakDropdown');

   // Add forms tab on several classes
   $types = [
      'Central',
      'Computer',
      'Preference',
      'Profile'
   ];
   Plugin::registerClass('PluginAlignakAlignak',
                         ['notificationtemplates_types' => true,
                          'addtabon' => $types,
                          'link_types' => true]);

   Plugin::registerClass('PluginAlignakRuleTestCollection',
                         ['rulecollections_types' => true]);

   // Add tags for the plugin
   if (version_compare(GLPI_VERSION, 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION', 'ge')) {
      if (class_exists('PluginAlignakAlignak')) {
         Link::registerTag(PluginAlignakAlignak::$tags);
      }
   }

   // Display a menu entry ?
   $_SESSION["glpi_plugin_alignak_profile"]['alignak'] = 'w';
   if (isset($_SESSION["glpi_plugin_alignak_profile"])) { // Right set in change_profile hook
      $PLUGIN_HOOKS['menu_toadd']['alignak'] = ['plugins' => 'PluginAlignakAlignak',
                                                'tools'   => 'PluginAlignakAlignak'];

      $PLUGIN_HOOKS["helpdesk_menu_entry"]['alignak'] = true;
   }

   // Configuration page
   if (Session::haveRight('config', UPDATE)) {
      $PLUGIN_HOOKS['config_page']['alignak'] = 'config.php';
   }

   // Init session
   //$PLUGIN_HOOKS['init_session']['alignak'] = 'plugin_init_session_alignak';
   // When the user changes its profile
   // $PLUGIN_HOOKS['change_profile']['alignak'] = 'plugin_change_profile_alignak';
   // When the user changes its entity
   // $PLUGIN_HOOKS['change_entity']['alignak'] = 'plugin_change_entity_alignak';

   // Item action events // See define.php for defined ITEM_TYPE
   $PLUGIN_HOOKS['pre_item_update']['alignak'] = ['Computer' => 'plugin_pre_item_update_alignak'];
   $PLUGIN_HOOKS['item_update']['alignak'] = ['Computer' => 'plugin_item_update_alignak'];
   $PLUGIN_HOOKS['item_empty']['alignak'] = ['Computer' => 'plugin_item_empty_alignak'];

   // Restrict right
   $PLUGIN_HOOKS['item_can']['alignak'] = ['Computer' => ['PluginAlignakComputer', 'item_can']];
   $PLUGIN_HOOKS['add_default_where']['alignak'] = ['Computer' => ['PluginAlignakComputer', 'add_default_where']];

   // Alignak using a method in class
   $PLUGIN_HOOKS['pre_item_add']['alignak'] = ['Computer' => ['PluginAlignakAlignak',
                                                                 'pre_item_add_computer']];
   $PLUGIN_HOOKS['post_prepareadd']['alignak'] = ['Computer' => ['PluginAlignakAlignak',
                                                                 'post_prepareadd_computer']];
   $PLUGIN_HOOKS['item_add']['alignak'] = ['Computer' => ['PluginAlignakAlignak', 'item_add_computer']];

   $PLUGIN_HOOKS['pre_item_delete']['alignak'] = ['Computer' => 'plugin_pre_item_delete_alignak'];
   $PLUGIN_HOOKS['item_delete']['alignak'] = ['Computer' => 'plugin_item_delete_alignak'];

   $PLUGIN_HOOKS['pre_item_purge']['alignak'] = ['Computer' => 'plugin_pre_item_purge_alignak'];
   $PLUGIN_HOOKS['item_purge']['alignak'] = ['Computer' => 'plugin_item_purge_alignak'];

   $PLUGIN_HOOKS['pre_item_restore']['alignak'] = ['Computer' => 'plugin_pre_item_restore_alignak'];
   $PLUGIN_HOOKS['item_restore']['alignak'] = ['Computer' => 'plugin_item_restore_alignak'];

   /* Add event to GLPI core itemtype, event will be raised by the plugin.
   // See plugin_alignak_uninstall for cleanup of notification
   $PLUGIN_HOOKS['item_get_events']['alignak'] = ['NotificationTargetTicket' => 'plugin_alignak_get_events'];
   */

   /* Add datas to GLPI core itemtype for notifications template.
   $PLUGIN_HOOKS['item_get_datas']['alignak'] = [
      'NotificationTargetTicket' => 'plugin_alignak_get_datas'];
   */

   // $PLUGIN_HOOKS['item_transfer']['alignak'] = 'plugin_item_transfer_alignak';

   /*
   // function to populate planning
   // No more used since GLPI 0.84
   // $PLUGIN_HOOKS['planning_populate']['alignak'] = 'plugin_planning_populate_alignak';
   // Use instead : add class to planning types and define populatePlanning in class
   $CFG_GLPI['planning_types'][] = 'PluginAlignakAlignak';

   //function to display planning items
   // No more used since GLPi 0.84
   // $PLUGIN_HOOKS['display_planning']['alignak'] = 'plugin_display_planning_alignak';
   // Use instead : displayPlanningItem of the specific itemtype
   */

   // Massive Action definition
   $PLUGIN_HOOKS['use_massive_action']['alignak'] = 1;

   $PLUGIN_HOOKS['assign_to_ticket']['alignak'] = 1;

   // Add specific files to add to the header : javascript or css
   $PLUGIN_HOOKS['add_javascript']['alignak'] = 'alignak.js';
   $PLUGIN_HOOKS['add_css']['alignak']        = 'alignak.css';

   /*
   // request more attributes from ldap
   //$PLUGIN_HOOKS['retrieve_more_field_from_ldap']['alignak']="plugin_retrieve_more_field_from_ldap_alignak";

   // Retrieve others datas from LDAP
   //$PLUGIN_HOOKS['retrieve_more_data_from_ldap']['alignak']="plugin_retrieve_more_data_from_ldap_alignak";
   */

/*   // Reports
   $PLUGIN_HOOKS['reports']['alignak'] = [
      'report.php' => 'New Report',
      'report.php?other' => 'New Report 2'];
*/
/*
   // Stats
   $PLUGIN_HOOKS['stats']['alignak'] = ['stat.php'       => 'New stat',
                                        'stat.php?other' => 'New stats 2',];
*/

   $PLUGIN_HOOKS['post_init']['alignak'] = 'plugin_alignak_postinit';

   $PLUGIN_HOOKS['status']['alignak'] = 'plugin_alignak_status';

   // CSRF compliance : All actions must be done via POST and forms closed by Html::closeForm();
   $PLUGIN_HOOKS['csrf_compliant']['alignak'] = true;

   // To display on central home page
   $PLUGIN_HOOKS['display_central']['alignak'] = "plugin_alignak_display_central";
   // To display on login page
   $PLUGIN_HOOKS['display_login']['alignak'] = "plugin_alignak_display_login";
   // To display on infocom
   $PLUGIN_HOOKS['infocom']['alignak'] = "plugin_alignak_infocom_hook";

   /* pre_show and post_show for tabs and items,
   // see PluginAlignakShowtabitem class for implementation explanations
   $PLUGIN_HOOKS['pre_show_tab']['alignak']     = ['PluginAlignakShowtabitem', 'pre_show_tab'];
   $PLUGIN_HOOKS['post_show_tab']['alignak']    = ['PluginAlignakShowtabitem', 'post_show_tab'];
   $PLUGIN_HOOKS['pre_show_item']['alignak']    = ['PluginAlignakShowtabitem', 'pre_show_item'];
   $PLUGIN_HOOKS['post_show_item']['alignak']   = ['PluginAlignakShowtabitem', 'post_show_item'];

   $PLUGIN_HOOKS['pre_item_form']['alignak']    = ['PluginAlignakItemForm', 'preItemForm'];
   $PLUGIN_HOOKS['post_item_form']['alignak']   = ['PluginAlignakItemForm', 'postItemForm'];
   */

   /* declare this plugin as an import plugin for Computer itemtype
   $PLUGIN_HOOKS['import_item']['exemple'] = ['Computer' => ['Plugin']];

   // add additional informations on Computer::showForm
   $PLUGIN_HOOKS['autoinventory_information']['exemple'] =  [
      'Computer' =>  ['PluginAlignakComputer', 'showInfo']];
   */

}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_alignak() {
   // Use requirements (Glpi > 9.2)
   return [
      'name'           => 'Alignak monitoring plugin',
      'version'        => PLUGIN_ALIGNAK_VERSION,
      'author'         => 'Frédéric Mohier & Alignak Team',
      'license'        => 'AGPLv3',
      'homepage'       => 'https://github.com/mohierf/alignak',
      'requirements'   => [
         'glpi' => [
            'min' => 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION',
            'dev' => true
         ],
         /* Required Glpi parameters
         'params' => [

         ],
         */
         /* Required installed aned activated plugins
         'plugins' => [

         ]
         */
      ]
   ];
}


/**
 * Check pre-requisites before install
 * OPTIONAL, but recommended
 * For Glpi < 9.2, else requirements are managed by core Glpi
 *
 * @return boolean
 */
function plugin_alignak_check_prerequisites() {

   $version = rtrim(GLPI_VERSION, '-dev');
   if (version_compare($version, 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION', 'lt')) {
      echo __('This plugin requires GLPI ' . PLUGIN_ALIGNAK_GLPI_MIN_VERSION, 'alignak');

      return false;
   }

   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_alignak_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'alignak');
   }
   return false;
}
