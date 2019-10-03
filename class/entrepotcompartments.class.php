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
 * 	\file	   htdocs/compartmentstracking/class/entrepotcompartments.class.php
 * 	\ingroup	compartmentstracking
 * 	\brief	  Fichier de la classe des gestion des compartiments de l'entrepot
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");
dol_include_once('/compartmentstracking/class/compartment.class.php');

/**
 * 	\class	  EntrepotCompartments
 *	\brief	  Classe des gestion des entrepotcompartmentss
 */
class EntrepotCompartments extends CommonObject
{
	public $element='entrepotcompartments';
	public $table_element='entrepotcompartments';
	public $fk_element='fk_entrepotcompartments';

	var $id; // EntrepotCompartments ID.
	var $fk_entrepot; // EntrepotCompartments Warehouse

	var $ref; // EntrepotCompartments ref.

	var $separator; // EntrepotCompartments separator for compartment ref.

	var $column; // Column number.
	var $shelf; // Shelf number.
	var $drawer; // Drawer number.

	var $column_is_alpha; // Column is alpha.
	var $shelf_is_alpha; // Shelf is alpha.
	var $drawer_is_alpha; // Drawer is alpha.

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
	 *	Create an entrepotcompartments into data base
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create( $notrigger = 0 )
	{
		global $conf;

		dol_syslog( __CLASS__ . "::create ref=" . $this->ref );

		$error = 0;

		// Check parameters.
		if ( ! $this->validate( true ) ) {

			$this->errors[] = 'ErrorBadParameterForFunc';

			dol_syslog( __CLASS__ . "::create " . 'ErrorBadParameterForFunc', LOG_ERR );

			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "entrepotcompartments (";
		$sql .= "fk_entrepot";
		$sql .= ", entity";
		$sql .= ", `column`";
		$sql .= ", shelf";
		$sql .= ", drawer";
		$sql .= ", column_is_alpha";
		$sql .= ", shelf_is_alpha";
		$sql .= ", drawer_is_alpha";
		$sql .= ", ref";
		$sql .= ", `separator`";
		$sql .= ") ";
		$sql .= " VALUES ( " . (int) $this->fk_entrepot;
		$sql .= ", " . (int) $conf->entity;
		$sql .= ", " . (int) $this->column;
		$sql .= ", " . (int) $this->shelf;
		$sql .= ", " . (int) $this->drawer;
		$sql .= ", " . (int) $this->column_is_alpha;
		$sql .= ", " . (int) $this->shelf_is_alpha;
		$sql .= ", " . (int) $this->drawer_is_alpha;
		$sql .= ", '" . $this->db->escape( $this->ref ) . "'";
		$sql .= ", '" . $this->db->escape( $this->separator ) . "'";
		$sql .= ")";

		dol_syslog( __CLASS__ . "::create sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query( $sql );

		if ( $result ) {
			$this->id = $this->db->last_insert_id( MAIN_DB_PREFIX . "entrepotcompartments" );
		} else {
			dol_print_error( $this->db );
			$error++;
		}

		if ( ! $error ) {
			$this->db->commit();

			$this->generate_compartments();

			return $this->id;
		}

		$this->errors[] = $this->db->error();

		dol_syslog( __CLASS__ . "::create " . $this->db->error(), LOG_ERR );

		$this->db->rollback();

		return -1;
	}


	function compartments() {

		if ( ! empty( $this->compartments ) ) {

			return $this->compartments;
		}

		$this->compartments = array();

		if ( $this->fk_entrepot <= 0 ) {

			return $this->compartments;
		}

		// Get compartments where fk_entrepot.
		$sql = "SELECT c.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "compartment as c";
		$sql .= " WHERE c.fk_entrepot=" . $this->fk_entrepot;

		dol_syslog( __CLASS__ . "::fetch sql=" . $sql, LOG_DEBUG );

		$resql = $this->db->query( $sql );

		if ( $resql ) {
			$num_rows = $this->db->num_rows( $resql );

			// @todo test loop!
			while ( $obj = $this->db->fetch_object($resql) ) {

				$compartment = new Compartment( $this->db );

				$compartment->fetch( $obj->rowid );

				$this->compartments[] = $compartment;
			}
		} else {

			$this->errors[] = $this->db->error();

			dol_syslog( __CLASS__ . "::compartments " . $this->db->error(), LOG_ERR );
		}

		return $this->compartments;
	}


	function generate_compartments() {

		global $user;

		$existing_compartments = $this->compartments();

		$compartment_ids = array();

		for ( $i = 1, $max = $this->column; $i <= $max; $i++  ) {

			for ( $j = 1, $max2 = $this->shelf; $j <= $max2; $j++ ) {

				for ( $k = 1, $max3 = $this->drawer; $k <= $max3; $k++ ) {

					$compartment_found = false;

					foreach ( $existing_compartments as $existing_compartment ) {

						if ( $i == $existing_compartment->column &&
							$j == $existing_compartment->shelf &&
							$k == $existing_compartment->drawer ) {

							// Re-generate Ref.
							$existing_compartment->update( $user );

							$compartment_ids[] = $existing_compartment->id;

							$compartment_found = true;

							break;
						}
					}

					if ( $compartment_found ) {

						continue;
					}

					// Create new compartment.
					$compartment = new Compartment( $this->db );

					$compartment->fk_entrepot = $this->fk_entrepot;

					$compartment->column = $i;
					$compartment->shelf = $j;
					$compartment->drawer = $k;

					$compartment->status = 1;

					$new_id = $compartment->create();

					if ( $new_id < 0 ) {

						$this->errors += $compartment->errors;
					} else {

						$compartment_ids[] = $new_id;
					}
				}
			}
		}

		// Delete obsolete compartments.
		foreach ( $existing_compartments as $existing_compartment ) {

			if ( ! in_array( $existing_compartment->id, $compartment_ids ) ) {

				$existing_compartment->delete( $user );
			}
		}

		if ( count( $compartment_ids ) != $this->compartments_total() ) {

			// Compartments does not match calculated total...
			return false;
		}

		return true;
	}


	/**
	 *	Fetch a entrepotcompartments
	 *
	 *	@param		int		$rowid		Id of entrepotcompartments
	 *	@param		string	$ref		Ref of entrepotcompartments
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch( $rowid, $fk_entrepot = 0, $ref = '' )
	{
		$sql = "SELECT c.rowid, c.ref, c.separator, c.datec, c.fk_entrepot, c.column";
		$sql .= ", c.shelf, c.drawer, c.column_is_alpha, c.shelf_is_alpha, c.drawer_is_alpha";
		$sql .= " FROM " . MAIN_DB_PREFIX . "entrepotcompartments as c";

		if ($ref) $sql.= " WHERE c.ref='" . $this->db->escape( $ref ) . "'";
		if ($fk_entrepot) $sql.= " WHERE c.fk_entrepot=" . $fk_entrepot;
		else $sql .= " WHERE c.rowid=" . $rowid;

		dol_syslog( __CLASS__ . "::fetch sql=" . $sql, LOG_DEBUG );

		$resql = $this->db->query( $sql );

		if ( $resql ) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->rowid			= $obj->rowid;
				$this->id				= $obj->rowid;
				$this->ref				= $obj->ref;
				$this->separator		= $obj->separator;
				$this->datec			= $this->db->jdate( $obj->datec );
				$this->fk_entrepot		= $obj->fk_entrepot;
				$this->column			= $obj->column;
				$this->shelf			= $obj->shelf;
				$this->drawer			= $obj->drawer;
				$this->column_is_alpha	= $obj->column_is_alpha;
				$this->shelf_is_alpha	= $obj->shelf_is_alpha;
				$this->drawer_is_alpha	= $obj->drawer_is_alpha;

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
		global $conf, $langs;

		$sql = "UPDATE " . MAIN_DB_PREFIX . "entrepotcompartments SET ";

		while( ! $this->check_tier_safe_delete( $this->column, 'column' ) ) {

			// Increment tier while has products (not safe to delete!).
			$this->column++;

			// Warn user.
			$this->errors[] = $langs->trans(
				'CompartmentsTrackingTierDeleteError',
				$langs->trans( 'CompartmentsTrackingColumn' ),
				$langs->trans( 'CompartmentsTrackingColumn' ) . ' ' . $this->column
			);
		}

		if ( $this->column > 0
			&& $this->validate_tier_number( $this->column, 'column' ) ) {

			$sql .= "`column`='" . $this->column . "',";
		}

		while( ! $this->check_tier_safe_delete( $this->shelf, 'shelf' ) ) {

			// Increment tier while has products (not safe to delete!).
			$this->shelf++;

			// Warn user.
			$this->errors[] = $langs->trans(
				'CompartmentsTrackingTierDeleteError',
				$langs->trans( 'CompartmentsTrackingShelf' ),
				$langs->trans( 'CompartmentsTrackingShelf' ) . ' ' . $this->shelf
			);
		}

		if ( $this->shelf > 0
			&& $this->validate_tier_number( $this->shelf, 'shelf' ) ) {

			$sql .= "shelf='" . $this->shelf . "',";
		}

		while( ! $this->check_tier_safe_delete( $this->drawer, 'drawer' ) ) {

			// Increment tier while has products (not safe to delete!).
			$this->drawer++;

			// Warn user.
			$this->errors[] = $langs->trans(
				'CompartmentsTrackingTierDeleteError',
				$langs->trans( 'CompartmentsTrackingDrawer' ),
				$langs->trans( 'CompartmentsTrackingDrawer' ) . ' ' . $this->drawer
			);
		}

		if ( $this->drawer > 0
			&& $this->validate_tier_number( $this->drawer, 'drawer' ) ) {

			$sql .= "drawer='" . $this->drawer . "',";
		}

		if ( $this->column_is_alpha >= 0 ) {

			$sql .= "`column_is_alpha`='" . $this->column_is_alpha . "',";
		}

		if ( $this->shelf_is_alpha >= 0 ) {

			$sql .= "`shelf_is_alpha`='" . $this->shelf_is_alpha . "',";
		}

		if ( $this->drawer_is_alpha >= 0 ) {

			$sql .= "`drawer_is_alpha`='" . $this->drawer_is_alpha . "',";
		}

		if ( $this->ref ) {
			$sql .= "ref='" . $this->ref . "',";
		}

		if ( $this->separator ) {
			$sql .= "`separator`='" . $this->separator . "',";
		}

		if ( $this->fk_entrepot > 0 ) {

			$sql .= "fk_entrepot='" . $this->fk_entrepot . "',";
		}

		if ( substr( $sql, -1, 1 ) === ',' ) {

			$sql = substr( $sql, 0, -1 );

			$sql .= " WHERE rowid = " . $this->id;
			$sql .= " AND entity = " . $conf->entity;

			dol_syslog( __CLASS__ . "::update SQL=" . $sql );
			
			$resql=$this->db->query( $sql );

			if ( $resql ) {

				// Re-generate compartments.
				$this->generate_compartments();

				return 1;
			} else {
				$this->errors[] = $this->db->lasterror();
				
				dol_syslog( __CLASS__ . "::update " . $this->db->lasterror(), LOG_ERR );
				
				return -1;
			}
		}
		
		return 0;
	}


	function validate_tier_number( $number, $type ) {

		foreach ( $this->compartments() as $compartment ) {

			if ( $compartment->{ $type } > $number ) {

				return false;
			}
		}

		return true;
	}


	function check_tier_safe_delete( $number, $type ) {

		if ( ! $this->id ) {

			// Compartments are new!
			return true;
		}

		foreach ( $this->compartments() as $compartment ) {

			if ( $compartment->{ $type } > $number
				&& $compartment->get_products_quantity_total() > 0 ) {

				// Compartments having a {tier_type} superior to this one AND having products.
				// Do not DELETE!
				return false;
			}
		}

		return true;
	}


	function validate( $create = false ) {

		if ( $this->fk_entrepot <= 0 || $this->column <= 0 || $this->shelf <= 0 || $this->drawer <= 0 ) {
			return false;
		}

		if ( $create ) {
			// Check if EntrepotCompartments already existing for fk_entrepot!
			$entrepot_compartments_check = new EntrepotCompartments( $this->db );

			$result = $entrepot_compartments_check->fetch( null, $this->fk_entrepot );

			if ( $result > 0 ) {

				// We already have an EntrepotCompartments for this fk_entrepot!
				return false;
			}
		} else {

			if ( ! $this->validate_tier_number( $this->column, 'column' ) ) {

				return false;
			}

			if ( ! $this->validate_tier_number( $this->shelf, 'shelf' ) ) {

				return false;
			}

			if ( ! $this->validate_tier_number( $this->drawer, 'drawer' ) ) {

				return false;
			}
		}

		return true;
	}


	function compartments_total() {

		if ( $this->fk_entrepot <= 0 ) {

			return 0;
		}

		$total = $this->column * $this->shelf * $this->drawer;

		foreach ( $this->compartments() as $compartment ) {

			if ( ! $compartment->status ) {

				$total--;
			}
		}

		return $total;
	}

	/**
	 *  Return label of the status of object
	 *
	 *  @param  int	   $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
	 *  @return string Label
	 */
	function getLibStatut( $mode=0 )
	{
		return '';
	}

	/**
	 *	Delete EntrepotCompartments
	 *
	 *	@param  User	$user	Object user who delete
	 *	@return	int				<0 if KO, >0 if OK
	 */
	function delete( $user )
	{
		global $conf, $langs;

		$this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "entrepotcompartments";
		$sql .= " WHERE rowid = " . $this->id;
		$sql .= " AND entity = " . $conf->entity;

		dol_syslog( "EntrepotCompartments::delete sql=" . $sql );

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
		$this->separator = 1;
		$this->specimen = 1;
		$this->fk_entrepot = 1;
		$this->datec = $now;
		$this->column = 1;
		$this->drawer = 2;
		$this->shelf = 3;
		$this->column_is_alpha = 0;
		$this->drawer_is_alpha = 1;
		$this->shelf_is_alpha = 0;
	}
}
