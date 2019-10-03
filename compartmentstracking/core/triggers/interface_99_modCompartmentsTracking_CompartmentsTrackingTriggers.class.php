<?php
/* Copyright (C) 2018 Open-DSI <info@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modCompartmentsTracking_CompartmentsTrackingTriggers.class.php
 * \ingroup compartmentstracking
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modCompartmentsTracking_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

dol_include_once('/compartmentstracking/lib/compartmentstracking.lib.php');

/**
 *  Class of triggers for CompartmentsTracking module
 */
class InterfaceCompartmentsTrackingTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "CompartmentsTracking triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
		$this->picto = 'compartmentstracking@compartmentstracking';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        if (empty($conf->compartmentstracking->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

        switch ($action) {

		    // Stock mouvement.
		    case 'STOCK_MOVEMENT':

		    	if ( ! empty( $object->errors ) ) {
				    // Has errors.
		    		break;
			    }

			    // var_dump($action);
			    // var_dump($_POST['nbpiece'], $_POST['id_entrepot'], $_POST['mouvement'], $_POST['id_compartment'], $_POST['id_compartment_destination']);
		    	// var_dump($object);

		    	$stock_movement = array();

		    	$stock_movement['qty'] = (int) abs( $object->qty );

		    	$stock_movement['type'] = $object->type; // Is movement type: add, 0 or remove.

				$stock_movement['product_id'] = $object->product_id;

				$stock_movement['entrepot_id'] = $object->entrepot_id;

				$stock_movement['compartment_id'] = GETPOST( 'id_compartment', 'int' );

				$stock_action = GETPOST( 'action', 'alpha' );

				if ( ( $stock_action === 'transfert_stock'
						|| $stock_action === 'createmovements' ) // @see product/stock/masstockmove.php.
					&& $stock_movement['type'] == 0 ) {

					// Is transfer and adding stock: get destination compartment.
					$stock_movement['compartment_id'] = GETPOST( 'id_compartment_destination', 'int' );
				} elseif ( $stock_action === 'dispatch' ) {

					static $split_i = 0;
					static $product_id_tmp;

					if ( ! empty( $product_id_tmp )
						&& $product_id_tmp !== $object->product_id ) {

						// Reset split i on product change.
						$split_i = 0;
					}

					$product_id_tmp = $object->product_id;

					$stock_movement['compartment_id'] = $_POST[ 'id_compartment' . $object->product_id ][ $split_i++ ];
				}

		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		        // var_dump( $stock_movement );var_dump($object);
		        // exit;

		        $corrected = compartmentstracking_product_stock_correct( $stock_movement );

		        return $corrected;

		        break;
		}

		return 0;
	}
}
