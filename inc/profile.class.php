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

class PluginAlignakProfile extends Profile
{

   static $rightname = "profile";

   static function getAllRights() {

      $rights = [
         ['itemtype'  => 'PluginAlignakEntity',
            'label'     => __('Entity management', 'alignak'),
            'field'     => 'plugin_alignak_entity'],
         ['itemtype'  => 'PluginAlignakComputer',
            'label'     => __('Computer management', 'alignak'),
            'field'     => 'plugin_alignak_computer',
            'rights'    => [READ => __('Read')]]];
      return $rights;
   }

   /**
    * Clean profiles_id from plugin's profile table
    *
    * @param $ID
    **/
   function cleanProfiles($ID) {

      global $DB;
      $query = "DELETE FROM `glpi_profiles`
                WHERE `profiles_id`='$ID'
                   AND `name` LIKE '%plugin_alignak%'";
      $DB->query($query);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         if ($item->getField('interface') == 'central') {
            return __('Monitoring profiles', 'alignak');
         }
         return '';
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $profile = new self();
         $ID   = $item->getField('id');
         //In case there's no right alignak for this profile, create it
         self::addDefaultProfileInfos(
            $item->getID(),
            ['plugin_alignak_entity' => 0]
         );
         $profile->showForm($ID);
      }
      return true;
   }

   /**
    * @param $profile
    **/
   static function addDefaultProfileInfos($profiles_id, $rights) {

      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable(
            'glpi_profilerights',
            "`profiles_id`='$profiles_id' AND `name`='$right'"
         )) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   /**
    * @param $ID  integer
    */
   static function createFirstAccess($profiles_id) {

      include_once GLPI_ROOT."/plugins/alignak/inc/profile.class.php";
      foreach (self::getAllRights() as $right) {
         self::addDefaultProfileInfos(
            $profiles_id,
            ['plugin_alignak_entity' => ALLSTANDARDRIGHT,
               'plugin_alignak_computer' => READ]
         );
      }
   }

   static function migrateProfiles() {
      global $DB;
      if (!$DB->tableExists('glpi_plugin_alignak_profiles')) {
         return true;
      }

      $profiles = getAllDatasFromTable('glpi_plugin_alignak_profiles');
      foreach ($profiles as $id => $profile) {
         $query = "SELECT `id` FROM `glpi_profiles` WHERE `name`='".$profile['name']."'";
         $result = $DB->query($query);
         if ($DB->numrows($result) == 1) {
            $id = $DB->result($result, 0, 'id');
            switch ($profile['entity']) {
               case 'r' :
                  $value = READ;
                  break;
               case 'w':
                  $value = ALLSTANDARDRIGHT;
                  break;
               case 0:
               default:
                  $value = 0;
                  break;
            }
            self::addDefaultProfileInfos($id, ['plugin_alignak_entity' => $value]);
            if ($value > 0) {
               self::addDefaultProfileInfos($id, ['plugin_alignak_computer' => READ]);
            } else {
               self::addDefaultProfileInfos($id, ['plugin_alignak_entity' => 0]);
            }
         }
      }
   }

   /**
    * Show profile form
    *
    * @param $items_id integer id of the profile
    * @param $target value url of target
    *
    * @return nothing
    **/
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
         && $openform
      ) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = self::getAllRights();
      $profile->displayRightsChoiceMatrix(
         self::getAllRights(),
         ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => __('General')]
      );
      if ($canedit
         && $closeform
      ) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}
