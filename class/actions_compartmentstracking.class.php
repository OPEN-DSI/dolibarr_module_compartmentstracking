<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

dol_include_once('/compartmentstracking/lib/compartmentstracking.lib.php');

/**
 *  \file       htdocs/efia/class/actions_compartmentstracking.class.php
 *  \ingroup    compartmentstracking
 *  \brief      File for hooks
 */

class ActionsCompartmentsTracking
{
	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		if (in_array($parameters['currentcontext'], array('stockproductcard'))
			&& ($action === 'correction' || $action === 'transfert')) {

			// Add our entrepot compartments list code, to the stock correction screen.
			echo compartmentstracking_warehouse_compartments_select_js( $action, $object->id );
		}

		if (in_array($parameters['currentcontext'], array('ordersuppliercard'))
			&& basename( $_SERVER['PHP_SELF'] ) === 'dispatch.php') {

			// Add our entrepot compartments list code, to the supplier order dispatch screen.
			echo compartmentstracking_warehouse_compartments_select_js( 'dispatch', $object, 'ordersupplier' );
		}

		if (in_array($parameters['currentcontext'], array('synergiestechmassstockmove'))
			&& basename( $_SERVER['PHP_SELF'] ) === 'massstockmove.php') {

			// Add our compartments lists, to the mass stock move screen.
			echo compartmentstracking_product_select_ajax_js( 'massstockmove' );
		}

		return 0;
	}


	/**
	 * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		$get_action = GETPOST( 'action', 'alpha' );

		if (in_array($parameters['currentcontext'], array('movementlist'))
			&& ($get_action === 'correction' || $get_action === 'transfert')) {

			$warehouse_id = GETPOST( 'id', 'int' );

			// Add our entrepot compartments list code, to the stock correction screen.
			echo compartmentstracking_warehouse_compartments_select_js( $get_action, $warehouse_id, 'warehouse' );
		}

		return 0;
	}
}

