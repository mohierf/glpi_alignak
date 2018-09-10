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

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "config";

   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::createTabEntry(__('Monitoring configuration', 'alignak'));
   }


   /**
    * Display the content of the tab
    *
    * @param CommonGLPI $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $pfProfile = new self();
      if ($item->fields['interface'] == 'central') {
         $pfProfile->showForm($item->getID());
      } else {
         $pfProfile->showFormSelfService($item->getID());
      }
      return true;
   }


   /**
    * Display form
    *
    * @param integer $profiles_id
    * @param boolean $openform
    * @param boolean $closeform
    * @return true
    */
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
         && $openform) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = $this->getRightsGeneral(false);
      if (! empty($rights)) {
         $profile->displayRightsChoiceMatrix($rights, ['canedit' => $canedit,
            'default_class' => 'tab_bg_2',
            'title' => __('General', 'alignak')]);
      }

      $rights = $this->getRightsAlignak(false);
      if (! empty($rights)) {
         $profile->displayRightsChoiceMatrix($rights, ['canedit' => $canedit,
            'default_class' => 'tab_bg_2',
            'title' => __('Alignak', 'alignak')]);
      }

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";

      $this->showLegend();
      return true;
   }


   /**
    * Display profile form for helpdesk simplified interface
    *
    * @param integer $profiles_id
    * @param boolean $openform
    * @param boolean $closeform
    */
   function showFormSelfService($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = $this->getRightsGeneral(true);
      if (! empty($rights)) {
         $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => __('General', 'alignak')]);
      }

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";

      $this->showLegend();
   }


   /**
    * Delete profiles
    */
   static function uninstallProfile() {
      $pfProfile = new self();
      $a_rights = $pfProfile->getAllRights();
      foreach ($a_rights as $data) {
         ProfileRight::deleteProfileRights([$data['field']]);
      }
   }


   /**
    * Get all rights
    *
    * @return array
    */
   function getAllRights($self_service = false) {
      $a_rights = [];
      $a_rights = array_merge($a_rights, $this->getRightsGeneral($self_service));
      $a_rights = array_merge($a_rights, $this->getRightsAlignak($self_service));
      return $a_rights;
   }


   /**
    * Get rights for inventory part
    *
    * @return array
    */
   function getRightsAlignak($self_service = false) {
      $rights = [
         ['itemtype'  => 'PluginAlignakAlignak',
            'label'     => __('XxX - Alignak', 'alignak'),
            'field'     => 'plugin_alignak_alignak',
            'rights'    => [READ => __('Read')]],
         ['rights'    => [READ => __('Read')],
            'label'     => __('XxX - Right 2', 'alignak'),
            'field'     => 'plugin_alignak_right2']
      ];
      return $rights;
   }


   /**
    * Get general rights
    *
    * @return array
    */
   function getRightsGeneral($self_service = false) {
      $rights = [
         ['rights'    => [READ => __('Read')],
            'label'     => __('XxX - Central page', 'alignak'),
            'field'     => 'plugin_alignak_central'],
         ['rights'    => [READ => __('Read')],
            'label'     => __('XxX - Login page', 'alignak'),
            'field'     => 'plugin_alignak_login'],
      ];
      if (! $self_service) {
         array_push($rights,
            ['rights'    => [READ => __('Read')],
               'label'     => __('XxX - Menu', 'alignak'),
               'field'     => 'plugin_alignak_menu'],
            ['rights'    => [READ => __('Read'), UPDATE => __('Update')],
               'itemtype'  => 'PluginAlignakConfig',
               'label'     => __('XxX - Configuration', 'alignak'),
               'field'     => 'plugin_alignak_configuration']
//            ['itemtype'  => 'PluginAlignakTask',
//               'label'     => __('XxX - Tasks', 'alignak'),
//               'field'     => 'plugin_alignak_tasks']
         );
      }

      return $rights;
   }


   /**
    * Add the default profile
    *
    * @param integer $profiles_id
    * @param array $rights
    */
   static function addDefaultProfileInfos($profiles_id, $rights) {
      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable('glpi_profilerights',
            "`profiles_id`='$profiles_id' AND `name`='$right'")) {
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
    * Create first access (so default profile)
    *
    * @param integer $profiles_id id of profile
    */
   static function createFirstAccess($profiles_id) {
      include_once(GLPI_ROOT."/plugins/alignak/inc/profile.class.php");
      $profile = new self();
      foreach ($profile->getAllRights() as $right) {
         self::addDefaultProfileInfos($profiles_id,
            [$right['field'] => ALLSTANDARDRIGHT]);
      }
   }


   /**
    * Delete rights stored in session
    */
   static function removeRightsFromSession() {
      $profile = new self();
      foreach ($profile->getAllRights() as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
      ProfileRight::deleteProfileRights([$right['field']]);

      if (isset($_SESSION['glpimenu']['plugins']['types']['PluginAlignakMenu'])) {
         unset ($_SESSION['glpimenu']['plugins']['types']['PluginAlignakMenu']);
      }
      if (isset($_SESSION['glpimenu']['plugins']['content']['PluginAlignakMenu'])) {
         unset ($_SESSION['glpimenu']['plugins']['content']['PluginAlignakMenu']);
      }
   }


   /**
    * Init profiles during installation:
    * - add rights in profile table for the current user's profile
    * - current profile has all rights on the plugin
    */
   static function initProfile() {
      $pfProfile = new self();
      $profile   = new Profile();
      $a_rights  = $pfProfile->getAllRights();

      foreach ($a_rights as $data) {
         if (countElementsInTable("glpi_profilerights", "`name` = '".$data['field']."'") == 0) {
            ProfileRight::addProfileRights([$data['field']]);
            $_SESSION['glpiactiveprofile'][$data['field']] = 0;
         }
      }

      // Add all rights to current profile of the user
      if (isset($_SESSION['glpiactiveprofile'])) {
         $dataprofile       = [];
         $dataprofile['id'] = $_SESSION['glpiactiveprofile']['id'];
         $profile->getFromDB($_SESSION['glpiactiveprofile']['id']);
         foreach ($a_rights as $info) {
            if (is_array($info)
               && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
               && (!empty($info['label'])) && (!empty($info['field']))) {

               if (isset($info['rights'])) {
                  $rights = $info['rights'];
               } else {
                  $rights = $profile->getRightsFor($info['itemtype']);
               }
               foreach ($rights as $right => $label) {
                  $dataprofile['_'.$info['field']][$right] = 1;
                  $_SESSION['glpiactiveprofile'][$data['field']] = $right;
               }
            }
         }
         $profile->update($dataprofile);
      }
   }
}
