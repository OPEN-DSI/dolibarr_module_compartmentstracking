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
 *   	\file       htdocs/compartmentstracking/includes/entrepotcompartments_list.inc.php
 *		\ingroup    includes
 *		\brief      List for EntrepotCompartments
 */

global $db, $user, $fk_entrepot, $entrepot_compartments, $compartment_id;

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');

if (! $sortorder) $sortorder="desc";

if (! $sortfield) $sortfield="p.ref";

$page = GETPOST('page', 'int');

if ($page == -1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;

$pageprev = $page - 1;
$pagenext = $page + 1;

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');

$compartment = new Compartment( $db );

$compartment->fetch( $compartment_id );

print '<div class="inline-block floatleft valignmiddle refid">' . $compartment->ref . '</div>';

$compartment_products = compartments_tracking()->get_compartment_products( $compartment_id, $sortfield." ".$sortorder . ", p.ref" );

if ( $compartment_products ) {

	$urlparam="&amp;id=".$fk_entrepot;

	if ($search_ref) {
		$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	}

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	print '<input type="hidden" class="flat" name="id" value="'.$fk_entrepot.'">';
	print '<input type="hidden" class="flat" name="compartment_id" value="'.$compartment_id.'">';

	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";

	print_liste_field_titre(
		$langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("Quantity"), $_SERVER["PHP_SELF"], "cp.qty",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print "</tr>\n";

	$total = 0;
	$i = 0;
	$total_qty = 0;

	// while ($i < min($num, $limit)) {
	foreach ( $compartment_products as $compartment_product ) {

		if ( ! $compartment_product->qty ) {
			continue;
		}

		$product = $compartment_product->product();

		if ( $search_ref && strpos( $product->ref, $search_ref ) === false ) {
			continue;
		}

		// Link ref to product card.
		print "<td><a href='../../../product/card.php?id=" . $product->id . "'>" .
			$product->ref . "</a></td>";

		print "<td>" . $product->label . "</td>";
		print "<td>" . $compartment_product->qty . "</td>";

		print "</tr>\n";

		$total_qty += $compartment_product->qty;

		$i++;
	}

	print '<tr class="liste_total"><td class="liste_total">'.$langs->trans("Total").'</td>';
	print '<td nowrap="nowrap" class="liste_total">'.$i.'</td>';
	print '<td nowrap="nowrap" class="liste_total">'.$total_qty.'</td>';
	print '</tr>';

	print '</table>';

	print "</form>\n";
}

