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

// var_dump( $_POST);


include ('../../../inc/includes.php');

Html::header(
   __('Monitoring templates', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'monitoring_template');

$paMonitoringTemplate = new PluginAlignakMonitoringTemplate();
if (isset($_POST["copy"])) {
   $paMonitoringTemplate->showForm(-1, -1, [ 'canedit'=>PluginAlignakMonitoringTemplate::canUpdate(), 'colspan'=>4 ], $_POST);
   Html::footer();
   exit;
} else if (isset ($_POST["add"])) {
   $paMonitoringTemplate->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $paMonitoringTemplate->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $paMonitoringTemplate->delete($_POST);
   $paMonitoringTemplate->redirectToList();
} else if (isset ($_POST["send"])) {
   Session::crongetCSVDailyCounters($_POST['id']);
   Html::back();
}

if (isset($_GET["id"])) {
   $paMonitoringTemplate->showForm($_GET['id'], -1, [ 'canedit'=>PluginAlignakMonitoringTemplate::canUpdate(), 'colspan'=>4 ]);
} else {
   $paMonitoringTemplate->showForm();
}

Html::footer();
