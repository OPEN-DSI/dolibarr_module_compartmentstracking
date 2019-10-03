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

global $db, $user, $fk_entrepot, $entrepot_compartments;

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');

if (! $sortorder) $sortorder="desc";

if (! $sortfield) $sortfield="c.status";

$page = GETPOST('page', 'int');

if ($page == -1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;

$pageprev = $page - 1;
$pagenext = $page + 1;

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');

$search_column=GETPOST('search_column', 'int');
$search_shelf=GETPOST('search_shelf', 'int');
$search_drawer=GETPOST('search_drawer', 'int');

$viewstatus = GETPOST('viewstatus', 'int');

/*$sql = "SELECT c.rowid, c.ref, c.datec, c.fk_entrepot, c.column, c.shelf, c.drawer, c.status";
$sql.= " FROM " . MAIN_DB_PREFIX . "compartment as c";
$sql.= " WHERE c.entity = ".$conf->entity;
$sql.= " AND c.fk_entrepot =".$db->escape($fk_entrepot);*/

if ($search_ref) {
	$sql.= " AND c.ref like '%".$db->escape($search_ref)."%'";
}

if ($viewstatus || $viewstatus === '0') {
	$sql.= " AND c.status ='".$db->escape($viewstatus)."'";
}

/*$sql.= " ORDER BY ".$sortfield." ".$sortorder;

$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);*/

$compartments = compartments_tracking()->get_compartments( $fk_entrepot, $sortfield." ".$sortorder . ", c.ref" );

if ( $compartments ) {

	$urlparam="&amp;id=".$fk_entrepot;

	if ($search_ref) {
		$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	}

	if ($search_column) {
		$urlparam .= "&amp;search_column=".$db->escape($search_column);
	}

	if ($search_shelf) {
		$urlparam .= "&amp;search_shelf=".$db->escape($search_shelf);
	}

	if ($search_drawer) {
		$urlparam .= "&amp;search_drawer=".$db->escape($search_drawer);
	}

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	print '<input type="hidden" class="flat" name="id" value="'.$fk_entrepot.'">';
	print '<input type="hidden" class="flat" name="list_action" id="list_action" value="">';

	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";

	print_liste_field_titre(
		$langs->trans("Ref"), $_SERVER["PHP_SELF"], "c.ref",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("CompartmentsTrackingColumn"), $_SERVER["PHP_SELF"], "c.column",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("CompartmentsTrackingShelf"), $_SERVER["PHP_SELF"], "c.shelf",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("CompartmentsTrackingDrawer"), $_SERVER["PHP_SELF"], "c.drawer",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		$langs->trans("Status"), $_SERVER["PHP_SELF"], "c.status",
		"", $urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
		'<input type="checkbox" onclick="checkAll(this.form, this.checked, \'activate\')" />', $_SERVER["PHP_SELF"], "",
		"", $urlparam, ''
	);

	print_liste_field_titre(
		'<input type="checkbox" onclick="checkAll(this.form, this.checked, \'neutralize\')" />', $_SERVER["PHP_SELF"], "",
		"", $urlparam, 'align="right"'
	);
	print "</tr>\n";

	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';

	print '<td class="liste_titre">';
	print '<input type="number" class="flat width50" name="search_column" min="0" max="' . $entrepot_compartments->column . '"
		value="'.( empty( $search_column ) ? '' : $search_column ) .'" size="2"></td>';

	print '<td class="liste_titre">';
	print '<input type="number" class="flat width50" name="search_shelf" min="0" max="' . $entrepot_compartments->shelf . '"
		value="'. ( empty( $search_shelf ) ? '' : $search_shelf ) .'" size="2"></td>';

	print '<td class="liste_titre">';
	print '<input type="number" class="flat width50" name="search_drawer" min="0" max="' . $entrepot_compartments->drawer . '"
		value="'. ( empty( $search_drawer ) ? '' : $search_drawer ) .'" size="2"></td>';

	// liste des états
	print '<td class="liste_titre">';
	print '<select class="flat" name="viewstatus">';
	print '<option value="">&nbsp;</option>';

	print '<option ';
	if ($viewstatus=='0') print ' selected ';
	print ' value="0">'.$compartments[0]->get_status_label( 0 ).'</option>';

	print '<option ';
	if ($viewstatus=='1') print ' selected ';
	print ' value="1">'.$compartments[0]->get_status_label( 1 ).'</option>';

	print '</select>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';

	print '<td class="liste_titre">';
	print '<input type="submit" class="button" value="'.$langs->trans("Activate").'"
		onclick="this.form.method = \'post\'; this.form.elements.list_action.value = \'activate\';">' . '</td>';

	print '<td class="liste_titre" align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("CompartmentsTrackingNeutralize").'"
		onclick="this.form.method = \'post\'; this.form.elements.list_action.value = \'neutralize\';">' . '</td>';
	print "</tr>\n";

	$total = 0;
	$i = 0;
	$total_qty = 0;

	// while ($i < min($num, $limit)) {
	foreach ( $compartments as $compartment ) {

		if ($viewstatus || $viewstatus === '0') {
			// Filter status.
			if ( $viewstatus != $compartment->status ) {

				continue;
			}
		}

		if ( $search_ref && strpos( $compartment->ref, $search_ref ) === false ) {
			continue;
		}

		if ( $search_column && $compartment->column != $search_column ) {
			continue;
		}

		if ( $search_shelf && $compartment->shelf != $search_shelf ) {
			continue;
		}

		if ( $search_drawer && $compartment->drawer != $search_drawer ) {
			continue;
		}

		$products_qty = $compartment->get_products_quantity_total();

		$link_products_begin = $lik_products_end = '';

		if ( $products_qty ) {
			// Link ref to detailed compartment view: products list.
			$link_products_begin = "<a href='entrepot.php?id=" . $fk_entrepot . "&compartment_id=" . $compartment->id .
				"' title='" . $langs->trans("Products") . "'>";

			$link_products_end = '</a>';
		}

		print "<td>" . $link_products_begin .
			$compartment->ref . $link_products_end . "</td>";

		print "<td>" . $compartment->column . "</td>";
		print "<td>" . $compartment->shelf . "</td>";
		print "<td>" . $compartment->drawer . "</td>";
		print '<td>'.$compartment->get_status_label_and_products().'</td>';
		print '<td>';

		if ( $compartment->status == 0 ){

			print '<input type="checkbox" name="activate[]" class="activate-checkbox" value="' . $compartment->id . '" /></td>';
		}

		print '</td><td align="right">';

		if ( $compartment->status > 0 && $compartment->can_be_neutralized() ) {

			print '<input type="checkbox" name="neutralize[]" class="neutralize-checkbox" value="' . $compartment->id . '" /></td>';
		}

		print "</tr>\n";

		$total_qty += $products_qty;

		$i++;
	}

	print '<tr class="liste_total"><td class="liste_total">'.$langs->trans("Total").'</td>';
	print '<td colspan="3"></td>';
	print '<td nowrap="nowrap" class="liste_total">('.$total_qty.')</td>';
	print '<td colspan="2" align="right" nowrap="nowrap" class="liste_total">'.$i.'</td>';
	print '</tr>';

	print '</table>';

	print "</form>\n";

	?>
	<script>

	  /**
	   * Check all checkboxes given the form,
	   * the value/state and the checkboxes name (beginning with).
	   *
	   * @param  {[type]} form      Form element.
	   * @param  {string} value     Checked value.
	   * @param  {string} name_like Checkbox name begins with.
	   */
	  var checkAll = function(form, value, name_like) {
	    for (var i = 0, max = form.elements.length; i < max; i++) {
	      var chk = form.elements[i];

	      if (chk.type == 'checkbox' &&
	        chk.name.substr(0, name_like.length) == name_like) {

	        chk.checked = value;
	      }
	    }
	  }
	</script>
	<?php
}

