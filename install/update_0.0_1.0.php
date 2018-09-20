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
 * Upgrade any version of Alignak < 1.0.0 to 1.0.0
 * -----------------
 * Note that this is only an example of what should be implemented
 * -----------------
 * @param Migration $migration
 */
function plugin_alignak_update_1_0(Migration $migration) {
   global $DB;

   $migration->displayMessage("Upgrade to version 1.0");

   plugin_alignak_updateTable_1_0($migration);

   $migration->executeMigration();
}

function plugin_alignak_updateTable_1_0(Migration $migration) {
   global $DB;

   /*
   // Legacy upgrade of a table...
   $migration->displayMessage("Upgrade glpi_plugin_alignak_tables");
   // Update field type from previous version (Need answer to be text since text can be WYSIWING).
   $query = "ALTER TABLE  `glpi_plugin_alignak_alignaks` CHANGE  `comment` `comment` text;";
   $DB->query($query) or plugin_alignak_upgrade_error($migration);
   */

   /*
   $query  = "SELECT `id`, `answer` FROM `glpi_plugin_alignak_answers`";
   $result = $DB->query($query);
   while ($line = $DB->fetch_array($result)) {
      $query_update = "UPDATE `glpi_plugin_alignak_answers` SET
      `answer` = '".addslashes($line['answer'])."'
                          WHERE `id` = ".$line['id'];
      $DB->query($query_update) or plugin_alignak_upgrade_error($migration);
   }

   //rename foreign key, to match table plugin_alignak_forms_answers name
   $migration->changeField('glpi_plugin_alignak_answers',
         'plugin_alignak_formanwers_id',
         'plugin_alignak_forms_answers_id',
         'integer');

   $migration->addKey('glpi_plugin_alignak_answers', 'plugin_alignak_forms_answers_id');
   $migration->addKey('glpi_plugin_alignak_answers', 'plugin_alignak_question_id');

   $migration->migrationOneTable('glpi_plugin_alignak_answers');
   */
}
