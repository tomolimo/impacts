<?php
/*
 * -------------------------------------------------------------------------
Impacts plugin
Copyright (C) 2018 by Raynet SAS a company of A.Raymond Network.

http://www.araymond.com
-------------------------------------------------------------------------

LICENSE

This file is part of Impacts plugin for GLPI.

This file is free software; you can redistribute it and/or modify
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

/**
 * Summary of plugin_impacts_install
 * @return true or die!
 */
function plugin_impacts_install() {
   global $DB;

   if (!$DB->tableExists("glpi_plugin_impacts_configs")) {
      // new installation
      include_once(GLPI_ROOT."/plugins/impacts/install/install.php");
      impacts_install();

   } else {
      //// upgrade installation
      include_once(GLPI_ROOT."/plugins/impacts/install/update.php");
      impacts_update();
   }

   return true;
}


function plugin_impacts_uninstall() {
   return true;
}

function plugin_item_purge_impacts($item) {
   global $DB;
   $id   = $item->fields['id'];
   $type = $item->getType();
   $crit = [
      'OR' => [
         [
            'AND' => [
               'itemtype_1' => $type,
               'items_id_1' => $id
            ]
         ],
         [
            'AND' => [
               'itemtype_2' => $type,
               'items_id_2' => $id
            ]
         ]
      ]
   ];
   $DB->delete(
      'glpi_plugin_impacts_impacts',
      $crit
      );
   return true;
}

