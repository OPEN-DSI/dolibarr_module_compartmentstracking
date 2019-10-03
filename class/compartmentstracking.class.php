<?php
/* Copyright (C) 2018	Open-DSI	<info@open-dsi.fr>
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
 * 	\file	   htdocs/compartmentstracking/class/compartment.class.php
 * 	\ingroup	compartmentstracking
 * 	\brief	  Fichier de la classe des gestion des compartiments
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 * 	\class	  Compartment
 *	\brief	  Classe des gestion des compartments
 */
class CompartmentsTracking
{

	/**
	 * The single instance of Course_Wizard_For_Sensei.
	 *
	 * @var    object
	 * @access private
	 * @since  1.0.0
	 */
	private static $_instance = null;

	public $db;

	function __construct() {

		global $db;

		$this->db = $db;

		// Include our object classes.
		dol_include_once('/compartmentstracking/class/entrepotcompartments.class.php');

		dol_include_once('/compartmentstracking/class/compartment.class.php');

		dol_include_once('/compartmentstracking/class/compartmentproduct.class.php');
	}


	/**
	 * Main CompartmentsTracking Instance
	 *
	 * Ensures only one instance of CompartmentsTracking is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see compartments_tracking()
	 *
	 * @param string $file    File pathname.
	 * @param string $version Version number.
	 * @return Object CompartmentsTracking instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	function get_product_preferred_compartments( $product_id, $force = false ) {

		if ( ! $force && isset( $this->product_preferred_compartments[ $product_id ] ) ) {

			return $this->product_preferred_compartments[ $product_id ];
		}

		$product_compartments = array();

		$sql = "SELECT cp.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartmentproduct cp";
		$sql.= " WHERE cp.fk_product = ".$product_id;
		$sql.= " AND cp.preferred = 1";

		$result=$this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartments.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$product_compartment = new CompartmentProduct( $this->db );

				$product_compartment->fetch( $objp->rowid );

				$product_compartments[] = $product_compartment;

				$i++;
			}
		}

		$this->product_preferred_compartments[ $product_id ] = $product_compartments;

		return $product_compartments;
	}


	// Compartments: preferred OR has products.
	function get_product_compartments( $product_id, $force = false ) {

		if ( ! $force && isset( $this->product_compartments[ $product_id ] ) ) {

			return $this->product_compartments[ $product_id ];
		}

		$product_compartments = array();

		$sql = "SELECT cp.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartmentproduct cp";
		$sql.= " WHERE cp.fk_product = ".$product_id;
		$sql.= " AND (cp.preferred = 1 OR cp.qty > 0)";

		$result=$this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartments.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$product_compartment = new CompartmentProduct( $this->db );

				$product_compartment->fetch( $objp->rowid );

				$product_compartments[] = $product_compartment;

				$i++;
			}
		}

		$this->product_compartments[ $product_id ] = $product_compartments;

		return $product_compartments;
	}

	function get_compartment_preferred_products( $compartment_id, $force = false ) {

		if ( ! $force && isset( $this->compartment_preferred_products[ $compartment_id ] ) ) {

			return $this->compartment_preferred_products[ $compartment_id ];
		}

		$product_compartments = array();

		$sql = "SELECT cp.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartmentproduct cp";
		$sql.= " WHERE cp.fk_compartment = ".$compartment_id;
		$sql.= " AND cp.preferred = 1";

		$result=$this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartments.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$product_compartment = new CompartmentProduct( $this->db );

				$product_compartment->fetch( $objp->rowid );

				$product_compartments[] = $product_compartment;

				$i++;
			}
		}

		$this->compartment_preferred_products[ $compartment_id ] = $product_compartments;

		return $product_compartments;
	}



	function get_product_preferred_compartments_id( $product_id ) {

		$product_compartments = $this->get_product_preferred_compartments( $product_id );

		$product_compartments_list = array();

		foreach ( $product_compartments as $product_compartment ) {
			$product_compartments_list[] =  $product_compartment->fk_compartment;
		}

		return $product_compartments_list;
	}


	function get_compartments( $entrepot_id = 0, $order = '', $include_neutralized = true, $force = false ) {

		global $conf;

		if ( ! $force && isset( $this->compartments[ $entrepot_id ] ) ) {

			return $this->compartments[ $entrepot_id ];
		}

		$compartments = array();

		$sql = "SELECT c.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartment c";
		$sql.= " WHERE c.entity = ".$conf->entity;

		if ( $entrepot_id > 0 ) {
			$sql .= " AND c.fk_entrepot = " . $entrepot_id;
		}

		if ( ! $include_neutralized ) {

			$sql .= " AND c.status > 0";
		}

		$sql.= " ORDER BY " . ( trim( $order ) ? $order : 'c.ref' );

		$result=$this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartments.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$compartment = new Compartment( $this->db );

				$compartment->fetch( $objp->rowid );

				$compartments[] = $compartment;

				$i++;
			}
		}

		$this->compartments[ $entrepot_id ] = $compartments;

		return $compartments;
	}


	function get_compartments_list( $include_neutralized = true ) {

		$compartments = $this->get_compartments( 0, '', $include_neutralized );

		$compartments_list = array();

		foreach ( $compartments as $compartment ) {
			$compartments_list[ $compartment->id ] = $compartment->ref;
		}

		return $compartments_list;
	}


	function get_compartments_array_per_warehouse( $include_neutralized = true, $product_id = 0 ) {

		$compartments = $this->get_compartments( 0, '', $include_neutralized );

		$warehouses_array = array();

		$warehouse_id = 0;

		$compartment_product = new CompartmentProduct( $this->db );

		foreach ( $compartments as $compartment ) {
			$warehouse_id = $compartment->fk_entrepot;

			$compartment_array = array(
				'ref' => $compartment->ref,
				'id' => $compartment->id,
				'fk_entrepot' => $warehouse_id,
			);


			if ( $product_id ) {

				$quantity = $preferred = 0;

				$has_products = $compartment_product->fetch( null, $compartment->id, $product_id );

				if ( $has_products > 0 ) {

					$quantity = $compartment_product->qty;

					$preferred = (int) $compartment_product->preferred;
				}

				$compartment_array['qty'] = $quantity;

				$compartment_array['preferred'] = $preferred;
			}

			if ( ! is_array( $warehouses_array[ $warehouse_id ] ) ) {

				$warehouses_array[ $warehouse_id ] = array();
			}

			$warehouses_array[ $warehouse_id ][] = $compartment_array;
		}

		if ( $product_id ) {

			$warehouses_array_sorted = array();

			// Sort compartments by preferred, quantity, ref.
			// @link https://stackoverflow.com/questions/2699086/sort-multi-dimensional-array-by-value#2699159
			foreach ( $warehouses_array as $warehouse_id => $compartments_array ) {

				usort( $compartments_array, function( $c1, $c2 ) {
					if ( $c1['preferred'] - $c2['preferred'] ) {
						return $c2['preferred'] - $c1['preferred'];
					}

					if ( $c2['qty'] - $c1['qty'] ) {

						return $c2['qty'] - $c1['qty'];
					}

					return strcmp( $c1['ref'], $c2['ref'] );
				});

				$warehouses_array_sorted[ $warehouse_id ] = $compartments_array;
			}

			$warehouses_array = $warehouses_array_sorted;
		}

		return $warehouses_array;
	}


	function get_compartments_array_per_product( $include_neutralized = true, $warehouse_id ) {

		$compartments = $this->get_compartments( $warehouse_id, '', $include_neutralized );

		$products_array = new stdClass();

		$product_id = 0;

		// Add all compartments, under product 0 index, so we have all refs at hand.
		foreach ( $compartments as $compartment ) {

			$compartment_array = array(
				'ref' => $compartment->ref,
				'id' => $compartment->id,
				// 'qty' => 0,
				// 'preferred' => 0,
			);

			if ( ! is_array( $products_array->{ $product_id } ) ) {

				$products_array->{ $product_id } = array();
			}

			$products_array->{ $product_id }[] = $compartment_array;
		}

		$compartment_products = $this->get_warehouse_product_compartments( $warehouse_id, 'cp.preferred DESC, cp.qty DESC', $include_neutralized );

		// Add preferred and qty != 0 compartments for each product.
		foreach ( $compartment_products as $compartment_product ) {

			// Skip if qty == 0 and not preferred, we already have it above!
			if ( $compartment_product->qty == 0 && ! $compartment_product->preferred ) {

				continue;
			}

			$compartment_array = array(
				// 'ref' => '',
				'id' => $compartment_product->fk_compartment,
				'qty' => $compartment_product->qty,
				'preferred' => $compartment_product->preferred,
			);

			$product_id = $compartment_product->fk_product;

			if ( ! is_array( $products_array->{ $product_id } ) ) {

				$products_array->{ $product_id } = array();
			}

			$products_array->{ $product_id }[] = $compartment_array;
		}

		return $products_array;
	}

	function set_product_preferred_compartment( $product_id, $compartment_id ) {

		global $user;

		$product_compartment = $this->get_product_compartment( $product_id, $compartment_id );

		if ( ! $product_compartment ) {

			return false;
		}

		if ( ! $product_compartment->preferred ) {
			// Update.
			$product_compartment->preferred = 1;

			$product_compartment->update( $user );
		}

		return true;
	}


	function get_product_compartment( $product_id, $compartment_id ) {

		global $user;

		$compartment = new Compartment( $this->db );

		$compartment->fetch( $compartment_id );

		if ( ! $compartment->id ) {

			return false;
		}

		// Try to fetch and update CompartmentProduct.
		$product_compartment = new CompartmentProduct( $this->db );

		$exists = $product_compartment->fetch( null, $compartment_id, $product_id );

		if ( $exists < 0 ) {

			// Error.
			return false;
		}

		if ( $exists == 0 ) {
			// Create a new one.
			$product_compartment->fk_compartment = $compartment_id;

			$product_compartment->fk_product = $product_id;

			$product_compartment->preferred = 0;

			$product_compartment->qty = 0;

			$product_compartment->create( $user );
		}

		return $product_compartment;
	}


	function unset_product_preferred_compartments( $product_id ) {
		global $user;

		$compartments = $this->get_product_preferred_compartments( $product_id );

		foreach ( $compartments as $compartment ) {

			$compartment->preferred = 0;

			$compartment->update( $user );
		}

		return true;
	}


	function get_compartment_products( $compartment_id, $order = 'p.ref' ) {

		if ( ! empty( $this->compartment_products[ $compartment_id ] ) ) {

			return $this->compartment_products[ $compartment_id ];
		}

		$compartment_products = array();

		$sql = "SELECT cp.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartmentproduct cp";
		$sql.= " WHERE cp.fk_compartment = ".$compartment_id;
		$sql.= " AND cp.qty <> 0";

		// $sql.= " ORDER BY " . ( $order ? $order : 'c.ref' );

		$result = $this->db->query( $sql );

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartment products.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$compartment_product = new CompartmentProduct( $this->db );

				$compartment_product->fetch( $objp->rowid );

				$compartment_products[] = $compartment_product;

				$i++;
			}
		}

		$this->compartment_products[ $compartment_id ] = $compartment_products;

		return $compartment_products;
	}


	function get_warehouse_product_compartments( $warehouse_id, $order, $include_neutralized = false ) {

		$product_compartments = array();

		$sql = "SELECT cp.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartmentproduct cp";
		$sql.= " , " . MAIN_DB_PREFIX . "compartment c";
		$sql.= " WHERE c.fk_entrepot = ".$warehouse_id;
		$sql.= " AND c.rowid = cp.fk_compartment";

		if ( ! $include_neutralized ) {

			$sql .= " AND c.status > 0";
		}

		$sql.= " ORDER BY " . ( $order ? $order : 'cp.fk_product' );

		$result = $this->db->query( $sql );

		if ($result) {
			$num = $this->db->num_rows($result);

			// Compartment products.
			$i = 0;

			while ($i < $num) {

				$objp = $this->db->fetch_object($result);

				$product_compartment = new CompartmentProduct( $this->db );

				$product_compartment->fetch( $objp->rowid );

				$product_compartments[] = $product_compartment;

				$i++;
			}
		}

		return $product_compartments;
	}


	function compartment_is_in_warehouse( $compartment_id, $warehouse_id ) {

		if ( $compartment_id <= 0 ||
			$warehouse_id <= 0 ) {

			return false;
		}

		$compartment = new Compartment( $this->db );

		$compartment->fetch( $compartment_id );

		return $compartment->fk_entrepot == $warehouse_id;
	}
}
