<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for Mantis module
 */
 
class InterfaceTarifWorkflow
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger du module de tarif par conditionnement";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
	
	function _getRemise($idProd,$qty,$conditionnement,$weight_units){
		//--- Aide ---
		//$qty = quantité testée (champs quantité dans l'ajout d'un produit à une propale par exemple)
		//$contitionnement = champs entre "Qté" et "Poids" dans l'ajout d'un produit à une propale
		//$weight_units = champs "Poids" dans l'ajout d'un produit à une propale
		
		/*echo "\$idProd : ".$idProd."<br />";
		echo "\$qty : ".$qty."<br />";
		echo "\$conditionnement : ".$conditionnement."<br />";
		echo "\$weight_units : ".$weight_units."<br />";*/

		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure !!!)
		$sql = "SELECT p.type_remise as type_remise, tc.quantite as quantite, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product";
		$sql.= " WHERE fk_product = ".$idProd;
		$sql.= " ORDER BY quantite DESC"; //unite_value DESC, 
		
		$resql = $this->db->query($sql);
		
		// Quantité totale de produit ajoutée dans la ligne
		//$qte_totale = $qty * $conditionnement * pow(10, $weight_units);
		
		if($resql->num_rows > 0) {
			$pallier = 0;
			while($res = $this->db->fetch_object($resql)) {
				if($qty>=$res->quantite && $res->type_remise == "qte"){
					//Ici on récupère le pourcentage correspondant et on arrête la boucle
					return $res->remise_percent;
				} else if($conditionnement>=$res->quantite && $res->type_remise == "conditionnement" && $res->unite_value == $weight_units) {
					return $res->remise_percent;
				}
			}
			//return -1;
		}
		
		/*
		$conditionnement_total = $qty * $conditionnement;
		
		//Si il existe au moin un prix par conditionnement
		if($resql->num_rows > 0) {
			while($res = $this->db->fetch_object($resql)){
				
				$qte_totale_grille = $res->quantite * pow(10, $res->unite_value); // latoxan !!
				
				if($qte_totale_grille <= $qte_totale) {
					//Récupération de la remise
					return $res->remise_percent;
				}
			}
			return -1;
		}*/
		
		//return -2;
	}

	function _getPrix($idProd,$qty,$conditionnement,$weight_units){

		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure !!!)
		$sql = "SELECT p.type_remise as type_remise, tc.type_price, tc.quantite as quantite, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product";
		$sql.= " WHERE fk_product = ".$idProd;
		$sql.= " ORDER BY quantite DESC"; //unite_value DESC, 
		
		$resql = $this->db->query($sql);
		
		// Quantité totale de produit ajoutée dans la ligne
		//$qte_totale = $qty * $conditionnement * pow(10, $weight_units);
		
		if($resql->num_rows > 0) {
			$pallier = 0;
			while($res = $this->db->fetch_object($resql)) {
				if($qty>=$res->quantite && $res->type_remise == "qte" && $res->type_price = 'PRICE'){
					//Ici on récupère le pourcentage correspondant et on arrête la boucle
					return $res->prix;
				} else if($conditionnement>=$res->quantite && $res->type_remise == "conditionnement" && $res->unite_value == $weight_units && $res->type_price = 'PRICE') {
					return $res->prix;
				}
			}
			//return -1;
		}
	}
	
	function _updateLineProduct(&$object,&$user,$idProd,$conditionnement,$weight_units,$remise, $prix){
		if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
		dol_include_once('/tarif/config.php');
		
		$product = new Product($this->db);
		$product->fetch($idProd);
		
		$object_parent = $this->_getObjectParent($object);
		
		/*echo '<pre>';
		print_r($object_parent);
		echo '</pre>'; exit;*/
		$conditionnement = $conditionnement * pow(10, ($weight_units - $product->weight_units ));
		
		//echo $product->price; exit;
		$object->remise_percent = $remise;
		$object->subprice = (!empty($product->multiprices[$object_parent->client->price_level])) ? $product->multiprices[$object_parent->client->price_level] : $product->price ;
		
		if($prix > 0){
			$object->subprice = $prix;
		}
		
		/*echo "\$object->subprice : ".$object->subprice."<br >";
		echo "\$conditionnement : ".$conditionnement."<br >";
		echo "\$product->weight : ".$product->weight."<br >";*/
		//exit;
		$object->subprice = $object->subprice  * ($conditionnement / $product->weight);
		
		$object->price = $object->subprice; // TODO qu'est-ce ? Due à un deprecated incertain, dans certains cas price est utilisé et dans d'autres c'est subprice
		//echo $object->subprice; exit;
		
 		if(get_class($object_parent) == "Facture" && $object_parent->type == 2){ // facture d'avoir
 			$object->remise_percent = $object->remise_percent * (-1);
			$object->subprice = $object->subprice * (-1);
			$object->price = $object->subprice;
		}
		
		if(get_class($object) == 'FactureLigne') $object->update($user, true);
		else $object->update(true);
	}
	
	function _updateTotauxLine(&$object,$qty){
		//MAJ des totaux de la ligne
		$object->total_ht = $object->subprice * $qty * (1 - $object->remise_percent / 100);
		$object->total_tva = ($object->total_ht * (1 + ($object->tva_tx/100))) - $object->total_ht;
		$object->total_ttc = $object->total_ht + $object->total_tva;
		$object->update_total();
	}
	
	function _getObjectParent(&$object){
		switch (get_class($object)) {
			case 'PropaleLigne':
				$object_parent = new Propal($this->db);
				$object_parent->fetch((!empty($object->fk_propal)) ? $object->fk_propal : $object->oldline->fk_propal);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
			case 'OrderLine':
				$object_parent = new Commande($this->db);
				$object_parent->fetch((!empty($object->fk_commande)) ? $object->fk_commande : $object->oldline->fk_commande);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
			case 'FactureLigne':
				$object_parent = new Facture($this->db);
				$object_parent->fetch((!empty($object->fk_facture)) ? $object->fk_facture : $object->oldline->fk_facture);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
		}
		return $object_parent;
	}
	
	
	//Calcule le prix de la ligne de facture
	private function calcule_prix_facture(&$res,&$object){
		$poids_exedie = ($res->weight * pow(10, $res->weight_unit))* $res->price;
		$poids_commande = ($res->tarif_poids * pow(10, $res->poids)) * $object->qty;
		$prix = $poids_exedie / $poids_commande;
		return floatval($prix);
	}
	
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
	{
		
		if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
		dol_include_once('/tarif/config.php');
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/fourn/class/fournisseur.commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/comm/propal/class/propal.class.php');
		dol_include_once('/dispatch/class/dispatchdetail.class.php');
		
		global $user;
		
		/*echo '<pre>';
		print_r($_REQUEST);
		echo '</pre>';exit;*/
		
		//Création d'une ligne de facture, propale ou commande
		if (($action == 'LINEORDER_INSERT' || $action == 'LINEPROPAL_INSERT' || $action == 'LINEBILL_INSERT') 
			&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			
			$idProd = 0;
			if(!empty($_POST['idprod'])) $idProd = $_POST['idprod'];
			if(!empty($_POST['productid'])) $idProd = $_POST['productid'];
			
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			if(GETPOST('poids', 'int')){
				
				$poids = (!empty($_POST['poids'])) ? floatval($_POST['poids']) : 0;
				$weight_units = $_POST['weight_units'];
				
				if(!empty($idProd)){
					
					$remise = $this->_getRemise($idProd,$_POST['qty'],$poids,$weight_units);
					$prix = 0;
					if($remise <= 0)
						$prix = $this->_getPrix($idProd,$_POST['qty'],$poids,$weight_units);
					
					//Quantité en dehors de la grille alors retourner erreur
					/*if($remise == -1){
						$this->db->rollback();
						$this->db->rollback();
						$object->error = "Quantité trop faible";
						return -1;
					}*/
					
					$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix); //--- $poids = conditionnement !
					$this->_updateTotauxLine($object,$_POST['qty']);
				}

				//MAJ du poids et de l'unité de la ligne
				if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
				if(get_class($object) == 'OrderLine') $table = 'commandedet';
				if(get_class($object) == 'FactureLigne') $table = 'facturedet'; 
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
				
				//echo "1 "; exit;
			} 
			
			// Sinon, Si l'object origine est renseigné et est soit une propale soit une commande
			// => filtre sur propale ou commande car confli éventuel avec le trigger sur expédition
			elseif(   ((!empty($object->origin) && !empty($object->origin_id)) 
					|| (!empty($_POST['origin']) && !empty($_POST['originid'])))
					&& ($_POST['origin'] == "propal" || $object->origin == "commande" || $object->origin == "shipping")){

				//Cas propal on charge la ligne correspondante car non passé dans le post
				if($_POST['origin'] == "propal"){
					
					if(isset($_POST['facnumber']))
						$table = "facturedet";
					else
						$table = "commandedet";
					
					$propal = new Propal($this->db);
					$propal->fetch($_POST['originid']);
					
					foreach($propal->lines as $line){
						if($line->rang == $object->rang)
							$originid = $line->rowid;
					}
					$sql = "SELECT tarif_poids as weight, 1 as qty, poids as weight_unit 
							FROM ".MAIN_DB_PREFIX."propaldet
							WHERE rowid = ".$originid;
	        	}
				//Cas commande la ligne d'origine est déjà chargé dans l'objet
				elseif($object->origin == "commande"){
					$table = "facturedet";
					$originid = $object->origin_id;
					$sql = "SELECT tarif_poids as weight, 1 as qty, poids as weight_unit 
							FROM ".MAIN_DB_PREFIX."commandedet
							WHERE rowid = ".$originid;
				}
				
				elseif($object->origin == "shipping"){
					
					//SI TU AS UNE ERREUR ICI C'EST QUE TU AS OUBLIE LE README DU MODULE TARIF
					$table = "facturedet";
					$originid = $object->origin_id;
					
					if(FACTURE_DISPATCH_ON_EXPEDITION && $conf->dispatch->enabled){
						$sql = "SELECT eda.weight as weight, eda.weight_unit as weight_unit, cd.price, cd.tarif_poids, cd.poids, ed.qty as qty";
					}
					else{
						$sql = "SELECT SUM(eda.weight) as weight, eda.weight_unit as weight_unit, cd.price, cd.tarif_poids, cd.poids, COUNT(eda.weight_unit) as qty";
					}
					
					$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet_asset eda
								LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (ed.rowid = eda.fk_expeditiondet)
								LEFT JOIN ".MAIN_DB_PREFIX."commandedet as cd ON (cd.rowid = ed.fk_origin_line)
								LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = cd.fk_product)
							WHERE eda.fk_expeditiondet = ".$originid."
							AND cd.fk_product = ".$object->fk_product;
							
					if(!FACTURE_DISPATCH_ON_EXPEDITION && $conf->dispatch->enabled){
						$sql.= " GROUP BY eda.weight_unit, cd.fk_product ";
					}
					
					$sql.= " ORDER BY eda.weight_unit ASC";
 				}

				//echo $sql; exit;
				
				$resql = $this->db->query($sql);
				$res = $this->db->fetch_object($resql);

				$poids = $res->weight;
				$weight_units = $res->weight_unit;
				$object->qty = $res->qty;

				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".($poids / $object->qty).", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

				if($object->origin == "shipping"){
					$object->subprice = $this->calcule_prix_facture($res,$object);

					$object->update($user);
					$this->_updateTotauxLine($object,$object->qty);
					
					//Si plusieurs flacons avec des unités différentes ont été envoyé
					//on ajoute des lignes de facture suplémentaire
					while($res = $this->db->fetch_object($resql)){
						$newrowid = $object->insert(true);
						$poids = $res->weight;
						$weight_units = $res->weight_unit;
						$object->qty = $res->qty;

						$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".($poids / $object->qty).", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

						$object->subprice = $this->calcule_prix_facture($res,$object);
						$object->update($user);	
						$this->_updateTotauxLine($object,$object->qty);
					}
				}
			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);
		}

		elseif(($action == 'LINEORDER_UPDATE' || $action == 'LINEPROPAL_UPDATE' || $action == 'LINEBILL_UPDATE') 
				&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			
			$idProd = 0;
			if(!empty($_POST['idprod'])) $idProd = $_POST['idprod'];
			if(!empty($_POST['productid'])) $idProd = $_POST['productid'];
			
			if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
			if(get_class($object) == 'OrderLine') $table = 'commandedet';
			if(get_class($object) == 'FactureLigne') $table = 'facturedet';
			$resql = $this->db->query("SELECT tarif_poids, poids FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".$object->rowid);
			
			$res = $this->db->fetch_object($resql);
			
			//echo floatval($res->tarif_poids * pow(10, $res->poids))." ".floatval($_POST['poids'] * pow(10, $_POST['weight_units']));exit;
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			// ET si la quantité ou le poids a changé
			if($object->oldline->qty != $_POST['qty'] || floatval($res->tarif_poids * pow(10, $res->poids)) != floatval($_POST['poids'] * pow(10, $_POST['weight_units']))){
				
				$poids = (!empty($_POST['poids'])) ? floatval($_POST['poids']) : 0;
				$weight_units = $_POST['weight_units'];
				
				if(!empty($idProd)){
					
					$remise = $this->_getRemise($idProd,$_POST['qty'],$poids,$weight_units);
					
					$prix = 0;
					if($remise <= 0)
						$prix = $this->_getPrix($idProd,$_POST['qty'],$poids,$weight_units);
					
					$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise, $prix);
					$this->_updateTotauxLine($object,$_POST['qty']);
					
				}

				//MAJ du poids et de l'unité de la ligne
				if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
				if(get_class($object) == 'OrderLine') $table = 'commandedet';
				if(get_class($object) == 'FactureLigne') $table = 'facturedet'; 
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);

		}
		
		//MAJ des différents prix de la grille de tarif par conditionnement lors d'une modification du prix produit
		elseif($action == 'PRODUCT_PRICE_MODIFY'){
			
			/*echo '<pre>';
			print_r($object);
			echo '</pre>'; exit;*/
			
			$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."tarif_conditionnement WHERE fk_product = ".$object->id);
			
			if($resql->num_rows > 0){
				$res = $this->db->fetch_object($resql);
				//MAJ des tarifs par conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."tarif_conditionnement 
								  SET tva_tx = ".$object->tva_tx.", price_base_type = '".$object->price_base_type."', prix = ".$object->price." 
								  WHERE fk_product = ".$object->id);
			}

			//MAJ du prix 2
			if(isset($_REQUEST['price_1'])){
				$level = 2;	
				$price = str_replace(',', '.', $_REQUEST['price_1']);
				$price = str_replace(' ', '', $price);
				$price = $price * (1 - 0.15);
				$price_ttc = $price * (1 + ($_REQUEST['tva_tx_1'] / 100));
				$base = $_REQUEST['multiprices_base_type_1'];
				$tva_tx = $_REQUEST['tva_tx_1'];
			}
			//MAJ du prix 1
			/*else{
				$level = 1;
				$price = $_REQUEST['price_2'] * (1 + 0.15);
				$price_ttc = $price * (1 + ($_REQUEST['tva_tx_2'] / 100));
				$base = $_REQUEST['multiprices_base_type_2'];
				$tva_tx = $_REQUEST['tva_tx_2'];
			}*/
			$now=dol_now();
			
			/*echo '<pre>';
			print_r($object);
			echo '</pre>';exit;*/
			//echo $object->fk_product_type;exit;
			
			//seulement si produit
			if($object->type == 0){
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price
					(price_level,date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,tosell,tva_tx,recuperableonly,localtax1_tx, localtax2_tx, price_min,price_min_ttc,price_by_qty,entity) 
					VALUES
					(".$level.",'".$this->db->idate($now)."',".$object->id.",".$user->id.",".$price.",".$price_ttc.",'".$base."',".$object->status.",".$tva_tx.",".$object->tva_npr.",".$object->localtax1_tx.",".$object->localtax2_tx.",".$object->price_min.",".$object->price_min_ttc.",0,".$conf->entity.")";
				
				$this->db->query($sql);
			}
		}

		return 1;
	}
}