<?php
/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Francois Mohier
// Purpose of file:
// ----------------------------------------------------------------------


error_reporting(E_ALL);
 ini_set('display_errors', 1);
 

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class PluginAlignakCounterTemplate
class PluginAlignakCounterTemplate extends CommonDBTM {
   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_counters';

   static function getTypeName($nb=0) {
      return _n('Counters template', 'Counters templates', $nb, 'alignak');
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `glpi_plugin_alignak_countertemplates` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(25) collate utf8_unicode_ci NOT NULL,
                  `entities_id` int(11),
                PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

         $DB->query($query) or die("error creating $table". $DB->error());
         
      }

      return true;
   }

   static function uninstall() {
      global $DB;

      $DB->query("DROP TABLE IF EXISTS `".self::getTable()."`");

      return true;
   }

   /*
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         if (PluginAlignakProfile::haveRight("config", 'r')) {
            $array_ret[0] = self::createTabEntry(__('Monitoring', 'monitoring'));
         }
      }
      return $array_ret;
   }
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      $ong = array();
      $ong[1] = 'titre de mon premier onglet';
      $ong[2] = 'titre de mon second onglet';
      return $ong;
   }
   */

/*
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      echo "DISPLAY SOMETING".$item->getID();
      if ($item->getID() > -1) {
         $pmCounter = new PluginAlignakCounterTemplate();
         $pmHostconfig = new PluginAlignakHostconfig();

         $pmHostconfig->showForm($item->getID(), "CounterTemplate");
         $pmEntity->showForm($item->fields['id']);
      }
      return true;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      echo "countertemplate displayTabContentForItem".$tabnum.":".$item;

      return true;
   }    

   public function defineTabs($options=[]) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginAlignakCounter', $ong, $options);
      return $ong;
   }
   */


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            return [1 => __('Counters template', 'alignak')];
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            $pkMailNotification = new PluginAlignakCounterTemplate();
            // Call the show form for the current entity
            $pkMailNotification->showForm(-1, $item->getID(), ['canedit'=>self::canUpdate(), 'colspan'=>4 ]);
            break;
      }
      return true;
   }

   /**
   * Display form for counter template tag
   *
   * @param $items_id integer ID of the counter
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   /* Commented out - Fred - Replaced with another function showForm
   function showForm($template_id, $options = []) {
      global $DB,$CFG_GLPI;

      
      $counters = $this->find("`id`='".$template_id."'", "", 1);
      $counter = current($counters);
      $this->getFromDB($counter['id']);

      // echo "<form name='form' method='post'
      //action='".$CFG_GLPI['root_doc']."/plugins/alignak/front/counter_template.form.php'>";

      $this->initForm($template_id, $options);
      $this->showFormHeader($options);

      echo "<table class='tab_cadre_fixe'";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo __('Set the counter template', 'alignak');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Template Name', 'alignak').'<span style="color:red;">*</span></td>';
      echo "<td>";
      echo "<input type='text' name='name' value='".$this->fields["name"]."' size='30'/>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Entity', 'alignak')." :</td>";
      echo "<td>";
      echo "<input type='text' name='entities_id' value='".$this->fields["entities_id"]."' size='30'/>";
      
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' align='center'>";
      echo "<input type='hidden' name='id' value='".$this->fields['id']."'/>";
      echo "<input type='submit' name='save' value=\"".__('Save')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();

      return true;
   }
   
   function getCounterTemplateListForDropdown($entities_id = '') {
      global $DB;

      $output = [];
      $query = "SELECT * FROM `".$this->getTable()."` ";
      
      if ($template_id != '') {
        $query .= " WHERE `template_id`='".$template_id."'";
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $output[$data['id']] = $data['name'];
      }
      return $output;
   }
   
   
   function getCounterTemplateList($entities_id = '') {
      global $DB;

      $output = [];
      $query = "SELECT * FROM `".$this->getTable()."` ";
      
      if ($template_id != '') {
        $query .= " WHERE `template_id`='".$template_id."'";
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $output[$data['id']] = $data['id'];
         $output[$data['name']] = $data['name'];
         $output[$data['entities_id']] = $data['entities_id'];
      }
      return $output;
   }
   
   */
   function showForm($ID = -1, $entities_id = -1, $options = [], $copy = []) {
      global $DB,$CFG_GLPI;

      if ($ID != -1) {
         // We still know which object if to be edited...
         $this->getFromDB($ID);
      } else {
         // Create a new object...

         // If no entity is specified, use the root entity
         if ($entities_id == -1) {
            // Root entity ...
            $entity = new Entity();
            $entity->getFromDBByCrit(['id' => '0']);
            $entities_id = $entity->getID();
         }

         // We still have an item for this entity?
         $a_confs = $this->find("`entities_id`='".$entities_id."'", "", 1);
         if (count($a_confs) > 0) {
            // If we have, use the found item
            $a_conf = current($a_confs);
            $ID = $a_conf['id'];
            $this->getFromDB($ID);
         } else {
            // else, create a new item
            $this->getEmpty();
            //$this->setDefaultContent();
            $this->fields['entities_id'] = $entities_id;
         }
      }

      // Get a user object
      $user = new User();
      $user->getFromDB(Session::getLoginUserID());

      // Get an entity object
      $entity = new Entity();
      $entity->getFromDB($this->fields["entities_id"]);
      $entities_id = $this->fields["entities_id"];
      echo '<input type="hidden" name="id" value="' . $this->fields['id'] . '"/>';
      echo '<input type="hidden" name="entities_id" value="' . $entities_id . '"/>';

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      /*
      @fred - For future idea
      echo '<tr>';
      echo '<td>';
      echo __('Is active?', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
      } else {
         echo Dropdown::getYesNo($this->fields['is_active']);
      }
      echo '</td>';
      echo '</tr>';
      */

      echo '<tr>';
      echo '<td>'.__('Entity', "alignak").'</td>';
      echo '<td colspan="5">';
      // Select an entity in the database
      $entity->dropdown([
         'name'=>'entities_id',
         'value'=>$this->fields['entities_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$user->fields['entities_id'],
         'entity_sons'=>true]);
      echo '</td>';
      echo '</tr>';


      echo '<tr>';
      echo '<td>'.__('Computer template name', "alignak").'</td>';
      echo '<td colspan="7">';
      if (! empty($this->fields["name"])) {
         echo '<input type="text" name="name" value="'. $this->fields["name"] .'" size="20"/>';
      } else {
         echo '<input type="text" name="name" value="'. __("Template ", "alignak") . $user->fields["name"] .'" size="20"/>';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr><td colspan="8">';
      echo '<hr/>';
      echo '</td></tr>';

      $this->showFormButtons($options);

      Html::closeForm();

      return true;
   }

   
      /**
    * Define search options for forms
    *
    * @return Array Array of fields to show in search engine and options for each fields
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'searchtype'         => 'contains',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => true
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'entities_id',
         'name'               => __('Entity'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];
      return $tab;
   }

}
