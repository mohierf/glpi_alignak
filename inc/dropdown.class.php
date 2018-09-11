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
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

// Class for a Dropdown
class PluginAlignakDropdown extends CommonDropdown
{


   static function install(Migration $migration) {
       global $DB;

       $table = self::getTable();

      if (!$DB->tableExists($table)) {
          $migration->displayMessage(sprintf(__("Installing %s"), $table));

          $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id`                                INT(11)  NOT NULL auto_increment,
                  `profiles_id`                       INT(11)  NOT NULL DEFAULT '0',
                  `plugin_fields_containers_id`       INT(11)  NOT NULL DEFAULT '0',
                  `right`                             CHAR(1)  DEFAULT NULL,
                  PRIMARY KEY                         (`id`),
                  KEY `profiles_id`                   (`profiles_id`),
                  KEY `plugin_fields_containers_id`   (`plugin_fields_containers_id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
          $DB->query($query) or die($DB->error());
      }

         return true;
   }

   static function uninstall() {
       global $DB;

       $DB->query("DROP TABLE IF EXISTS `".self::getTable()."`");

       return true;
   }

   static function getTypeName($nb = 0) {

      if ($nb > 0) {
          return __('Plugin Example Dropdowns', 'example');
      }
         return __('Plugin Example Dropdowns', 'example');
   }
}
