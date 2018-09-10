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
// Original Author of file: Francois Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ('../../../inc/includes.php');

if ($_POST && isset($_POST['_glpi_csrf_token']) && isset($_POST['computer_id'])) {
   // Check that a link has been passed
   if (!isset($_POST['template']) or empty($_POST['template'])) {
     Html::displayErrorAndDie('Please specified a template');
   }

   // Load the Computer that need association with that given template
   $computerCounterTemplate = new PluginAlignakComputerCounterTemplate();

   $computerCounterTemplate->getFromDB($_POST['computer_id']);

   $computerCounterTemplate->fields['computer_id'] = $_POST['computer_id'];
   $computerCounterTemplate->fields['template_id'] = $_POST['template'];

   // var_dump( $computerCounterTemplate);
   // Save the new computerCounterTemplate to the DataBase
   $computerCounterTemplate->addToDB();

   // Redirect the user to the Computer Page
   $url = explode("?", $_SERVER['HTTP_REFERER']);
   Html::redirect($url[0] . "?id=" . $_POST['computer_id']);
}

