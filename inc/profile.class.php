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
     * @param  object  $item         the item object
     * @param  integer $withtemplate 1 if is a template form
     * @return string name of the tab
     */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile'
         && $item->getField('interface') != 'helpdesk') {
         return __('Monitoring', 'alignak');
      }
      return '';
   }

    /**
     * Display the content of the tab
     *
     * @param  CommonGLPI $item
     * @param  integer    $tabnum       number of the tab to display
     * @param  integer    $withtemplate 1 if is a template form
     * @return boolean
     */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $paProfile = new self();
         $self_service = ($item->fields['interface'] != 'central');
         $paProfile->showForm($item->getID(), true, true, $self_service);
      }
      return true;
   }

    /**
     * Display profile form
     *
     * @param  integer $profiles_id
     * @param  boolean $openform
     * @param  boolean $closeform
     * @param  boolean $self_service: true if the profile is the self-service profile
     * @return true
     */
   function showForm($profiles_id = 0, $openform = true, $closeform = true, $self_service = false) {

      echo "<div class='firstbloc $self_service'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform) {
          $profile = new Profile();
          echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = $this->getRightsGeneral($self_service);
      if (! empty($rights)) {
         $profile->displayRightsChoiceMatrix(
            $rights, ['canedit' => $canedit,
            'default_class' => 'tab_bg_2',
            'title' => __('General', 'alignak')]
          );
      }

      $rights = $this->getRightsAlignak($self_service);
      if (! empty($rights)) {
         $profile->displayRightsChoiceMatrix(
            $rights, ['canedit' => $canedit,
            'default_class' => 'tab_bg_2',
            'title' => __('Alignak', 'alignak')]
          );
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
    * Delete profiles
    */
   static function uninstallProfile() {
      $pfProfile = new self();
      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Removing plugin rights from the database\n");
      foreach ($pfProfile->getAllRights() as $data) {
         ProfileRight::deleteProfileRights([$data['field']]);
      }
   }

    /**
     * Get all rights
     *
     * @param $self_service: true if currently using the self-service profile
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
     * Get rights for the plugin monitoring features
     *
     * @return array
     */
   function getRightsAlignak($self_service = false) {
      $rights = [
         ['itemtype' => 'PluginAlignakAlignak',
            'label' => __('Alignak', 'alignak'),
            'field' => 'plugin_alignak_alignak']
      ];

      if (! $self_service) {
         $rights[] = [
            'itemtype' => 'PluginAlignakMonitoringTemplate',
            'label' => __('Monitoring', 'alignak'),
            'field' => 'plugin_alignak_monitoring'
         ];

         $rights[] = [
            'rights' => [READ => __('Read')],
            'label' => __('XxX - Right 2', 'alignak'),
            'field' => 'plugin_alignak_right2'
         ];
      }

      return $rights;
   }

    /**
     * Get general rights
     * - plugin_alignak_central: display Alignak information on the central page
     *
     * @return array
     */
   function getRightsGeneral($self_service = false) {
      $rights = [
         ['rights'    => [READ => __('Read')],
            'label'     => __('Central page', 'alignak'),
            'field'     => 'plugin_alignak_central'],
      ];
      if (! $self_service) {
         // Add a menu in the Administration menu
         $rights[] = [
            'rights'    => [READ => __('Read')],
            'label'     => __('Menu', 'alignak'),
            'field'     => 'plugin_alignak_menu'
         ];

         $rights[] = [
            'rights'    => [READ => __('Read'), UPDATE => __('Update')],
            'itemtype'  => 'PluginAlignakConfig',
            'label'     => __('Configuration', 'alignak'),
            'field'     => 'plugin_alignak_configuration'
         ];

         $rights[] = [
            'itemtype'  => 'PluginAlignakCounter',
            'label'     => __('Counters', 'alignak'),
            'field'     => 'plugin_alignak_counters'
         ];

         $rights[] = [
            'itemtype'  => 'PluginAlignakDashboard',
            'label'     => __('Dashboards', 'alignak'),
            'field'     => 'plugin_alignak_dashboard'
         ];

         $rights[] = [
            'itemtype'  => 'PluginAlignakMailNotification',
            'label'     => __('Mail notifications', 'alignak'),
            'field'     => 'plugin_alignak_mailnotification'
         ];
      }

      return $rights;
   }

   /**
     * Delete rights stored in the session
     */
   static function removeRightsFromSession() {
      // Get current profile
      $profile = new self();
      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Removing plugin rights from the session\n");
      foreach ($profile->getAllRights() as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   /**
     * Init profiles during installation:
     * - add rights in profile table for the current user's profile
     * - current profile has all rights on the plugin
     */
   static function initProfile() {
      $paProfile = new self();
      $dbu = new DbUtils();

      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Initializing plugin profile rights:\n");
      // Add all plugin rights to the current user profile
      if (isset($_SESSION['glpiactiveprofile']) && isset($_SESSION['glpiactiveprofile']['id'])) {
         // Set the plugin profile rights for the currently used profile
         self::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
      } else {
         // No current profile!
         Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "No current profile in the session!\n");
      }
   }

   /**
    * Create first access (so default profile)
    *
    * @param integer $profiles_id id of the profile
    */
   static function createFirstAccess($profiles_id) {
      include_once GLPI_ROOT."/plugins/alignak/inc/profile.class.php";
      $profile = new self();
      foreach ($profile->getAllRights() as $right) {
         self::addDefaultProfileInfos($profiles_id, [$right['field'] => ALLSTANDARDRIGHT]);
      }
   }

   /**
    * Add the default profile rights
    *
    * @param integer $profiles_id
    * @param array   $rights
    */
   static function addDefaultProfileInfos($profiles_id, $rights) {
      $dbu = new DbUtils();
      $profileRight = new ProfileRight();

      // Get current profile
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Add default rights for the profile: {$profile->getName()}\n");

      foreach ($rights as $right => $value) {
         // If it does not yet exists...
         if ($profileRight->getFromDBByCrit(["WHERE" => "`profiles_id`='$profiles_id' AND `name`='$right'"])) {
            // Update the profile right
            $myright['rights']      = $value;
            $profileRight->update($myright);
            Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "- updating: $right = $value\n");
         } else {
            // Create the profile right
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);
            Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "- added: $right = $value\n");
         }

         // Update right in the current session
         $_SESSION['glpiactiveprofile'][$right] = $value;
      }
   }
}
