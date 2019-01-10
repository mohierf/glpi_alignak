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

// Class of the defined type
class PluginAlignakAlignak extends CommonDBTM
{
   static $tags = '[ALIGNAK_ID]';

   public $dohistory = true;

   static $rightname = 'plugin_alignak_alignak';

   static function getTypeName($nb = 0) {
      return _n('Alignak instance', 'Alignak instances', $nb, 'alignak');
   }

   function defineTabs($options = []) {

       $ong = [];
       $this->addDefaultFormTab($ong);
       $this->addStandardTab('Link', $ong, $options);

       return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo '<tr>';
      echo '<td>'.__('Alignak instance name', "alignak").'</td>';
      echo '<td>';
      echo '<input type="text" name="name" value="'. $this->fields["name"] .'" size="20"/>';
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="40" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<th colspan="2">';
      echo __('Set the tag value to link this entity with a specific Alignak server', 'alignak');
      echo '</th>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Tag', 'alignak');
      echo '</td>';
      echo '<td>';
      if ($this->canUpdateItem()) {
         echo '<input type="text" name="tag" value="' . $this->fields["tag"] . '" size="30"/>';
      } else {
         echo '<span>' . $this->fields["tag"] . '</span>';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<th colspan="2">';
      echo __('Set the Alignak configuration for this entity', 'alignak');
      echo '</th>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Alignak address', 'monitoring');
      echo '</td>';
      echo '<td>';
      echo "<input type='text' name='address' value='".$this->fields["address"]."' size='30'/>";
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Alignak username', 'monitoring');
      echo '</td>';
      echo '<td>';
      echo "<input type='text' name='username' value='".$this->fields["username"]."' size='30'/>";
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Alignak password', 'monitoring');
      echo '</td>';
      echo '<td>';
      echo "<input type='text' name='password' value='".$this->fields["password"]."' size='30'/>";
      echo '</td>';
      echo '</tr>';

      $this->showFormButtons($options);

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
         'name'               => __('Alignak instance')
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

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'tag',
         'name'               => __('Tag', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'address',
         'name'               => __('Address', 'alignak'),
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'username',
         'name'               => __('Username', 'alignak'),
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

   /**
    * Give localized information about 1 task
    *
    * @param $name string - name of the task
    *
    * @return array of strings
    */
   static function cronInfo($name) {
      switch ($name) {
         case 'AlignakBuild' :
            return [
               'description' => __('Cron description for alignak', 'alignak'),
               'parameter'   => __('Cron parameter for alignak', 'alignak')
            ];
      }
      return [];
   }

   /**
    * Execute 1 task managed by the plugin
    *
    * Build Alignak instance configuration
    *
    * @param $task Object of CronTask class for log / stat
    *
    * @return integer
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronAlignakBuild($task) {

      $task->log("Example log message from class");
      $r = mt_rand(0, $task->fields['param']);
      usleep(1000000+$r*1000);
      $task->setVolume($r);

      return 1;
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'ComputerDisk' :
            case 'Supplier' :
              return [1 => __("Test Plugin", 'alignak'),
                   2 => __("Test Plugin 2", 'alignak')];

            case 'Computer' :
                $pmHost = new PluginAlignakComputer();
                $pmHost->getTabNameForItem($item, $withtemplate);
              break;

            case 'Central' :
               return __("Alignak monitoring", 'alignak');

            case 'Preference':
            case 'Notification':
              return [1 => __("Alignak monitoring", 'alignak')];
         }
      }
      return '';
   }

   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Central' :
            echo __("Plugin central action", 'alignak');
            PluginAlignakBoard::showHostsBoard();
            break;

         case 'Preference' :
            // Complete form display
            $data = plugin_version_alignak();

            echo "<form action='Where to post form'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>".$data['name']." - ".$data['version'];
            echo "</th></tr>";

            echo "<tr class='tab_bg_1'><td>Name of the pref</td>";
            echo "<td>Input to set the pref</td>";

            echo "<td><input class='submit' type='submit' name='submit' value='submit'></td>";
            echo "</tr>";

            echo "</table>";
            echo "</form>";
            break;

         case 'Notification' :
            echo __("Plugin mailing action", 'alignak');
            break;

         case 'ComputerDisk' :
         case 'Supplier' :
            if ($tabnum==1) {
                echo __('First tab of Plugin alignak', 'alignak');
            } else {
               echo __('Second tab of Plugin alignak', 'alignak');
            }
            break;

         case 'Computer' :
            $pmHost = new PluginAlignakComputer();
            $pmHost->displayTabContentForItem($item, $tabnum, $withtemplate);
            break;

         default :
            //TRANS: %1$s is a class name, %2$d is an item ID
            printf(
                __('Plugin Alignak object type=%1$s id=%2$d, tab: %3$d', 'alignak'),
                $item->getType(), $item->getField('id'), $tabnum
             );

             // $item->displayTabContentForItem($item, $tabnum, $withtemplate);
            break;
      }
         return true;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
          $values = [$field => $values];
      }
      switch ($field) {
         case 'tag' :
          return "tag: ".$values[$field];
      }
         return '';
   }
}
