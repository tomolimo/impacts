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
// Purpose of file: to setup impacts plugin to GLPI
// ----------------------------------------------------------------------


define('IMPACTS_VERSION', '2.0.8');

// Minimal GLPI version, inclusive
define("IMPACTS_MIN_GLPI", "9.5");
// Maximum GLPI version, exclusive
define("IMPACTS_MAX_GLPI", "9.6");

/**
 * Summary of plugin_init_impacts
 */
function plugin_init_impacts() {
   global $PLUGIN_HOOKS, $DB, $CFG_GLPI;

   $plugin = new Plugin();
   if ($plugin->isInstalled('impacts')
        && $plugin->isActivated('impacts')
        && Session::getLoginUserID()) {

      $PLUGIN_HOOKS['post_show_tab']['impacts'] = ['PluginImpactsItemtype', 'post_show_tab_impacts'];

      foreach($DB->request(['FROM' => 'glpi_plugin_impacts_itemtypes']) as $row) {
         if ($plug = isPluginItemType($row['name'])) {
            $plugname = strtolower($plug['plugin']);

            // check plugin exists and is enabled
            if (!Plugin::isPluginLoaded($plugname)) {
               // load plugin class if plugin is not loaded
               Plugin::load($plugname);
            }
         }

         if (class_exists($row['name'])) {
            $CFG_GLPI["impact_asset_types"][$row['name']] = $row['url_path_pics'];
         }
      }

      foreach(Impact::getEnabledItemtypes() as $type) {
         CommonGLPI::registerStandardTab($type, 'Impact');
      }

   }

   $PLUGIN_HOOKS['csrf_compliant']['impacts'] = true;

}

/**
 * Summary of plugin_version_impacts
 * @return string[]
 */
function plugin_version_impacts() {

   return [
      'name'           => 'Asset impacts',
      'version'        => IMPACTS_VERSION,
      'author'         => 'Olivier Moron',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/tomolimo/impacts',
      'requirements'   => [
         'glpi'   => [
            'min' => IMPACTS_MIN_GLPI,
            'max' => IMPACTS_MAX_GLPI,
            //'plugins' => ['appliances', 'accounts']
         ]
      ]
   ];
}


/**
 * Summary of plugin_impacts_check_prerequisites
 * @return bool
 */
function plugin_impacts_check_prerequisites() {
   return true;
}


/**
 * Summary of plugin_impacts_check_config
 * @param mixed $verbose
 * @return bool
 */
function plugin_impacts_check_config($verbose = false) {
   return true;
}

