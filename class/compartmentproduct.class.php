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
 * 	\file	   htdocs/compartmentstracking/class/compartmentproduct.class.php
 * 	\ingroup	compartmentstracking
 * 	\brief	  Fichier de la classe des gestion des compartiments de l'entrepot
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 * 	\class	  CompartmentProduct
 *	\brief	  Classe des gestion des compartmentproduct
 */
class CompartmentProduct extends CommonObject
{
	public $element='compartmentproduct';
	public $table_element='compartmentproduct';

	var $id; // CompartmentProduct ID.
	var $fk_compartment; // CompartmentProduct Compartment.
	var $fk_product; // CompartmentProduct Product.

	var $qty; // CompartmentProduct quantity.

	var $preferred; // CompartmentProduct preferred.

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct( $db )
	{
		$this->db = $db;
	}


	/**
	 *	Create an compartmentproduct into data base
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create( $notrigger = 0 )
	{
		global $conf;

		dol_syslog( __CLASS__ . "::create" );

		$error = 0;

		// Check parameters.
		if ( ! $this->validate() ) {

			$this->errors[] = 'ErrorBadParameterForFunc';

			dol_syslog( __CLASS__ . "::create " . 'ErrorBadParameterForFunc', LOG_ERR );

			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "compartmentproduct (";
		$sql .= "fk_compartment";
		$sql .= ", fk_product";
		$sql .= ", qty";
		$sql .= ", preferred";
		$sql .= ") ";
		$sql .= " VALUES ( " . (int) $this->fk_compartment;
		$sql .= ", " . (int) $this->fk_product;
		$sql .= ", " . (int) $this->qty;
		$sql .= ", " . (int) $this->preferred;
		$sql .= ")";

		dol_syslog( __CLASS__ . "::create sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query( $sql );

		if ( $result ) {
			$this->id = $this->db->last_insert_id( MAIN_DB_PREFIX . "compartmentproduct" );
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


	function product( $fk_product = 0 ) {

		if ( ! $fk_product ) {

			$fk_product = $this->fk_product;
		}

		if ( ! empty( $this->product ) && $this->product->id == $fk_product ) {

			return $this->product;
		}

		// Load Product class.
		require_once( DOL_DOCUMENT_ROOT . "/product/class/product.class.php" );

		$this->product = new Product( $this->db );

		$this->product->fetch( $fk_product );

		return $this->product;
	}


	function compartment( $fk_compartment = 0 ) {

		if ( ! $fk_compartment ) {

			$fk_compartment = $this->fk_compartment;
		}

		if ( ! empty( $this->compartment ) && $this->compartment->id == $fk_compartment ) {

			return $this->compartment;
		}

		$this->compartment = new Compartment( $this->db );

		$this->compartment->fetch( $fk_compartment );

		return $this->compartment;
	}


	/**
	 *	Fetch a compartmentproduct
	 *
	 *	@param		int		$rowid		Id of compartmentproduct
	 *	@param		string	$ref		Ref of compartmentproduct
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch( $rowid, $fk_compartment = 0, $fk_product = 0 )
	{
		$sql = "SELECT c.rowid, c.fk_compartment, c.fk_product, c.qty, c.preferred";
		$sql .= " FROM " . MAIN_DB_PREFIX . "compartmentproduct as c";

		if ($fk_compartment && $fk_product) {
			$sql.= " WHERE c.fk_compartment='" . $this->db->escape( $fk_compartment ) . "'";
			$sql.= " AND c.fk_product='" . $this->db->escape( $fk_product ) . "'";
		}
		else $sql .= " WHERE c.rowid=" . $rowid;

		dol_syslog( __CLASS__ . "::fetch sql=" . $sql, LOG_DEBUG );

		$resql = $this->db->query( $sql );

		if ( $resql ) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->rowid			= $obj->rowid;
				$this->id				= $obj->rowid;
				$this->fk_compartment	= $obj->fk_compartment;
				$this->fk_product		= $obj->fk_product;
				$this->qty				= $obj->qty;
				$this->preferred		= $obj->preferred;

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

		$sql = "UPDATE " . MAIN_DB_PREFIX . "compartmentproduct SET ";

		$sql .= "qty='" . $this->qty . "',";

		if ( $this->preferred == 0 || $this->preferred == 1 ) {
			$sql .= "preferred='" . $this->preferred . "',";
		}

		if ( $this->fk_product > 0 ) {

			$sql .= "fk_product='" . $this->fk_product . "',";
		}

		if ( $this->fk_compartment > 0 ) {

			$sql .= "fk_compartment='" . $this->fk_compartment . "',";
		}

		if ( substr( $sql, -1, 1 ) === ',' ) {

			$sql = substr( $sql, 0, -1 );

			$sql .= " WHERE rowid = " . $this->id;

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


	/**
	 * Update quantity
	 *
	 * @param int $qty Quantity can be positive (add products) or negative (remove products).
	 *
	 * @return int
	 */
	function update_quantity( $qty ) {

		global $user, $langs;

		$this->qty += $qty;

		if ( $this->qty < 0 ) {

			$this->errors[] = $langs->trans( 'CompartmentsTrackingErrorCompartmentProductQtyNegative' );

			dol_syslog( __CLASS__ . "::update_quantity " . $langs->trans( 'CompartmentsTrackingErrorCompartmentProductQtyNegative' ), LOG_ERR );

			return -1;
		}

		return $this->update( $user );
	}


	/**
	 * Set preferred
	 *
	 * @return int
	 */
	function set_preferred( $preferred ) {

		global $user;


		if ( $this->preferred != 0 && $this->preferred != 1 ) {

			return -1;
		}

		if ( $this->preferred == $preferred ) {

			return 0;
		}

		$this->preferred = $preferred;

		return $this->update( $user );
	}


	function validate() {

		if ( $this->fk_product <= 0 || $this->fk_compartment <= 0 || $this->qty < 0 ) {
			return false;
		}

		if ( $this->preferred != 0 && $this->preferred != 1 ) {
			return false;
		}

		if ( $this->product()->id != $this->fk_product ) {

			return false;
		}

		if ( $this->compartment()->id != $this->fk_compartment ) {

			return false;
		}

		return true;
	}


	/**
	 *	Delete CompartmentProduct
	 *
	 *	@param  User	$user	Object user who delete
	 *	@return	int				<0 if KO, >0 if OK
	 */
	function delete( $user )
	{
		global $conf, $langs;

		$this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "compartmentproduct";
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog( "CompartmentProduct::delete sql=" . $sql );

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
		// Initialise parametres.
		$this->id = 0;
		$this->specimen = 1;
		$this->fk_product = 1;
		$this->fk_compartment = 1;
		$this->qty = 10;
		$this->preferred = 0;
	}
}
