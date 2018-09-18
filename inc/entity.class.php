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
            $pmEntity = new PluginAlignakEntity();
            $pmEntity->showForm($item);
         }
      }
      return true;
   }

    /**
     * Display form for an entity
     *
     * @param $entity Entity
     * @param $options array
     *
     * @return bool true if form is ok
     **/
   function showForm(Entity $entity) {

      $ID = $entity->getField('id');
      if (! $entity->can($ID, READ)) {
         return false;
      }

      PluginAlignakToolbox::log("Edit relation with entity {$ID}");
      $existing = true;
      if (! $this->getFromDBByCrit(['entities_id' => $ID])) {
         $existing = false;
         $this->getEmpty();
         $this->fields['entities_id'] = $ID;
         $entity = $this;
         PluginAlignakToolbox::log("Create a new entity relation: ". serialize($this->fields));
      } else {
         PluginAlignakToolbox::log("Existing entity relation: ". serialize($this->fields));
      }

      echo "<div class='spaced'>";
      if ($entity->canUpdateItem()) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo '<table class="tab_cadre_fixe"';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="124" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      if (Session::haveRight('plugin_alignak_alignak', READ)) {
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
         if ($entity->canUpdateItem()) {
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
         if ($entity->canUpdateItem()) {
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
         if ($entity->canUpdateItem()) {
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

      echo '</div>';

      return true;
   }

   /*
   function getEntitiesByTag($tag = '') {
       global $DB;

      if ($tag == '') {
          return ['-1' => "-1"];
      } else {
         $output = [];
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `tag`='".$tag."'";
         $result = $DB->query($query);
         while ($data=$DB->fetch_array($result)) {
             $output[$data['entities_id']] = $data['entities_id'];
         }
         return $output;
      }
   }

   static function getTagByEntities($entities_id) {
       global $DB;

       $query = "SELECT * FROM `glpi_plugin_monitoring_entities`
         WHERE `entities_id`='".$entities_id."'
            LIMIT 1";
       $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
          return $data['tag'];
      }
   }
   */
}

