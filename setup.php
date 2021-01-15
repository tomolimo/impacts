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

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: to setup office online plugin to GLPI
// ----------------------------------------------------------------------


define('IMPACTS_VERSION', '1.3.7');

// Minimal GLPI version, inclusive
define("IMPACTS_MIN_GLPI", "9.3");
// Maximum GLPI version, exclusive
define("IMPACTS_MAX_GLPI", "9.6");

/**
 * Summary of plugin_init_impacts
 */
function plugin_init_impacts() {
   global $PLUGIN_HOOKS, $DB;

   $plugin = new Plugin();
   if ($plugin->isInstalled('impacts')
        && $plugin->isActivated('impacts')
        && Session::getLoginUserID()) {

      if (Session::haveRightsOr("config", [READ, UPDATE])) {
         Plugin::registerClass('PluginImpactsConfig', ['addtabon' => 'Config']);
         $PLUGIN_HOOKS['config_page']['impacts'] = 'front/config.form.php';
      }

      $conf = PluginImpactsConfig::getInstance();
      foreach ($conf->fields['assets'] as $asset) {
         Plugin::registerClass('PluginImpactsImpact', ['addtabon' => $asset]);
      }

      $sub1 = new QuerySubQuery(['SELECT DISTINCT' => 'itemtype_1 AS itemtype', 'FROM' => 'glpi_plugin_impacts_impacts']);
      $sub2 = new QuerySubQuery(['SELECT DISTINCT' => 'itemtype_2 AS itemtype', 'FROM' => 'glpi_plugin_impacts_impacts']);
      $union = new QueryUnion([$sub1, $sub2]);
      $itemtypes = $DB->request(['FROM' => $union]);

      foreach ($itemtypes as $row) {
         $PLUGIN_HOOKS['item_purge']['impacts'][$row['itemtype']] = 'plugin_item_purge_impacts';
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

