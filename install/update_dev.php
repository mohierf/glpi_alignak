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

/**
 * Upgrade during development
 * -----------------
 *  Append tables modifications at the end of this function and make the code non regressive !
 * -----------------
 * @param Migration $migration
 */
function plugin_alignak_update_dev(Migration $migration) {
   global $DB;

   // 2018-09-24 - Configuration table
   $newTable = "glpi_plugin_alignak_configs";
   if (! $DB->TableExists($newTable)) {
      $query = "CREATE TABLE `".$newTable."` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `timezones` varchar(255) NOT NULL DEFAULT '[\"0\"]',
                 `extradebug` tinyint(1) NOT NULL DEFAULT '0',
                 `alignak_webui_url` varchar(255) DEFAULT 'http://127.0.0.1:5001',
                 `alignak_backend_url` varchar(255) DEFAULT 'http://127.0.0.1:5000',
                 `graphite_url` varchar(255) DEFAULT 'http://127.0.0.1:8080',
                 `graphite_prefix` varchar(255) DEFAULT '',
                  PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query);
   }

   // 2018-09-24 - Monitoring users table
   $newTable = "glpi_plugin_alignak_users";
   if (! $DB->TableExists($newTable)) {
      $query = "CREATE TABLE `".$newTable."` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `users_id` int(11) NOT NULL DEFAULT '0',
                 `backend_login` varchar(255) DEFAULT NULL,
                 `backend_password` varchar(255) DEFAULT NULL,
                 `backend_token` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query);
   }


   // 2018-09-20 - Update the counters table
   $newTable = "glpi_plugin_alignak_counters";
   $migration->displayMessage("Upgrade $newTable");

   // Add fields if they do not exist
   $migration->addField($newTable,
      'graphite_name',
      "varchar(255) DEFAULT NULL");
   // Update field type from previous version (Need answer to be text since text can be WYSIWING).
//   $query = "ALTER TABLE  `glpi_plugin_alignak_counters` ADD  `graphite_name` VARCHAR(255);";
   $migration->migrationOneTable($newTable);

   // 2018-09-22 - Computers table
   $newTable = "glpi_plugin_alignak_computers";
   if (! $DB->TableExists($newTable)) {
      $query = "CREATE TABLE `".$newTable."` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `itemtype` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
                  `items_id` int(11) NOT NULL
                  PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query);
   }

   // Add fields if they do not exist
   $migration->addField($newTable,
      'last_check',
      "datetime DEFAULT NULL");
   $migration->addField($newTable,
      'state',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");
   $migration->addField($newTable,
      'state_type',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");
   $migration->addField($newTable,
      'perf_data',
      "text COLLATE utf8_unicode_ci DEFAULT NULL");
   $migration->addField($newTable,
      'output',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");

   // Update fields if they exist
   $migration->changeField($newTable,
      'last_check',
      'last_check',
      "datetime DEFAULT NULL");
   $migration->changeField($newTable,
      'state',
      'state',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");
   $migration->changeField($newTable,
      'state_type',
      'state_type',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");
   $migration->changeField($newTable,
      'perf_data',
      'perf_data',
      "text DEFAULT NULL COLLATE utf8_unicode_ci");
   $migration->changeField($newTable,
      'output',
      'output',
      "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL");

   $migration->migrationOneTable($newTable);
//   $DB->query($query) or plugin_alignak_upgrade_error($migration);
}
