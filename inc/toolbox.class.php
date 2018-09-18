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
// Purpose of file: some utility functions
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}
global $PLUGIN_ALIGNAK_LOG;

/**
 * Toolbox of various utility methods
 **/
class PluginAlignakToolbox
{

   /**
    * Log when extra-debug is activated
    *
    * @param $message string or array
    */
   static function log($message) {
      /*
      * Call the Glpi base file logging function:
      * - base filename
      * - log message
      * - force file logging - not set to use the default Glpi configuration (use_log_in_files)
      */
      if (is_array($message)) {
         $message = print_r($message, true);
      }
      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, $message . "\n");
   }

   /**
    * Log when extra-debug is activated
    *
    * @param $message string or array
    */
   static function logIfDebug($message) {
      $config = new PluginAlignakConfig();
      if ($config->getValue('extra_debug')) {
         if (is_array($message)) {
            $message = print_r($message, true);
         }
         PluginAlignakToolbox::log($message);
      }
   }

}
