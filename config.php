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
// Purpose of file: plugin configuration management
// ----------------------------------------------------------------------

// Non menu entry case
//header("Location:../../central.php");

// Entry menu case
define('GLPI_ROOT', '../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", UPDATE);

// To be available when plugin in not activated
Plugin::load('alignak');

global $PLUGIN_ALIGNAK_NAME;
Html::header(__($PLUGIN_ALIGNAK_NAME . " - configuration page", 'alignak'), $_SERVER['PHP_SELF'], "config", "plugins");
echo __("This is the plugin configuration page", 'alignak');
echo ("<br>");



/*
 * Test Twig for templating system similar to jinja templates!
 * See: https://twig.symfony.com/doc/2.x/templates.html for the templating patterns
 * See: https://twig.symfony.com/doc/2.x/api.html for the templating implementation
 */
require_once 'vendor/autoload.php';

try {
   $loader = new Twig_Loader_Filesystem('templates');
   $twig = new Twig_Environment($loader, array('cache' => GLPI_PLUGIN_DOC_DIR . '/templates_cache'));

   $template = $twig->load('host.cfg');
   $result = $template->render(array('name' => 'localhost', 'address' => '127.0.0.1'));
   echo nl2br("\nTemplate result is: \n", true);
   echo nl2br($result, true);
} catch (Twig_Error_Loader $e) {
   // Could not get the templates, raise an error !
   Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates are not available:", 'alignak'), true, ERROR);
   Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
} catch (RuntimeException $e) {
   // Could not parse the templates, raise an error !
   Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates runtime exception:", 'alignak'), true, ERROR);
   Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
}







Html::footer();
