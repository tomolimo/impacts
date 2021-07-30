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


/**
 * itemtype short summary.
 *
 * itemtype description.
 *
 * @version 1.0
 * @author GaubertA
 */
class PluginImpactsItemtype extends CommonDropdown
{

   static function canCreate() {
      return self::canUpdate();
   }

   /**
    * Summary of getTypeName
    * @param mixed $nb plural
    * @return mixed
    */
   static function getTypeName($nb = 0) {
      return __("Impact Itemtypes", "impacts");
   }

   public function maybeTranslated() {
      return false;
   }

   function showForm($id = null, $options = []) {
      global $CFG_GLPI;

      if (!$this->isNewID($id)) {
         $this->check($id, READ);
      } else {
         // Create item
         $this->check(-1, CREATE);
      }

      $this->showFormHeader(['colspan' => 3]);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='name'>".__('Item type class name (example: PluginAccountsAccount,...)', 'impacts')."</label></td>";
      echo "<td><input type='text' id='name' name='name' value='".$this->fields['name']."'/></td>";
      if (isset($this->fields['name'])
         && class_exists($this->fields['name'])) {
         echo '<td>Item type label is "'.$this->fields['name']::getTypeName(1).'"</td>';
      }
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='urlpath'>".__('Icon path (example: \'plugins/impacts/pics/PluginAccountsAccount.png\')', 'impacts')."</label></td>";
      echo "<td><input size=50 type='text' id='urlpath' name='url_path_pics' value='".$this->fields['url_path_pics']."'/></td>";
      $file_path = GLPI_ROOT . "/" . $this->fields['url_path_pics'];
      if (file_exists($file_path)
         && is_file($file_path)) {
         echo "<td><img src='".$CFG_GLPI['root_doc'] . "/" . $this->fields['url_path_pics']."' style='width: 32px; height: 32px;'/></td>";
      } else {
         echo '<td><font color="red">File does not exist! Default icon will be used!</font></td>';
      }
      echo "</tr'>\n";

      if (isset($_REQUEST['_in_modal'])) {
         echo "<input type='hidden' name='_in_modal' value='1'>";
      }

      $this->showFormButtons($options);

   }

   /**
    * Summary of post_item_form_arbehaviours
    * @param array $parms $parms['item'] is a Ticket, TicketTask,...; $parms['options'] are options
    * @return void
    */
   static function post_show_tab_impacts($parms) {

      if ($parms['item']->getType() == 'Config') {
         $item = new PluginImpactsItemtype;
         if ($parms['options']['tabnum'] == 11) {
            if ($item->canCreate()) {
               echo "<button class='vsubmit' onClick=\"$('#add_impacts_itemtype').dialog('open');\"><i class='fas fa-plus'></i> ". __('Add an itemtype') ."</button>";
               echo  Ajax::createIframeModalWindow('add_impacts_itemtype',
                                                         $item->getFormURL(),
                                                         ['display' => false, 'reloadonclose' => true]);
            }
         }

      }
   }


   function prepareInputForAdd($input) {
      if (!class_exists($input['name'])) {
         Session::addMessageAfterRedirect(
               sprintf(__('Item type class: \''.$input['name'].'\' does not exists, can\'t add')),
               true,
               ERROR
            );
         $input = false;
      }
      $elt = new self;
      if ($input && $elt->getFromDBByCrit(['name' => $input['name']])) {
         Session::addMessageAfterRedirect(
               sprintf(__('Item type class: \''.$input['name'].'\' is already existing!')),
               true,
               ERROR
            );
         $input = false;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      if (!class_exists($input['name'])) {
         Session::addMessageAfterRedirect(
               sprintf(__('Item type class: \''.$input['name'].'\' does not exists, can\'t update')),
               true,
               ERROR
            );
         $input = false;
      }
      $elt = new self;
      if ($input && $elt->getFromDBByCrit(['name' => $input['name']]) && $input['url_path_pics'] == $elt->fields['url_path_pics']) {
         Session::addMessageAfterRedirect(
               sprintf(__('Item type class: \''.$input['name'].'\' is already existing!')),
               true,
               ERROR
            );
         $input = false;
      }
      return $input;
   }


   function rawSearchOptions() {
       global $DB;
       $tab = [];

       $tab[] = [
          'id'   => 'common',
          'name' => __('Characteristics')
       ];

       $tab[] = [
          'id'                => '1',
          'table'             => $this->getTable(),
          'field'             => 'name',
          'name'              => __('Name'),
          'datatype'          => 'itemlink',
          'massiveaction'     => false,
          'autocomplete'      => true,
       ];

       $tab[] = [
          'id'                => '2',
          'table'             => $this->getTable(),
          'field'             => 'id',
          'name'              => __('ID'),
          'massiveaction'     => false,
          'datatype'          => 'number'
       ];

       return $tab;
   }
}