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
   @link      http://alignak.net/
   @since     2018

   ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('alignak') || !$plugin->isActivated('alignak')) {
   Html::displayNotFoundError();
}

// Check for ACLs
if (PluginAlignakAlignak::canView()) {
   // View is granted: display the list.

   // Add page header
   if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
      Html::header(__('Alignak instances', 'alignak'), $_SERVER['PHP_SELF'],
         "admin", "pluginalignakmenu", "alignak");
   } else {
      Html::helpHeader(__('Alignak instances', 'alignak'), $_SERVER['PHP_SELF']);
   }

   Search::show('PluginAlignakAlignak');

   Html::footer();
} else {
   // View is not granted.
   Html::displayRightError();
}
