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

class PluginAlignakEntity extends CommonDBTM
{

   static $rightname = 'plugin_alignak_alignak';

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         if (Session::haveRight('plugin_alignak_alignak', READ)
            || Session::haveRight('plugin_alignak_counters', READ)) {
            $array_ret[] = self::createTabEntry(__('Alignak plugin', 'alignak'));
         }
      }
         return $array_ret;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getID() > -1) {
         if (Session::haveRight('plugin_alignak_alignak', READ)
            || Session::haveRight('plugin_alignak_counters', READ)) {
            $paEntity = new self();
            PluginAlignakToolbox::log("Display tab for entity: ". $item->getID());
            if (! $paEntity->getFromDBByCrit(['plugin_alignak_entitites_id' => $item->getID()])) {
               $paEntity->getEmpty();
               $paEntity->fields['plugin_alignak_entitites_id'] = $item->getID();
            }
            $paEntity->showForm($paEntity->getID(), ["in_tab" => true]);
         }
      }
      return true;
   }

    /**
     * Display form for an entity
     *
     * @param $ID Entity identifier
     * @param $options array
     *
     * @return bool true if form is ok
     **/
   function showForm($ID = -1, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      PluginAlignakToolbox::log("Edit relation with entity: ". $this->fields['plugin_alignak_entitites_id']);
      PluginAlignakToolbox::log("Alignak entity relation: ". serialize($this->fields));
      /*
      echo "<div class='spaced'>";
      if ($entity->canUpdateItem()) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }*/

      echo '<table class="tab_cadre_fixe"';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="40" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      if (Session::haveRight('plugin_alignak_alignak', READ)) {
         if (! isset($options['in_tab'])) {
            echo '<tr class="tab_bg_1">';
            echo '<td>';
            $entity = new Entity();
            $entity->getFromDB($this->fields["plugin_alignak_entitites_id"]);
            echo '<span>' . $entity->getLink() . '</span>';
            echo '<input type="hidden" name="plugin_alignak_entitites_id" value="'. $this->fields['plugin_alignak_entitites_id'] .'"/>';
            echo '</td>';
            echo '</tr>';
         }

         echo '<tr class="tab_bg_1">';
         echo '<th colspan="2">';
         echo __('Set the Alignak instance monitoring the computers of this entity', 'alignak');
         echo '</th>';
         echo '</tr>';

         echo '<tr class="tab_bg_1">';
         echo '<td>';
         echo __('Alignak instance', 'alignak');
         echo '</td>';
         echo '<td>';
         if ($this->canUpdateItem()) {
            Dropdown::show('PluginAlignakAlignak',
               ['name' => 'plugin_alignak_alignak_id',
                  'value' => $this->fields["plugin_alignak_alignak_id"],
                  'comments' => false]);
         } else {
            $paObject = new PluginAlignakAlignak();
            $paObject->getFromDB($this->fields["plugin_alignak_alignak_id"]);
            echo '<span>' . $paObject->getName() . '</span>';
         }
         // Link to the object
         if ($this->fields["plugin_alignak_alignak_id"] != 0) {
            $paObject = new PluginAlignakAlignak();
            $paObject->getFromDB($this->fields["plugin_alignak_alignak_id"]);
            echo $paObject->getLink();
         }
         echo '</td>';
         echo '</tr>';

         echo '<tr class="tab_bg_1">';
         echo '<th colspan="2">';
         echo __('Set the monitoring template used for the computers of this entity', 'alignak');
         echo '</th>';
         echo '</tr>';

         echo '<tr class="tab_bg_1">';
         echo '<td>';
         echo __('Monitoring template', 'alignak');
         echo '</td>';
         echo '<td>';
         if ($this->canUpdateItem()) {
            Dropdown::show('PluginAlignakMonitoringTemplate',
               ['name' => 'plugin_alignak_monitoring_template_id',
                  'value' => $this->fields["plugin_alignak_monitoring_template_id"],
                  'comments' => false]);
         } else {
            $paObject = new PluginAlignakMonitoringTemplate();
            $paObject->getFromDB($this->fields["plugin_alignak_monitoring_template_id"]);
            echo '<span>' . $paObject->getName() . '</span>';
         }
         // Link to the object
         if ($this->fields["plugin_alignak_monitoring_template_id"] != 0) {
            $paObject = new PluginAlignakMonitoringTemplate();
            $paObject->getFromDB($this->fields["plugin_alignak_monitoring_template_id"]);
            echo $paObject->getLink();
         }
         echo '</td>';
         echo '</tr>';
      }

      if (Session::haveRight('plugin_alignak_counters', READ)) {
         echo '<tr class="tab_bg_1">';
         echo '<th colspan="2">';
         echo __('Set the counters template used for the computers of this entity', 'alignak');
         echo '</th>';
         echo '</tr>';

         echo '<tr class="tab_bg_1">';
         echo '<td>';
         echo __('Monitoring counters template', 'alignak');
         echo '</td>';
         echo '<td>';
         if ($this->canUpdateItem()) {
            Dropdown::show('PluginAlignakCountersTemplate',
               ['name' => 'plugin_alignak_counters_template_id',
                  'value' => $this->fields["plugin_alignak_counters_template_id"],
                  'comments' => false]);
         } else {
            $paObject = new PluginAlignakCountersTemplate();
            $paObject->getFromDB($this->fields["plugin_alignak_counters_template_id"]);
            echo '<span>' . $paObject->getName() . '</span>';
         }
         // Link to the object
         if ($this->fields["plugin_alignak_counters_template_id"] != 0) {
            $paObject = new PluginAlignakCountersTemplate();
            $paObject->getFromDB($this->fields["plugin_alignak_counters_template_id"]);
            echo $paObject->getLink();
         }
         echo '</td>';
         echo '</tr>';
      }

      /*
       *
      echo '<tr class="tab_bg_1">';
      echo '<td';
      echo _n('Virtual machine', 'Virtual machines', 2);
      echo '</td>';
      echo '<td';
      Dropdown::showYesNo("import_vm", $pfConfig->getValue('import_vm'));
      echo '</td>';
       */
      PluginAlignakToolbox::log("Edit relation with entity: ". $this->fields['plugin_alignak_entitites_id']);

      //      echo '<input type="hidden" name="plugin_alignak_entitites_id" value="'. $this->fields['plugin_alignak_entitites_id'] .'/>';
      //      Html::hidden('plugin_alignak_entitites_id', $this->fields['plugin_alignak_entitites_id']);
      $this->showFormButtons($options);

      /*
      if ($entity->canUpdateItem()) {
         echo '<tr>';
         echo '<td class="tab_bg_2 center" colspan="4">';
         echo '<input type="hidden" name="id" value="'. $this->fields['id'] .'">';
         echo '<input type="hidden" name="entities_id" value="'. $this->fields['entities_id'] .'">';
         echo '<input type="submit" name="update" value="'. _sx('button', 'Save'). '" class="submit">';
         echo '</td>';
         echo '</tr>';
         echo '</table>';
         Html::closeForm();
      } else {
         echo '</table>';
      }
      */

      echo '</div>';

      return true;
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
         'name'               => __('Alignak entity')
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
         'table'              => 'glpi_plugin_alignak_entities',
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'plugin_alignak_entitites_id',
         'name'               => __('Related entity', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_plugin_alignak_alignaks',
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'plugin_alignak_alignak_id',
         'name'               => __('Related Alignak instance', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_plugin_alignak_monitoringtemplates',
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'plugin_alignak_monitoring_template_id',
         'name'               => __('Related Alignak monitoring template', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'datatype'           => 'itemlink',
         'linkfield'          => 'plugin_alignak_counters_template_id',
         'name'               => __('Related counters template', 'alignak'),
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

   /*
    * Get the Alignak entity configuration for the provided entity.
    * If not found in the provided entity, have a look in this entty ancestors
    *
    * ret
    */
   static function getForEntity($entities_id) {
      $dbu = new DbUtils();

      PluginAlignakToolbox::log("Get Alignak entity configuration for : ". $entities_id);
      $paEntity = new self();
      if (! $paEntity->getFromDBByCrit(['plugin_alignak_entitites_id' => $entities_id])) {
         $ancestors = $dbu->getAncestorsOf('glpi_entities', $entities_id);
         PluginAlignakToolbox::log("Entity ancestors: " . serialize($ancestors));
         $entity = new Entity();
         foreach ($ancestors as $index => $id) {
            $entity->getFromDB($id);
            if ($paEntity->getFromDBByCrit(['plugin_alignak_entitites_id' => $id])) {
               // Found!
               $entities_id = $id;
               PluginAlignakToolbox::log("Get Alignak entity ancestor for: ". $entities_id);
               PluginAlignakToolbox::log("Got: ". $paEntity->getLinkURL());
               break;
            }
         }
      } else {
         // Found!
         PluginAlignakToolbox::log("Get Alignak entity for: ". $entities_id);
      }

      return $paEntity;
   }
}
