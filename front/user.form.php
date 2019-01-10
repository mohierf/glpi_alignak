<?php

/*
   ------------------------------------------------------------------------
   Plugin Monitoring for GLPI
   Copyright (C) 2011-2016 by the Plugin Monitoring for GLPI Development Team.

   https://forge.indepnet.net/projects/monitoring/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Plugin Monitoring project.

   Plugin Monitoring for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Plugin Monitoring for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Monitoring. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Monitoring for GLPI
   @author    David Durieux
   @co-author
   @comment
   @copyright Copyright (c) 2011-2016 Plugin Monitoring for GLPI team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/monitoring/
   @since     2011

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

Session::checkRight("plugin_alignak_alignak", UPDATE);

$object = new PluginAlignakUser();
if (isset($_POST["add"])) {
   Session::checkRight('plugin_alignak_alignak', CREATE);
   $object->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   Session::checkRight('plugin_alignak_alignak', UPDATE);
   $object->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   Session::checkRight('plugin_alignak_alignak', DELETE);
   $object->delete($_POST);
   $object->redirectToList();
} else if (isset($_POST['purge'])) {
   // Check PURGE ACL
   Session::checkRight('plugin_alignak_alignak', PURGE);
   // Do object purge
   $object->delete($_POST, 1);
   // Redirect to objects list
   $object->redirectToList();
}

Html::header(
   __('Monitoring user', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'user');

Html::footer();


