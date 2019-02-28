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

define('IMPACTS_VERSION', '1.1.0');

/**
 * Summary of plugin_init_impacts
 */
function plugin_init_impacts() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

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
   }

   $PLUGIN_HOOKS['csrf_compliant']['impacts'] = true;

}

/**
 * Summary of plugin_version_impacts
 * @return string[]
 */
function plugin_version_impacts() {

   return ['name'           => 'Asset impacts',
                'version'        =>IMPACTS_VERSION,
                'author'         => 'Olivier Moron',
                'license'        => 'GPLv2+',
                'homepage'       => 'https://github.com/tomolimo/impacts',
                'requirements'   => [
                  'glpi'   => [
                     'min' => '9.2',
                     'max' => '9.2.99',
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
   global $DB, $LANG;

   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, '9.2', 'lt')) {
      return false;
   }

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

