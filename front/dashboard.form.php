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

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

Html::header(
   __('Alignak - dashboards', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'dashboard');

$paDashboard = new PluginAlignakDashboard();

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_dashboard", READ);

if (isset($_GET["id"])) {
   $paDashboard->showForm($_GET['id'], -1, array( 'canedit'=>PluginKiosksDashboard::canUpdate(), 'colspan'=>4 ));
} else {
   $paDashboard->showForm(-1);
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_dashboard", UPDATE);

if (isset($_POST["copy"])) {
   $paDashboard->showForm(-1, -1, array( 'canedit'=>PluginAlignakDashboard::canUpdate(), 'colspan'=>4 ), $_POST);
   Html::footer();
   exit;
} else if (isset ($_POST["update"])) {
   $_POST = $paDashboard->convertPostdata($_POST);
   $paDashboard->update($_POST);
   Html::back();
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_dashboard", CREATE);

if (isset ($_POST["add"])) {
   $_POST = $paDashboard->convertPostdata($_POST);
   $paDashboard->add($_POST);
   Html::back();
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_dashboard", DELETE);

if (isset ($_POST["delete"])) {
   $paDashboard->delete($_POST);
   $paDashboard->redirectToList();
}

Html::footer();
