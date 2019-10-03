<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
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
 *	\file       htdocs/product/stats/facture.php
 *	\ingroup    product service facture
 *	\brief      Page of invoice statistics for a product
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';      // to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once('/compartmentstracking/lib/compartmentstracking.lib.php');

$langs->load("companies");
$langs->load("products");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productstatsinvoice'));

$showmessage=GETPOST('showmessage');


$product_compartments = compartments_tracking()->get_product_preferred_compartments( $id );

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel');

$preferred_list = GETPOST( 'preferred_list', 'array' );

if ( $action === 'update' ) {

	if ( ! $cancel ) {
		compartments_tracking()->unset_product_preferred_compartments( $id );

		foreach ( $preferred_list as $preferred_id ) {

			compartments_tracking()->set_product_preferred_compartment( $id, $preferred_id );
		}
	}

	$action = '';

	header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	exit();
}

/**
 * View
 */
$form = new Form($db);
$formother= new FormOther($db);

if ($id > 0 || ! empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters=array('id'=>$id);
	$reshook=$hookmanager->executeHooks('doActions',$parameters,$product,$action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label,16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
	{
		$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('Referers');
		$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
	{
		$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('Referers');
		$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);

	if ($result > 0)
	{
		$head=product_prepare_head($product);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==Product::TYPE_SERVICE?'service':'product');
		dol_fiche_head($head, 'compartments2', $titre, -1, $picto);

		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$product,$action);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->societe_id && ! in_array('product', explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		if ( $object->id > 0 && ! $action ) {
			/* ************************************************************************** */
			/*                                                                            */
			/* Barre d'action                                                             */
			/*                                                                            */
			/* ************************************************************************** */

			print "<div class=\"tabsAction\">\n";

			$parameters = array();
			$reshook = $hookmanager->executeHooks( 'addMoreActionsButtons', $parameters, $object, $action );   // Note that $action and $object may have been modified by hook
			if ( empty( $reshook ) ) {
				if ( empty( $action ) ) {
					if ( $user->rights->stock->creer )
						print "<a class=\"butAction\" href=\"?action=edit&id=" . $object->id . "\">" . $langs->trans( "CompartmentsTrackingSetPreferred" ) . "</a>";
					else
						print "<a class=\"butActionRefused\" href=\"#\">" . $langs->trans( "CompartmentsTrackingSetPreferred" ) . "</a>";
				}
			}

			print "</div>";

			print '<div class="fichecenter">';

			print '<div class="underbanner clearboth"></div>';

			print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";

			print '<input type="hidden" class="flat" name="id" value="'.$product->id.'">';
			// print '<input type="hidden" class="flat" name="list_action" value="">';

			print '<table class="border tableforfield" width="100%">';

			$product_compartments = compartments_tracking()->get_product_compartments( $product->id );

			$nboflines = show_product_compartments( $product_compartments );

			print "</table>";

			print "</form>";

			print '</div>';
			print '<div style="clear:both"></div>';
		}

		/**
		 * Edition liste
		 */
		if ( $object->id > 0 && $action === 'edit' )
		{
			print '<form action="product.php?id=' . $object->id . '" method="POST">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="' . $object->id . '">';

			$compartments_list = compartments_tracking()->get_compartments_list( false );

			$preferred_compartments_id = compartments_tracking()->get_product_preferred_compartments_id( $object->id );

			print '<label>' . $langs->trans( 'CompartmentsTrackingPreferredCompartments' ) . '<br />';

			print $form->multiselectarray(
				'preferred_list',
				$compartments_list,
				$preferred_compartments_id,
				0,
				0,
				'',
				0,
				300
			);

			print '</label>';

			print '<br /><div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';
			print '</form>';
		}

		dol_fiche_end();
	}
} else {
	dol_print_error();
}

llxFooter();
$db->close();
