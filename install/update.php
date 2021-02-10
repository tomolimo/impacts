<?php
/*
 * -------------------------------------------------------------------------
Impacts plugin
Copyright (C) 2021 by Raynet SAS a company of A.Raymond Network.

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

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// ----------------------------------------------------------------------

function impacts_update() {
   global $DB;

   // update from older versions
   // load config to get current version
   if (!$DB->fieldExists("glpi_plugin_impacts_configs", "db_version" )) {
      $current_version = '1.0.0';
   } else {
      include_once(GLPI_ROOT."/plugins/impacts/inc/config.class.php");
      $config = PluginImpactsConfig::getInstance();
      $current_version = $config->fields['db_version'];
      if (empty($current_version)) {
         $current_version = '1.0.0';
      }
   }

   //switch ($current_version) {
   //   case '1.0.0' :
   //       include_once(GLPI_ROOT."/plugins/impacts/install/update_to_2_0_0.php");
   //       $new_version = update_to_2_0_0();
   //}

   include_once(GLPI_ROOT."/plugins/impacts/install/update_to_2_0_0.php");
   $new_version = update_to_2_0_0();

   if (isset($new_version)) {
      // end update by updating the db version number
      $DB->updateOrDie('glpi_plugin_impacts_configs', [
         'db_version' => $new_version
      ], [
         'id' => 1
      ], "error when updating db_version field in glpi_plugin_impacts_configs".$DB->error());
   }

}
