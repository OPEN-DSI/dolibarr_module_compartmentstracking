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
class Compartment extends CommonObject
{
	public $element='compartment';
	public $table_element='compartment';
	public $fk_element='fk_compartment';

	var $id; // Compartment ID.
	var $fk_entrepot; // Compartment Warehouse

	var $ref; // Compartment ref.

	var $status; // 0=Unavailable, 1=Available.

	var $column; // Column number.
	var $shelf; // Shelf number.
	var $drawer; // Drawer number.

	var $entrepot_label = ''; // Warehouse label.

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct( $db )
	{
		$this->db = $db;
		$this->status = 1;
	}

	/**
	 *	Create an compartment into data base
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create( $notrigger = 0 )
	{
		global $conf;

		$this->ref = $this->generate_ref();

		dol_syslog( __CLASS__ . "::create ref=" . $this->ref );

		$error = 0;

		// Check parameters.
		if ( ! $this->validate_compartment() ) {

			$this->errors[] = 'ErrorBadParameterForFunc';

			dol_syslog( __CLASS__ . "::create " . 'ErrorBadParameterForFunc', LOG_ERR );

			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "compartment (";
		$sql .= "fk_entrepot";
		$sql .= ", entity";
		$sql .= ", `column`";
		$sql .= ", shelf";
		$sql .= ", drawer";
		$sql .= ", status";
		$sql .= ", ref";
		$sql .= ") ";
		$sql .= " VALUES ( " . (int) $this->fk_entrepot;
		$sql .= ", " . (int) $conf->entity;
		$sql .= ", " . (int) $this->column;
		$sql .= ", " . (int) $this->shelf;
		$sql .= ", " . (int) $this->drawer;
		$sql .= ", " . (int) $this->status;
		$sql .= ", '" . $this->db->escape( $this->ref ) . "'";
		$sql .= ")";

		dol_syslog( __CLASS__ . "::create sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query( $sql );

		if ( $result ) {
			$this->id = $this->db->last_insert_id( MAIN_DB_PREFIX . "compartment" );
		} else {
			dol_print_error( $this->db );
			$error++;
		}

		if ( ! $error ) {
			$this->db->commit();

			return $this->id;
		}

		$this->errors[] = $this->db->error();

		dol_syslog( __CLASS__ . "::create " . $this->db->error(), LOG_ERR );

		$this->db->rollback();

		return -1;
	}


	function generate_ref() {

		if ( ! $this->validate_compartment() ) {
			return '';
		}

		$sep = $this->entrepot_compartments()->separator;

		// Concatenate Warehouse name, Column name, Shelf name and Drawer name.
		$ref = $this->entrepot_compartments()->ref;

		$ref .= $sep . $this->generate_ref_tier( $this->column, 'column' );

		$ref .= $sep . $this->generate_ref_tier( $this->shelf, 'shelf' );

		$ref .= $sep . $this->generate_ref_tier( $this->drawer, 'drawer' );

		return $ref;
	}


	function generate_ref_tier( $number, $tier_type ) {

		if ( ! $this->entrepot_compartments()->{ $tier_type . '_is_alpha' } ) {

			return $number;
		}

		$alphabet = range( 'A', 'Z' );

		return $alphabet[ --$number ]; // Returns Letter.
	}



	function entrepot_compartments( $fk_entrepot = 0 ) {

		if ( ! $fk_entrepot ) {

			$fk_entrepot = $this->fk_entrepot;
		}

		if ( ! empty( $this->entrepot_compartments ) && $this->entrepot_compartments->fk_entrepot == $fk_entrepot ) {

			return $this->entrepot_compartments;
		}

		$entrepot_compartments = new EntrepotCompartments( $this->db );

		$entrepot_compartments->fetch( null, $fk_entrepot );

		$this->entrepot_compartments = $entrepot_compartments;

		return $entrepot_compartments;
	}


	/**
	 *	Fetch a compartment
	 *
	 *	@param		int		$rowid		Id of compartment
	 *	@param		string	$ref		Ref of compartment
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch( $rowid, $ref='' )
	{
		$sql = "SELECT c.rowid, c.ref, c.datec, c.fk_entrepot, c.column, c.shelf, c.drawer, c.status, e.label AS entrepot_label";
		$sql.= " FROM " . MAIN_DB_PREFIX . "compartment c, " . MAIN_DB_PREFIX . "entrepot e";

		if ($ref) $sql.= " WHERE c.ref='" . $this->db->escape( $ref ) . "'";
		else $sql .= " WHERE c.rowid=" . $rowid;

		$sql .= " AND e.rowid = c.fk_entrepot";

		dol_syslog( __CLASS__ . "::fetch sql=" . $sql, LOG_DEBUG );

		$resql = $this->db->query( $sql );

		if ( $resql ) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->rowid			= $obj->rowid;
				$this->id				= $obj->rowid;
				$this->ref				= $obj->ref;
				$this->datec			= $this->db->jdate( $obj->datec );
				$this->fk_entrepot		= $obj->fk_entrepot;
				$this->column			= $obj->column;
				$this->shelf			= $obj->shelf;
				$this->drawer			= $obj->drawer;
				$this->status			= $obj->status;
				$this->entrepot_label	= $obj->entrepot_label;

				$this->db->free($resql);

				return 1;
			}
		} else {

			$this->errors[] = $this->db->error();

			dol_syslog( __CLASS__ . "::fetch " . $this->db->error(), LOG_ERR );

			return -1;
		}

		return 0;
	}


	/**
	 * Update
	 *
	 * @param  User	$user	User that set draft
	 * @return int			<0 if KO, >0 if OK
	 */
	function update( $user )
	{
		global $conf;

		$sql = "UPDATE " . MAIN_DB_PREFIX . "compartment SET ";

		if ( $this->column > 0
			&& $this->validate_compartment_tier_number( $this->column, 'column' ) ) {

			$sql .= "`column`='" . $this->column . "',";
		}

		if ( $this->shelf > 0
			&& $this->validate_compartment_tier_number( $this->shelf, 'shelf' ) ) {

			$sql .= "shelf='" . $this->shelf . "',";
		}

		if ( $this->drawer > 0
			&& $this->validate_compartment_tier_number( $this->drawer, 'drawer' ) ) {

			$sql .= "drawer='" . $this->drawer . "',";
		}

		if ( $this->status == 0 || $this->status == 1 ) {
			$sql .= "status='" . $this->status . "',";
		}

		if ( $this->fk_entrepot > 0 ) {

			$sql .= "fk_entrepot='" . $this->fk_entrepot . "',";
		}

		if ( substr( $sql, -1, 1 ) === ',' ) {

			$ref = $this->generate_ref();

			// Update Ref.
			$sql .= "ref='" . $ref . "',";

			$sql = substr( $sql, 0, -1 );

			$sql .= " WHERE rowid = " . $this->id;
			$sql .= " AND entity = " . $conf->entity;

			dol_syslog( __CLASS__ . "::update SQL=" . $sql );
			
			$resql=$this->db->query( $sql );

			if ( $resql ) {

				return 1;
			} else {
				$this->errors[] = $this->db->lasterror();
				
				dol_syslog( __CLASS__ . "::update " . $this->db->lasterror(), LOG_ERR );
				
				return -1;
			}
		}
		
		return 0;
	}


	function validate_compartment_tier_number( $number, $type ) {

		if ( empty( $this->entrepot_compartments()->{ $type } ) ) {

			return true;
		}

		return $this->entrepot_compartments()->{ $type } >= $number;
	}


	function validate_compartment() {

		if ( $this->fk_entrepot <= 0 || $this->column <= 0 || $this->shelf <= 0 || $this->drawer <= 0 ) {
			return false;
		}

		if ( ! $this->validate_compartment_tier_number( $this->column, 'column' ) ) {

			return false;
		}

		if ( ! $this->validate_compartment_tier_number( $this->shelf, 'shelf' ) ) {

			return false;
		}

		if ( ! $this->validate_compartment_tier_number( $this->drawer, 'drawer' ) ) {

			return false;
		}

		return true;
	}


	function neutralize( $user ) {

		if ( $this->status < 1 ) {

			// Already inactive.
			return true;
		}

		if ( ! $this->can_be_neutralized() ) {

			return false;
		}

		$this->status = 0;

		return $this->update( $user );
	}

	function can_be_neutralized() {

		// Check if has products.
		$products_total = $this->get_products_total();

		$products_quantity_total = $this->get_products_quantity_total();

		if ( $products_total && $products_quantity_total > 0 ) {

			return false;
		}

		$has_preferred_products = compartments_tracking()->get_compartment_preferred_products( $this->id );

		if ( $has_preferred_products ) {

			return false;
		}

		return true;
	}


	function activate( $user ) {


		if ( $this->status > 0 ) {

			// Already active.
			return true;
		}

		$this->status = 1;

		return $this->update( $user );
	}


	/**
	 *	Returns the label of a status
	 *
	 *	@param	  int		$status	 Id status
	 *	@return	 string	  	Label
	 */
	function get_status_label( $status = null )
	{
		global $langs;

		if ( is_null( $status ) ) {

			$status = $this->status;
		}

		if ( $status == 1 ) {
			return $langs->trans( 'Available' );
		}

		return $langs->trans( 'CompartmentsTrackingNeutralized' );
	}



	/**
	 *	Returns the label of a status + empty or number of products + (total)
	 *
	 *	@param	  int		$status	 Id status
	 *	@return	 string	  	Label
	 */
	function get_status_label_and_products( $status = null )
	{
		global $langs;

		$status_label = $this->get_status_label( $status );

		$products_total = $this->get_products_total();

		$products_quantity_total = $this->get_products_quantity_total();

		if ( ! $products_total || ! $products_quantity_total ) {
			// $status_label .= ' ' . $langs->trans( 'Empty' );

			return $status_label;
		}

		$status_label .= ', ' . $products_total . ' ' . $langs->trans( 'Products' ) . ' (' . $products_quantity_total . ')';

		return $status_label;
	}


	function get_products_total() {

		$products = compartments_tracking()->get_compartment_products( $this->id );

		return count( $products );
	}

	function get_products_quantity_total() {

		$qty_total = 0;

		$products = compartments_tracking()->get_compartment_products( $this->id );

		foreach ( $products as $product ) {
			$qty_total += $product->qty;
		}

		return $qty_total;
	}

	/**
	 *	Delete Compartment
	 *
	 *	@param  User	$user	Object user who delete
	 *	@return	int				<0 if KO, >0 if OK
	 */
	function delete( $user )
	{
		global $conf, $langs;

		$this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "compartment";
		$sql .= " WHERE rowid = " . $this->id;
		$sql .= " AND entity = " . $conf->entity;

		dol_syslog( "Compartment::delete sql=" . $sql );

		if ( $this->db->query( $sql ) ) {

			// Fin appel triggers.
			$this->db->commit();

			return 1;
		} else {
			$this->errors[] = $this->db->lasterror();

			$this->db->rollback();

			return -1;
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		$now = dol_now();

		// Initialise parametres.
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->fk_entrepot = 1;
		$this->datec = $now;
		$this->statut = 1;
		$this->column = 1;
		$this->drawer = 2;
		$this->shelf = 3;
	}
}
