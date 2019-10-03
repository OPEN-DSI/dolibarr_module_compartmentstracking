<?php
/* Copyright (C) 2018  Open-DSI  <info@open-dsi.fr>
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

/**
 *   	\file       htdocs/compartmentstracking/includes/entrepotcompartments_list_action.inc.php
 *		\ingroup    includes
 *		\brief      List action for EntrepotCompartments
 */

global $fk_entrepot;

$list_action = GETPOST('list_action', 'alpha');

if ( $list_action ) {
	$compartments = compartments_tracking()->get_compartments( $fk_entrepot );

	$neutralize_list = (array) $_POST['neutralize'];

	if ( $list_action === 'neutralize' ) {

		foreach ( $compartments as $compartment ) {

			if ( ! in_array( $compartment->id, $neutralize_list ) ) {

				continue;
			}

			$compartment->neutralize( $user );

			if ( $compartment->errors ) {

				dol_htmloutput_errors( '', $compartment->errors );
			}
		}

		$list_action = '';

		header( "Location: " . $_SERVER['PHP_SELF'] . "?id=" . $fk_entrepot );
		exit();
	}

	$activate_list = (array) $_POST['activate'];

	if ( $list_action === 'activate' ) {

		foreach ( $compartments as $compartment ) {

			if ( ! in_array( $compartment->id, $activate_list ) ) {

				continue;
			}

			$compartment->activate( $user );

			if ( $compartment->errors ) {

				dol_htmloutput_errors( '', $compartment->errors );
			}
		}

		$list_action = '';

		header( "Location: " . $_SERVER['PHP_SELF'] . "?id=" . $fk_entrepot );
		exit();
	}
}
