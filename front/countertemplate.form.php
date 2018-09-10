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

// var_dump( $_POST);


include ('../../../inc/includes.php');

if ($_POST && isset($_POST['save']) && isset($_POST['id'])) {
   // Check that a link has been passed
   if (!isset($_POST['name']) or empty($_POST['name'])) {
     Html::displayErrorAndDie('Please specified a template name');
   }

   $counterTemplate = new PluginAlignakCounterTemplate();

   $counterTemplate->getFromDB($_POST['id']);
   // Update counterTemplate to the DataBase
   if ($counterTemplate->update($_POST)) {
      Session::addMessageAfterRedirect(__('The template has been successfully updated!', 'alignak'), true, INFO);
   }
 
   // Redirect the user to the Template Page
   $url = explode("?", $_SERVER['HTTP_REFERER']);
   Html::redirect($url[0] . "?id=" . $_POST['id']);
}
else {
   $template = new PluginAlignakCounterTemplate();
   Html::header(__('Template'), '', "tools", "pluginalignak", "config");
  
   $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : -1;
   
   $template->display($_GET);
   Html::footer();
}

