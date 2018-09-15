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
header('Content-Type: text/javascript');
?>

var modalWindow;
var rootDoc          = CFG_GLPI['root_doc'];

$(function() {
   var target = $('body');
   modalWindow = $("<div></div>").dialog({
      width: 980,
      autoOpen: false,
      height: "auto",
      modal: true,
      position: ['center', 50],
      open: function( event, ui ) {
         //remove existing tinymce when reopen modal (without this, tinymce don't load on 2nd opening of dialog)
         modalWindow.find('.mce-container').remove();
      }
   });
});

// === COUNTERS ===
var urlCounter      = rootDoc + "/plugins/alignak/ajax/counter.php";
var urlFrontCounter = rootDoc + "/plugins/alignak/front/counter.form.php";

function addCounter(items_id, token, section) {
   modalWindow.load(urlCounter, {
      section_id: section,
      form_id: items_id,
      _glpi_csrf_token: token
   })
   .dialog("open");
}

function editCounter(items_id, token, counter, section) {
   modalWindow.load(urlCounter, {
      counter_id: counter,
      section_id: section,
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function deleteCounter(items_id, token, counter_id) {
   if(confirm("<?php echo __('Are you sure you want to delete this counter?', 'alignak'); ?> ")) {
      jQuery.ajax({
        url: urlFrontCounter,
        type: "POST",
        data: {
            id: counter_id,
            delete_counter: 1,
            plugin_alignak_counters_id: items_id,
            _glpi_csrf_token: token
         }
      }).done(reloadTab);
   }
}





