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

/**
 *	\file       htdocs/compartmentstracking/lib/compartmentstracking.lib.php
 * 	\ingroup	compartmentstracking
 *	\brief      Functions for the module compartmentstracking
 */


dol_include_once('/compartmentstracking/class/compartmentstracking.class.php');

/**
 * Returns the main instance of CompartmentsTracking to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object CompartmentsTracking
 */
function compartments_tracking() {

	$instance = CompartmentsTracking::instance();

	return $instance;
}

compartments_tracking();

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function compartmentstracking_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/compartmentstracking/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("OpenDsiChangeLog");
	$head[$h][2] = 'changelog';
	$h++;

	$head[$h][0] = dol_buildpath("/compartmentstracking/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf,$langs,null,$head,$h,'compartmentstracking_admin');

	return $head;
}



/**
 * Show product compartments
 *
 * @param   Product     $product    Product object
 * @param   int         $socid      Thirdparty id
 * @return  integer                 NB of lines shown into array
 */
function show_product_compartments( $product_compartments )
{
	global $langs, $db, $conf;

	$view_entrepot = GETPOST('view_entrepot', 'int');

	$nblines = 0;

	print '<tr class="liste_titre">';
	print '<td align="left" class="tdtop" width="20%">'.$langs->trans("Warehouse").'</td>';
	print '<td align="left" class="tdtop" width="20%">'.$langs->trans("CompartmentsTrackingCompartment").'</td>';
	print '<td align="left" class="tdtop" width="20%">'.$langs->trans("Quantity").'</td>';
	print '<td align="right" width="20%">'.$langs->trans("Availability").'</td>';
	print '<td align="right" width="20%">'.$langs->trans("CompartmentsTrackingPreferred").'</td>';
	print '</tr>';

	print "<tr class=\"liste_titre\">";

	// liste des entrepots.
	print '<td class="liste_titre">';
	print '<select class="flat" name="view_entrepot">';
	print '<option value="">&nbsp;</option>';

	$select_entrepot_ids = array();

	foreach ( (array) $product_compartments as $product_compartment ) {

		if ( empty( $product_compartment->fk_compartment ) ) {
			// Error ?
			continue;
		}

		$entrepot_id = $product_compartment->compartment()->fk_entrepot;

		if ( in_array( $entrepot_id, $select_entrepot_ids ) ) {

			// Entrepot already in select.
			continue;
		}

		print '<option ';
		if ( $view_entrepot == $entrepot_id ) print ' selected ';
		print ' value="' . $entrepot_id . '">' . $product_compartment->compartment()->entrepot_label . '</option>';

		// Add entrepot to list so we add it once only.
		$select_entrepot_ids[] = $entrepot_id;
	}

	print '</select>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';

	print "<td align=\"right\" colspan=\"4\"></td></tr>\n";


	// Compartments.
	foreach ( (array) $product_compartments as $product_compartment ) {

		if ( empty( $product_compartment->fk_compartment ) ) {
			// Error ?
			continue;
		}

		if ( $view_entrepot > 0 && $product_compartment->compartment()->fk_entrepot != $view_entrepot ) {
			continue;
		}

		print '<tr><td>';
		print $product_compartment->compartment()->entrepot_label;
		print '</td>';
		print '<td>';
		print $product_compartment->compartment()->ref;
		print '</td>';
		print '<td>';
		print ( $product_compartment->qty < 0 ?
			'<span style="color: red;">' . $product_compartment->qty . '</span>' :
			$product_compartment->qty
		);
		print '</td><td align="right">';
		print $product_compartment->compartment()->get_status_label();
		print '</td><td align="right">';
		print ( $product_compartment->preferred ? $langs->trans("Yes") : '' );
		print '</td>';
		print '</tr>';

		$nblines++;
	}

	return $nblines;
}


function compartmentstracking_warehouse_compartments_select_js( $action, $product_id_or_warehouse_id_or_order, $type = 'product' ) {

	global $langs;


	if ( $action !== 'transfert' && $action !== 'correction' && $action !== 'dispatch' && $action !== 'massstockmove' ) {

		return;
	}

	if ( $type !== 'product' && $type !== 'warehouse' && $type !== 'ordersupplier' ) {

		return;
	}

	// Display Compartments select on Warehouse select change.
	?>
	<script>
		var ctIsTransfer = <?php echo json_encode( $action === 'transfert' || $action === 'massstockmove' ); ?>;
		var ctIsSelectProduct = <?php echo json_encode( $type === 'warehouse' ); ?>;
		var ctIsDispatch = <?php echo json_encode( $action === 'dispatch' ); ?>;
		var ctIsMassStockMove = <?php echo json_encode( $action === 'massstockmove' ); ?>;
		var ctDispatchSplitLineCounter = 0;

		function displayCompartmentsSelectRowInStockForm( select ) {

          var idCompartment = 'id_compartment';

			if ( ctIsDispatch ) {

              // Get current ordersupplier product line.
			  // Extract line index from select ID: entrepot_0_0.
              var productLineIndex = select.attr('id').replace( 'entrepot_', '' ).replace( /^[0-9]_*/, '' );

              // Add product ID to select ID.
              var idCompartmentDispatch = idCompartment + supplierOrderProductIds[ productLineIndex ];

              ctDispatchSplitLineCounter++;

              var idCompartmentDispatchCounter = idCompartmentDispatch + '_' + ctDispatchSplitLineCounter;
				
                select.after( '<br /><label for="' + idCompartmentDispatchCounter + '">' +
					<?php echo json_encode( $langs->trans("Compartment") ); ?> +
					'</label></td><td>\n' +
				  '<select id="' + idCompartmentDispatchCounter +
				  '" name="' + idCompartmentDispatch + '[]" id="" class="minwidth200imp">' +
				  '<option value=""></option>' +
				  '</select>' +
				  '</label>' );

              $( '#' + idCompartmentDispatchCounter ).select2();

            } else {

				var selectTr = select.parents( 'tr' );

				if ( ! selectTr.length ) {
				  return;
				}

				var compartmentsDestinationSelect = '';

				if ( ctIsTransfer ) {

                  var idCompartmentDestination = idCompartment + '_destination';

				  compartmentsDestinationSelect = ( ! ctIsMassStockMove ?
                    '<td><label for="' + idCompartmentDestination + '">' +
					<?php echo json_encode( $langs->trans("Compartment") ); ?> +
					'</label></td>' : '' ) +
                    '<td>\n' +
					'<select id="' + idCompartmentDestination + '" name="' + idCompartmentDestination + '" class="minwidth200imp">' +
					'<option value=""></option>' +
					'</select>' +
					'</label></td>' +
                    ( ! ctIsMassStockMove ? '' : '<td></td><td></td>' );
				}

				// Remove any existing <tr> before adding this one!
              	$('.warehouse-compartments-select-tr').remove();

				selectTr.after( '<tr class="warehouse-compartments-select-tr"><td><label for="' + idCompartment + '">' +
					<?php echo json_encode( $langs->trans("Compartment") ); ?> +
					'</label></td>' +
					'<td>\n' +
				  '<select id="' + idCompartment + '" name="' + idCompartment + '" class="minwidth200imp">' +
				  '<option value=""></option>' +
				  '</select>' +
				  '</label></td>' +
				  compartmentsDestinationSelect +
				  '</tr>' );

				$( '#' + idCompartment ).select2();

				if ( ctIsTransfer ) {

				  $( '#' + idCompartmentDestination ).select2();
				}
			}

			displayCompartmentsSelectOnSelectChange( select );
		}

		function displayCompartmentsSelectOnSelectChange( select ) {

			if ( ! select.length ) {
			  return;
			}

			if ( select.val() > 0 ) {

			  // Isset on page load, for example when having errors.
			  displayCompartmentsSelect( select, 'id_compartment' );
			}

			select.change(function() {

				displayCompartmentsSelect( $( this ), 'id_compartment' );
			});

          if ( ctIsDispatch ) {
            // On slit line Warehouse select change too!
			$( '.splitbutton' ).click(function() {
			  var splitWarehouseSelect = $( this ).parents( 'tr' ).next( 'tr' ).find( 'select[name^="entrepot_"]' );

			  // Fix select2 for compartments not working on copy cause of same ID conflict.
			  splitWarehouseSelect.nextAll( '.select2-container' ).remove();

			  // Change (increment) id_compartment ID on copy.
              splitWarehouseSelect.nextAll( 'select[id^="id_compartment"]' ).attr( 'id', function( i, att ){

				return att.replace( /_[0-9]$/, '_' + (ctDispatchSplitLineCounter++) );
			  }).select2();

              splitWarehouseSelect.change( function() {
                displayCompartmentsSelect( splitWarehouseSelect, 'id_compartment' );
			  });
			});
          }

            var stockMovement = $( '#mouvement' );

			if ( stockMovement.length ) {

			  stockMovement.change(function() {

				displayCompartmentsSelect( select, 'id_compartment' );
			  });
			}

			var warehouseDestinationSelect = ( ctIsMassStockMove ? $('#id_tw') : $('#id_entrepot_destination') );

			if ( ! warehouseDestinationSelect.length ) {
			  return;
			}

			if ( warehouseDestinationSelect.val() > 0 ) {

			  // Isset on page load, for example when having errors.
			  displayCompartmentsSelect( warehouseDestinationSelect, 'id_compartment_destination' );
			}

			warehouseDestinationSelect.change(function() {

				displayCompartmentsSelect( $( this ), 'id_compartment_destination' );
			});
		}

		function displayCompartmentsSelect( select, compartmentsSelectId ) {

		  var id = select.val();

		  var compartmentsSelect;

		  if ( ctIsDispatch ) {

              // Check if we have a select after the warehouse select beginning with "id_compartment".
              compartmentsSelect = select.nextAll( 'select' );
          } else {
            compartmentsSelect = $('#' + compartmentsSelectId);
		  }

		  if ( ! compartmentsSelect.length ) {
              return;
		  }

		  var options = [];

		  var warehouseId = productId = 0;

		  if ( ctIsSelectProduct ) {
			productId = id;
		  } else {
			warehouseId = id;
		  }

		  if ( warehouseId > 0 ) {

		    if ( ctIsDispatch ) {

              // Get current ordersupplier product line.
			  // Extract line index from select ID: entrepot_0_0.
              var productLineIndex = select.attr('id').replace( 'entrepot_', '' ).replace( /^[0-9]_*/, '' );

              // Fill select with corresponding compartments.
              options = compartmentsPerWarehouse[ supplierOrderProductIds[ productLineIndex ] ][ warehouseId ];
			} else {

              // Fill select with corresponding compartments.
              options = compartmentsPerWarehouse[ warehouseId ];
			}

		  }

		  if ( productId > 0 ) {

			// Fill select with corresponding compartments.
			options = compartmentsPerProduct[ productId ];
		  }

		  addCompartmentsSelectOptions( compartmentsSelect, options );
		}


		function addCompartmentsSelectOptions( compartmentsSelect, options ) {

		  compartmentsSelect.find('option').remove();

		  compartmentsSelect.find('option').end().append('<option value=""></option>');

		  console.log( options );

		  var removeProducts = false;

		  if ( ctIsTransfer && compartmentsSelect.attr( 'id' ) === 'id_compartment' ) {

			// Is source compartment on transfers, or
			// If remove products, only show compartments where quantity > 0!
			removeProducts = true;
		  }

		  var stockMovement = $( '#mouvement' );

		  if ( stockMovement.length && stockMovement.val() === '1' ) {

			removeProducts = true;
		  }

		  if ( ctIsSelectProduct ) {

			// All compartments at product index 0.
			// Fix: use slice to clone array!
			var optionsAll = compartmentsPerProduct[0].slice();

			var options2 = [];

			for(var i in options) {
				var shared = false;

				for (var j in optionsAll) {

					if (options[i].id == optionsAll[j].id) {
						shared = true;
						options[i].ref = optionsAll[j].ref;
						delete optionsAll[j];
						break;
					}
				}

				if(shared) {
					options2.push(options[i]);
				}
			}

			if ( removeProducts ) {

			  options = options2;
			} else {
              options = options2.concat(optionsAll);
			}

			//console.log(options,options2,optionsAll);
		  }

		  for (var key in options) {
			var option = options[ key ],
				optionValue = option.id,
				optionText = option.ref,
				optionQty = 0,
				optionPreferred = 0;

			if ( removeProducts && option.qty < 1 ) {

			  // Skip compartments with no quantity when removing products.
			  continue;
			}

			if ( option.hasOwnProperty( 'qty' ) ) {

				optionQty = option.qty;
			}

			if ( option.hasOwnProperty( 'preferred' ) ) {

				optionPreferred = option.preferred;
			}

			optionText += ( optionQty && optionQty != '0' ? ' (' + optionQty + ')' : '' );

			optionText += ( optionPreferred > 0 ? ' *' : '' );

			var optionHtml = '<option value="' + optionValue + '">' + optionText + '</option>';

			compartmentsSelect.find('option').end().append(optionHtml);
		  }
		}


		$(document).ready(function() {
          if ( ! ctIsDispatch ) {
            var select = ( ctIsSelectProduct ? $('#product_id') : ( ctIsMassStockMove ? $('#id_sw') : $('#id_entrepot') ) );


            displayCompartmentsSelectRowInStockForm( select );
          } else {
		    $( 'select[name^="entrepot_"]' ).each( function() {
              displayCompartmentsSelectRowInStockForm( $( this ) );
			});
		  }
		});
		<?php

		if ( $type === 'product' ) :

			// Load compartments per Warehouse in JSON object, array indexed per warehouse.
			$compartments_per_warehouse = compartments_tracking()->get_compartments_array_per_warehouse( false, $product_id_or_warehouse_id_or_order );

			?>
			var compartmentsPerWarehouse = <?php echo json_encode( $compartments_per_warehouse ); ?>;
		<?php
		// Type is warehouse.
		elseif ( $type === 'warehouse' ) :

			// Load compartments per Product in JSON object, array indexed per product (qty > 0 + preferred) + all (index 0).
			$compartments_per_product = compartments_tracking()->get_compartments_array_per_product( false, $product_id_or_warehouse_id_or_order );

		?>
			var compartmentsPerProduct = <?php echo json_encode( $compartments_per_product ); ?>;
		<?php
		// Type is ordersupplier.
		else :

			$order_lines = $product_id_or_warehouse_id_or_order->lines;

			$compartments_per_warehouse = array();

			$product_ids = array();

			foreach ( $order_lines as $order_line ) {

				$product_id = $order_line->fk_product;

				$product_ids[] = $order_line->fk_product;

				// Load compartments per Warehouse in JSON object, array indexed per warehouse.
				$compartments_per_warehouse[ $product_id ] = compartments_tracking()->get_compartments_array_per_warehouse( false, $product_id );
			}

			sort( $product_ids );

			?>
			var supplierOrderProductIds = <?php echo json_encode( $product_ids ); ?>;
			var compartmentsPerWarehouse = <?php echo json_encode( $compartments_per_warehouse ); ?>;
			<?php
		endif;
		?>
	</script>
	<?php
}


function compartmentstracking_product_select_ajax_js( $screen ) {

	if ( $screen !== 'massstockmove' ) {

		return;
	}

	// Load / inject compartmentstracking_warehouse_compartments_select_js() using AJAX on Product selection.
	?>
	<script>
      $(document).ready(function() {
        $('#productid').change(function(){

          var productId = this.value;

          if ( productId < 1 ) {

            return false;
		  }

		  // Add spinner while loading.
		  $( this ).after( '<img src="../../custom/compartmentstracking/img/ct-spinner.gif" class="ct-spinner" />' );

		  var urlAjax = '../../custom/compartmentstracking/ajax/compartmentsselect.php';

          $.ajax({
            url: urlAjax,
          	data: {
              "product_id": productId,
              "action": <?php echo json_encode( 'massstockmove' ); ?>
            },
			dataType: "html"
          })
		  .done(function(data) {
		    // console.log(data);
            $("body").append(data);
            $('.ct-spinner').remove();
          });
        });
      });
	</script>
	<?php
}

function compartmentstracking_product_stock_correct( $stock_movement ) {

	global $user;

	/*$stock_movement['qty'] = $object->qty;

	$stock_movement['type'] = $object->type; // Is movement type: add, 0 or remove.

	$stock_movement['product_id'] = $object->product_id;

	$stock_movement['entrepot_id'] = $object->entrepot_id;

	$stock_movement['compartment_id'] = GETPOST( 'id_compartment', 'int' );*/

	if ( empty( $stock_movement['qty'] )
		|| $stock_movement['type'] < 0
		|| $stock_movement['product_id'] < 1
		|| $stock_movement['entrepot_id'] < 1 ) {

		return -1;
	}

	if ( $stock_movement['compartment_id'] < 1 ) {

		// No compartment selected, do nothing.
		return 0;
	}

	// Compartment product.
	$compartment_product = compartments_tracking()->get_product_compartment(
		$stock_movement['product_id'],
		$stock_movement['compartment_id']
	);

	if ( ! $compartment_product ) {

		// Compartment does not exist or CompartmentProduct error.
		return -1;
	}

	// Correct stock: add or remove qty from compartment.
	if ( $stock_movement['type'] == 0 || $stock_movement['type'] == 3 ) {

		// Add.
		$compartment_product->qty += $stock_movement['qty'];

	} elseif ( $stock_movement['type'] == 1 ) {

		// Remove.
		$compartment_product->qty -= $stock_movement['qty'];
	}

	// Update.
	$updated = $compartment_product->update( $user );

	return $updated;
}
