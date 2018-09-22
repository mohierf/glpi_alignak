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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginAlignakInstall {
   protected $migration;

   /**
    * Install the plugin
    * @param Migration $migration
    *
    * @return boolean
    */
   public function install(Migration $migration) {
      $this->migration = $migration;

      // Drop existing tables if some exist
      $this->dropTables(true);

      $this->installSchema();
      $this->migrateInnoDb();

      // Check class and front files
      plugin_alignak_checkFiles();

      $this->createProfile();

      $this->createCronTasks();

      $this->createDefaultDisplayPreferences();

      Config::setConfigurationValues('alignak', ['schema_version' => PLUGIN_ALIGNAK_VERSION]);

      return true;
   }

   /**
    * Upgrade the plugin
    */
   public function upgrade(Migration $migration) {
      $this->migration = $migration;
      $fromSchemaVersion = $this->getSchemaVersion();

      $this->installSchema();

      // All cases are run starting from the one matching the current schema version
      switch ($fromSchemaVersion) {
         case '0.0':
         case '1.0':
            //Any schema version below or equal 1.0
            require_once(__DIR__ . '/update_0.0_1.0.php');
            plugin_alignak_update_1_0($this->migration);

         default:
            // Must be the last case
            if ($this->endsWith(PLUGIN_ALIGNAK_VERSION, "-dev")) {
               if (is_readable(__DIR__ . "/update_dev.php") && is_file(__DIR__ . "/update_dev.php")) {
                  include_once __DIR__ . "/update_dev.php";
                  $updateDevFunction = 'plugin_alignak_update_dev';
                  if (function_exists($updateDevFunction)) {
                     $updateDevFunction($this->migration);
                  }
               }
            }
      }
      $this->migration->executeMigration();

      $this->createCronTasks();
      Config::setConfigurationValues('alignak', ['schema_version' => PLUGIN_ALIGNAK_VERSION]);

      return true;
   }

   /**
    * Find the version of the plugin
    *
    * @return string|NULL
    */
   protected function getSchemaVersion() {
      if ($this->isPluginInstalled()) {
         return $this->getSchemaVersionFromGlpiConfig();
      }

      return null;
   }

   /**
    * Find version of the plugin in GLPI's config
    *
    * @return string
    */
   protected function getSchemaVersionFromGlpiConfig() {
      global $DB;

      $config = Config::getConfigurationValues('alignak', ['schema_version']);
      if (!isset($config['schema_version'])) {
         // No schema version in GLPI config, then this is older than 2.5
         return '0.0';
      }

      // Version found in GLPI config
      return $config['schema_version'];
   }

   /**
    * is the plugin already installed ?
    *
    * @return boolean
    */
   public function isPluginInstalled() {
      global $DB;

      $result = $DB->query("SHOW TABLES LIKE 'glpi_plugin_alignak_%'");
      if ($result) {
         if ($DB->numrows($result) > 0) {
            return true;
         }
      }

      return false;
   }

   protected function installSchema() {
      global $DB;

      $this->migration->displayMessage("Creating database schema");

      $dbFile = __DIR__ . '/mysql/plugin_alignak_empty.sql';
      if (! $DB->runFile($dbFile)) {
         $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
         die('Giving up!');
      }
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param string $haystack
    * @param string $needle
    *
    * @return boolean
    */
   protected function endsWith($haystack, $needle) {
      // search foreward starting from end minus needle length characters
      return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
   }

   /**
    *
    */
   public function uninstall($drop_tables = false) {
      $config = new Config();
      $config->deleteByCriteria(['context' => 'alignak']);

      // Clean display preferences
      $pref = new DisplayPreference;
      $pref->deleteByCriteria(['itemtype' => ['LIKE' , 'PluginAlignak%']]);

      $this->cleanProfile();

      $this->dropTables($drop_tables);
   }

   /**
    * Drop all the plugin tables
    */
   protected function dropTables() {
      global $DB;

      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Dropping the plugin tables");

      // Drop tables of the plugin if they exist
      $query = "SHOW TABLES";
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {
         if (strstr($data[0], "glpi_plugin_alignak") !== false) {
            Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "- drop: {$data[0]}");

            $DB->query("DROP TABLE " . $data[0]);
         }
      }
   }

   /**
    * Create cron tasks
    */
   protected function createCronTasks() {

      $this->migration->displayMessage("Creating plugin tasks");

      CronTask::Register('PluginAlignakAlignak', 'AlignakBuild', DAY_TIMESTAMP, [
         'comment'   => __('Alignak - to be developed...', 'alignak'),
         'mode'      => CronTask::MODE_EXTERNAL,
         'param' => 50
      ]);
   }

   /**
    * Create profile rights
    */
   protected function createProfile() {

      $this->migration->displayMessage("Creating plugin profile");

      require_once (GLPI_ROOT . "/plugins/alignak/inc/profile.class.php");
      PluginAlignakProfile::initProfile();
      $this->migration->displayMessage("created.");
   }

   /**
    * Clean profile rights
    */
   protected function cleanProfile() {
      require_once (GLPI_ROOT . "/plugins/alignak/inc/profile.class.php");

      // Remove information related to profiles from the session (to clean menu and breadcrumb)
      PluginAlignakProfile::removeRightsFromSession();
      // Remove profiles rights
      PluginAlignakProfile::uninstallProfile();
   }

   /**
    * Migrate tables to InnoDB engine if Glpi > 9.3
    */
   protected function migrateInnoDb() {
      global $DB;

      $this->migration->displayMessage("Migrating tables engine");

      $version = rtrim(GLPI_VERSION, '-dev');
      if (version_compare($version, '9.3', '>=')) {
         $to_migrate = $DB->getMyIsamTables();

         while ($table = $to_migrate->next()) {
            $this->migration->displayMessage("- migrating: {$table['TABLE_NAME']}");
            $DB->queryOrDie("ALTER TABLE {$table['TABLE_NAME']} ENGINE = InnoDB");
         }
      }
   }

   /*
    * Create default display preferences
    */
   protected function createDefaultDisplayPreferences() {
      global $DB;
      $this->migration->displayMessage("create default display preferences");

      // Create standard display preferences
      $displayprefs = new DisplayPreference();
      $found_dprefs = $displayprefs->find("`itemtype` = 'PluginAlignakAlignak'");
      if (count($found_dprefs) == 0) {
         $query = "INSERT IGNORE INTO `glpi_displaypreferences`
                   (`id`, `itemtype`, `num`, `rank`, `users_id`) VALUES
                   (NULL, 'PluginAlignakAlignak', 3, 1, 0),
                   (NULL, 'PluginAlignakAlignak', 4, 2, 0),
                   (NULL, 'PluginAlignakAlignak', 5, 3, 0)";
         $DB->query($query) or die ($DB->error());
      }

      $displayprefs = new DisplayPreference;
      $found_dprefs = $displayprefs->find("`itemtype` = 'PluginAlignakMonitoringTemplate'");
      if (count($found_dprefs) == 0) {
         $query = "INSERT IGNORE INTO `glpi_displaypreferences`
                   (`id`, `itemtype`, `num`, `rank`, `users_id`) VALUES
                   (NULL, 'PluginAlignakMonitoringTemplate', 2, 1, 0);";
         $DB->query($query) or die ($DB->error());
      }
   }
}
