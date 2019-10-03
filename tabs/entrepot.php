<?php
/* Copyright (C) 2018	Open-DSI	<info@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file	   htdocs/compartmentstracking/tabs/entrepot.php
 *	\brief	   Compartments tab in Warehouse
 *	\ingroup	compartmentstracking
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");

dol_include_once('/compartmentstracking/lib/compartmentstracking.lib.php');

$langs->load("companies");
$langs->load("stocks");
$langs->load("compartmentstracking@compartmentstracking");

// $result = restrictedArea($user, 'compartmentstracking', $entrepotid, 'compartmentstracking');

$entrepot_id = GETPOST('id', 'int');

$fk_entrepot = $entrepot_id;

$compartment_id = GETPOST('compartment_id', 'int');

$fk_entrepot = $entrepot_id;

$entrepot_compartments = new EntrepotCompartments( $db );

$entrepot_compartments->fetch( null, $fk_entrepot );

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel');

$ref = GETPOST( 'ref', 'alpha' );
$separator = GETPOST( 'separator', 'alpha' );

$column = GETPOST( 'column', 'int' );
$shelf = GETPOST( 'shelf', 'int' );
$drawer = GETPOST( 'drawer', 'int' );

$column_is_alpha = GETPOST( 'column_is_alpha', 'int' );
$shelf_is_alpha = GETPOST( 'shelf_is_alpha', 'int' );
$drawer_is_alpha = GETPOST( 'drawer_is_alpha', 'int' );

if ( $action === 'update' ) {

	if ( ! $cancel ) {

		$entrepot_compartments->fk_entrepot = $fk_entrepot;

		$entrepot_compartments->ref = $ref;
		$entrepot_compartments->separator = $separator;

		$entrepot_compartments->column = $column;
		$entrepot_compartments->shelf = $shelf;
		$entrepot_compartments->drawer = $drawer;

		$entrepot_compartments->column_is_alpha = $column_is_alpha;
		$entrepot_compartments->shelf_is_alpha = $shelf_is_alpha;
		$entrepot_compartments->drawer_is_alpha = $drawer_is_alpha;

		if ( $entrepot_compartments->id ) {
			// Update EntrepotCompartments.
			$entrepot_compartments->update( $user );
		} else {
			// Create EntrepotCompartments.
			$entrepot_compartments->create( $user );
		}

		if ( $entrepot_compartments->errors ) {
			setEventMessage( $entrepot_compartments->errors, 'errors' );
		} else {
			header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $fk_entrepot);
			exit();
		}
	}

	$action = '';
}

dol_include_once('/compartmentstracking/includes/entrepotcompartments_list_action.inc.php');

/**
 * View.
 */
$form = new Form( $db );

llxHeader();

$object = new Entrepot( $db );

$result = $object->fetch( $entrepot_id );

/**
 * Show tab only if we ask a particular warehouse
 */
if ( $object->id > 0 )
{
	$head = stock_prepare_head($object);

	dol_fiche_head($head, 'compartments1', $langs->trans("Warehouse"), 0, 'stock');

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	$morehtmlref.=$langs->trans("LocationSummary").' : '.$object->lieu;
	$morehtmlref.='</div>';

	$shownav = 1;
	if ($user->societe_id && ! in_array('stock', explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

	dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid', 'libelle', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Description
	print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.dol_htmlentitiesbr($object->description).'</td></tr>';

	$calcproductsunique=$object->nb_different_products();
	$calcproducts=$object->nb_products();

	// Total nb of different products
	print '<tr><td>'.$langs->trans("NumberOfDifferentProducts").'</td><td>';
	print empty($calcproductsunique['nb'])?'0':$calcproductsunique['nb'];
	print "</td></tr>";

	// Nb of products
	print '<tr><td>'.$langs->trans("NumberOfProducts").'</td><td>';
	$valtoshow=price2num($calcproducts['nb'], 'MS');
	print empty($valtoshow)?'0':$valtoshow;
	print "</td></tr>";

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	// Value
	print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
	print price((empty($calcproducts['value'])?'0':price2num($calcproducts['value'],'MT')), 0, $langs, 0, -1, -1, $conf->currency);
	print "</td></tr>";

	// Last movement
	$sql = "SELECT MAX(m.datem) as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
	$sql .= " WHERE m.fk_entrepot = '".$object->id."'";
	$resqlbis = $db->query($sql);
	if ($resqlbis)
	{
		$obj = $db->fetch_object($resqlbis);
		$lastmovementdate=$db->jdate($obj->datem);
	}
	else
	{
		dol_print_error($db);
	}

	print '<tr><td>'.$langs->trans("LastMovement").'</td><td>';
	if ($lastmovementdate)
	{
		print dol_print_date($lastmovementdate,'dayhour');
	}
	else
	{
		print $langs->trans("None");
	}
	print "</td></tr>";

	print "</table>";

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';
}

print '<br /><hr /><br />';

if ( $object->id > 0 && ! $action && $entrepot_compartments->id ) {

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $langs->trans("CompartmentsTrackingCompartmentsTotal") . ' : ' . $entrepot_compartments->compartments_total();
	$morehtmlref .= '</div>';

	dol_banner_tab( $entrepot_compartments, 'id', '', 0, 'rowid', 'ref', $morehtmlref );

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Columns.
	print '<tr><td class="titlefield">'.$langs->trans("CompartmentsTrackingColumns").'</td><td>';
	print (int) $entrepot_compartments->column;
	print "</td></tr>";

	// Shelves.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingShelves").'</td><td>';
	print (int) $entrepot_compartments->shelf;
	print "</td></tr>";

	// Drawers.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingDrawers").'</td><td>';
	print (int) $entrepot_compartments->drawer;
	print "</td></tr>";

	// Separator.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingSeparator").'</td><td>';
	print empty( $entrepot_compartments->separator ) ? $langs->trans( 'None' ) : $entrepot_compartments->separator;
	print "</td></tr>";

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	// Column is alpha.
	print '<tr><td class="titlefield">'.$langs->trans("CompartmentsTrackingIsAlpha").'</td><td>';
	print $entrepot_compartments->column_is_alpha ? $langs->trans("Yes") : $langs->trans("No");
	print "</td></tr>";

	// Shelf is alpha.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingIsAlpha").'</td><td>';
	print $entrepot_compartments->shelf_is_alpha ? $langs->trans("Yes") : $langs->trans("No");
	print "</td></tr>";

	// Drawer is alpha.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingIsAlpha").'</td><td>';
	print $entrepot_compartments->drawer_is_alpha ? $langs->trans("Yes") : $langs->trans("No");
	print "</td></tr>";

	print "</table>";

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print "<div class=\"tabsAction\">\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks( 'addMoreActionsButtons', $parameters, $object, $action );    // Note that $action and $object may have been modified by hook
	if ( empty( $reshook ) ) {
		if ( empty( $action ) ) {
			if ( $user->rights->stock->creer )
				print "<a class=\"butAction\" href=\"?action=edit&id=" . $object->id . "\">" . $langs->trans( "Modify" ) . "</a>";
			else
				print "<a class=\"butActionRefused\" href=\"#\">" . $langs->trans( "Modify" ) . "</a>";
		}
	}

	print "</div>";

	if ( compartments_tracking()->compartment_is_in_warehouse( $compartment_id, $entrepot_id ) ) {

		// Check we have a valid compartment ID and load Warehouse Compartment Products list.
		dol_include_once('/compartmentstracking/includes/entrepotcompartmentproducts_list.inc.php');
	} else {

		// Compartments list.
		dol_include_once( '/compartmentstracking/includes/entrepotcompartments_list.inc.php' );
	}
}

if ( $object->id > 0 && ! $action && ! $entrepot_compartments->id ) {
	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print "<div class=\"tabsAction\">\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks( 'addMoreActionsButtons', $parameters, $object, $action );    // Note that $action and $object may have been modified by hook
	if ( empty( $reshook ) ) {
		if ( empty( $action ) ) {
			if ( $user->rights->stock->creer )
				print "<a class=\"butAction\" href=\"?action=edit&id=" . $object->id . "\">" . $langs->trans( "Create" ) . "</a>";
			else
				print "<a class=\"butActionRefused\" href=\"#\">" . $langs->trans( "Create" ) . "</a>";
		}
	}

	print "</div>";
}


/**
 * Edition fiche
 */
if ( $object->id > 0 && $action === 'edit' )
{
	$langs->trans( "CompartmentsTrackingEntrepotCompartmentsEdit" );

	print '<form action="entrepot.php?id=' . $object->id . '" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	$ref = empty( $entrepot_compartments->ref ) ?
		( empty( $object->lieu ) ? $object->label : $object->lieu ) :
		$entrepot_compartments->ref;

	// Ref.
	print '<tr><td>'.$langs->trans("Ref").'</td><td>';
	print '<input name="ref" size="20" maxlength="24" type="text" value="' . $ref . '" />';
	print "</td></tr>";

	// Separator.
	print '<tr><td>'.$langs->trans("CompartmentsTrackingSeparator").'</td><td>';
	print '<input name="separator" size="3" maxlength="3" type="text" value="' . $entrepot_compartments->separator . '" />';
	print "</td></tr>";


	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	// Columns.
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("CompartmentsTrackingColumns").'</td><td>';
	print '<input name="column" size="3" type="number" min="1" max="99" value="' . (int) $entrepot_compartments->column . '" />';

	print ' <label>';
	print '<input name="column_is_alpha" size="3" type="checkbox" value="1" autocomplete="off" ' .
		( $entrepot_compartments->column_is_alpha ? 'checked' : '' ) . ' /> ';
	print $langs->trans("CompartmentsTrackingIsAlpha") . '</label>';

	print "</td></tr>";

	// Shelves.
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("CompartmentsTrackingShelves").'</td><td>';
	print '<input name="shelf" size="3" type="number" min="1" max="99" value="' . (int) $entrepot_compartments->shelf . '" />';

	print ' <label>';
	print '<input name="shelf_is_alpha" size="3" type="checkbox" value="1" autocomplete="off" ' .
		( $entrepot_compartments->shelf_is_alpha ? 'checked' : '' ) . ' /> ';
	print $langs->trans("CompartmentsTrackingIsAlpha") . '</label>';

	print "</td></tr>";

	// Drawers.
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("CompartmentsTrackingDrawers").'</td><td>';
	print '<input name="drawer" size="3" type="number" min="1" max="99" value="' . (int) $entrepot_compartments->drawer . '" />';

	print ' <label>';
	print '<input name="drawer_is_alpha" size="3" type="checkbox" value="1" autocomplete="off" ' .
		( $entrepot_compartments->drawer_is_alpha ? 'checked' : '' ) . ' /> ';
	print $langs->trans("CompartmentsTrackingIsAlpha") . '</label>';

	print "</td></tr>";

	print "</table>";

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print '</table>';

	print '<br /><div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}


dol_fiche_end();

llxFooter();

$db->close();
