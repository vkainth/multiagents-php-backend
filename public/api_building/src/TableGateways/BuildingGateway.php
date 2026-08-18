<?php
namespace Src\TableGateways;

use Src\Cache\Cache as Cache;

class BuildingGateway {

    private $db = null;
    private $dbMLS = null;
    private $dbLES = null;
    private $cache = null;

    public function __construct($db,$dbMLS,$dbLES)
    {
        $this->db = $db;
        $this->dbMLS = $dbMLS;
        $this->dbLES = $dbLES;
        if(getenv('ENV') === 'production') {
            $this->cache = new Cache('MEMCACHE', 3600);
        }
    }

    /**
     * @return mixed
     */
    public function findAll()
    {
        $statement = "
            SELECT
                *
            FROM
                condos;
        ";

        try {
            $statement = $this->db->query($statement);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage()."__1");
        }
    }

    /**
     * @param $strataNo
     * @return mixed
     */
    public function find($strataNo)
    {
        $statement = "
            SELECT
                *
            FROM
                condos
            WHERE strata_no = ?;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array($strataNo));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage()."__2");
        }
    }

    /**
     * [getBuildingObjectWithCondoId description]
     * @param  int|string $condoid [condos.id field from ]
     * @return array|bool|false|mixed|string          [description]
     */
    public function getBuildingObjectWithCondoId($condoid) {
        $cached = $this->checkCache('building_condoid_'.$condoid);
        if($cached) {
            // return $cached;
        }
        
        $statement = "SELECT * from `condos` WHERE `id` = '". $condoid ."' AND `visible` = 'y' AND `active`='y'";

        // if(!empty($streetNo)){
        //     $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `street_no` = '". $streetNo ."' AND `visible` = 'y' AND `active`='y'";
        // }else{
        //     $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `visible` = 'y' AND `active`='y'";
        // }

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(getenv('ENV') === 'production') {
                $this->cache->set($strataNo, $result);
            }
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage()."__3");
        }
        // return $amenities;
    }

    /**
     * @param $strataNo
     * @return array|bool|false|mixed|string
     */
    public function getBuildingObject($strataNo, $streetNo=null) {
        $cachedKey = 'building_'.$strataNo.(!empty($streetNo)?('_withstreet_'.$streetNo):'');
        
        $cached = $this->checkCache($cachedKey);
        
        if($cached) {
            return $cached;
        }
        
        $statement = "select * from condos where `strata_no` = '". $strataNo ."' AND `visible` = 'y' AND `active`='y'";

        if(!empty($streetNo)){
            $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `visible` = 'y' AND `active`='y'  ORDER BY `street_no` LIKE '".$streetNo."'  DESC, `updated` DESC "; //" `street_no` DESC"; [new order-by-like-street_no: 26-04-2022]
            // $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `street_no` = '". $streetNo ."' AND `visible` = 'y' AND `active`='y'  ORDER BY `street_no` LIKE '".$streetNo."' "; // " `street_no` DESC"; [new order-by-like-street_no: 26-04-2022]
        }else{
            $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `visible` = 'y' AND `active`='y'  ORDER BY `street_no` LIKE '".$streetNo."' DESC, `updated` DESC "; //" `street_no` DESC"; [new order-by-like-street_no: 26-04-2022]
        }

        /* [Updated to orderBy-Like-street_no :26-04-2022]
        if(!empty($streetNo)){
            $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `street_no` = '". $streetNo ."' AND `visible` = 'y' AND `active`='y'  ORDER BY `street_no` DESC";
        }else{
            $statement = "SELECT * from `condos` WHERE (`strata_no` = '".$strataNo."' OR (`strata_no` = '' AND `slug` = '".$strataNo."') ) AND `visible` = 'y' AND `active`='y'  ORDER BY `street_no` DESC";
        }*/

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(getenv('ENV') === 'production') {
                $this->cache->set($strataNo, $result);
            }
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage()."__4");
        }
        // return $amenities;
    }

    /**
     * @param $strataNo
     * @param null $building_id
     * @return string[]
     */

    private function checkCache($key){
        if($this->cache) {
            if($this->cache->get($key)) {
                return $this->cache->get($key);
            }
        }
        return false;
    }

    /**
     * @param $table
     * @param $id
     * @return mixed
     */
    public function getName($table, $id){
        $key = 'name-'.$id;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }

        $statement = "select name from `" . $table . "` where id = '" . $id . "'";
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($result)>0){
                if(getenv('ENV') === 'production') {
                    $this->cache->set($key, $result[0]['name']);
                }
                return $result[0]['name'];
            }
        } catch (\PDOException $e) {
            exit($e->getMessage()."__5");
        }
    }

    /**
     * @param $id
     * @return mixed
     */

    public function get_strataManagement($id){
        $key = 'strata-mgmt'.$id;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }
        $statement = "SELECT name,phone,email, `website` FROM companies WHERE  id = '" . $id . "'"; //[`website` -added:29-09-2021]
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($result)>0){
                if(getenv('ENV') === 'production') {
                    $this->cache->set($key, $result[0]);
                }
                return $result[0];
            }
        } catch (\PDOException $e) {
            exit($e->getMessage()."__6");
        }
    }

    /**
     * @param $id
     * @return array
     */
    public function getStrata_Minutes($id){
        $key = 'strata-minutes'.$id;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }
        $data = array();
        $statement = "select * from bccondos.condo_files where condo_id = " . $id . " and category = 'strata minutes' order by `date` desc";
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(getenv('ENV') === 'production') {
                $this->cache->set($key, $result);
            }
            return $result;

        } catch (\PDOException $e) {
            exit($e->getMessage()."__7");
        }
    }

    /**
     * @param $id
     * @param $strata_no
     * @param $street_no
     * @param $street_name
     * @return mixed
     */
    public function getCondoSuites($id,$strata_no,$street_no,$street_name){
        $condo_Suites =array();

        $key = 'condo-suites'.$strata_no;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }

        $statement = "SELECT floorplan_list.id,floorplan_list.property_postal_code,floorplan_list.strata_lot,floorplan_list.pid,floorplan_list.suite,floorplan_list.address,floorplan_list.searchtext,floorplan_list.building_id,floorplan_list.property_id,floorplan_list.floor,floorplan_list.number,floorplan_list.slug FROM floorplan_list WHERE floorplan_list.building_id =" . $id . " AND (floorplan_list.searchtext LIKE '%" .$strata_no. "%' || floorplan_list.searchtext LIKE '%" .$street_no. ' ' . $street_name . "%') GROUP BY floorplan_list.suite ORDER BY `floor` ASC, number ASC, suite DESC LIMIT 11";
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                //Here we get MLS&reg; Id
                $row['mls'] = $this->getlistings_mls($row['slug'],$id,$strata_no);
                //Here we get property details
                $data = $this->getproperty($row['property_id']);
                $row['bedrooms']		= $data['bedrooms'];
                $row['squarefoot']		= $data['squarefoot'];
                $row['session_key']		= $data['session_key'];
                $row['master_key']		= $data['master_key'];
                $row['not_available']	= $data['not_available'];
                $row['propertyID']		= $data['propertyID'];
                $row['full_bathrooms']	= '';
                $condo_Suites[] = $row;
            }

        } catch (\PDOException $e) {
            exit($e->getMessage()."__8");
        }
        if(getenv('ENV') === 'production') {
            $this->cache->set($key, $condo_Suites);
        }
        return $this->condo_Suites = $condo_Suites;
    }

    /**
     * @param $propertyid
     * @return mixed
     */
    public function getproperty($propertyid){
        $key = 'property-'.$propertyid;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }
        $statement = "SELECT property.bedrooms, property.squarefoot, property.session_key, property.master_key, property.not_available, property.id AS propertyID FROM property WHERE id='" . $propertyid . "'";
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($result)>0){
                if(getenv('ENV') === 'production') {
                    $this->cache->set($key, $result[0]);
                }
                return $result[0];
            }
        } catch (\PDOException $e) {
            exit($e->getMessage()."__9");
        }
    }

    /**
     * @param $floorslug
     * @param $id
     * @param $strata_no
     * @return mixed
     */
    public function getlistings_mls($floorslug,$id,$strata_no){
        $key = 'listing-mls-'.$strata_no;
        $cached = $this->checkCache($key);
        if($cached) {
            return $cached;
        }

        if ($strata_no != '') {
            $where = "AND strata_no='" . $strata_no . "'";
        }
        $statement = "SELECT listingid as mls FROM pixilink_mlsr.mlsr_listings WHERE mlsr_listings.floorplan ='" . $floorslug . "' AND mlsr_listings.building_id =" . $id . " AND `status`='Active' AND listingid!='R2335404' AND reciprocity='Yes' " . $where;
        try {
            $statement = $this->db->prepare($statement);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($result)>0){
                if(getenv('ENV') === 'production') {
                    $this->cache->set($key, $result[0]);
                }
                return $result[0];
            }
        } catch (\PDOException $e) {
            exit($e->getMessage()."__10");
        }
    }

    /**
     * @param $string
     * @return array
     */
	public function remove_ultags($string){
		$rows=array();
		$string=str_replace('<li>','',$string);
		$string=str_replace('<ul>','',$string);
		$string=str_replace('</ul>','',$string);
		$rows=array_map('trim',array_filter(explode('</li>',$string)));
		unset($rows[count($rows)-1]);
		return $rows;
	}
	public function getDocuments($id){
		$condo_media=null;
		$statement = "select id,width,location,name from media WHERE id= '".$id."'";
	
		try {
			$statement = $this->db->prepare($statement);
			$statement->execute();
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			if(count($result)>0){
				foreach ($result as $row) {
					$condo_media = 'https://bccondos.net/uploads/'.$row['location'];
				}
			}
			return $condo_media;
		}catch (\PDOException $e) {
			exit($e->getMessage()."__11");
		}
	}
	/*public function getPhotos($id){
		$photos=array();
		$statement = "select * from bccondos.condo_media where (tag = '' OR tag is null) and type = 'images'  and condo_id = " . $id . " AND visible='y' order by `order` asc, `id` asc ";
		try {
			$statement = $this->db->prepare($statement);
			$statement->execute(array($id));
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			if(count($result)>0){
				foreach ($result[0] as $row) {
					$photos[] = array('title' => $row['title'], 'high' => new Media($row['media2']), 'low' => new Media($row['media']), 'original' => new Media($row['media']));
				}
			}
			return $photos;
		} catch (\PDOException $e) {
			exit($e->getMessage());
		}
		
	}*/
	public function getCondoMedia($id){
		$condo_media=array();
		$statement = "select m.id,m.width,m.location,m.name,c.title,c.media2,c.media,c.type,c.condo_id from bccondos.condo_media c LEFT JOIN  media m on c.media2=m.id where c.type ='maps' and c.condo_id = " . $id . "    ORDER BY FIELD(c.type, 'c.pdfmaps') ASC,c.typical DESC  ";
		try {
			$statement = $this->db->prepare($statement);
			$statement->execute();
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			if(count($result)>0){
				foreach ($result as $row) {
					$condo_media[] = array('title' => $row['title'], 'media' =>'https://bccondos.net/Wm_newwmimg.php?v=1&w='.$row['width'].'&imgname=https://bccondos.net/uploads/'.$row['location'].'', 'type' => $row['type'],'name' => $row['name']);
				}
			}
			return $condo_media;
		}catch (\PDOException $e) {
			exit($e->getMessage()."__12");
		}
	}

    /**
     * getSiblings get-buildings-grouped-by-same-complex/area
     * @param  int|array $building_info_arg since-12-Aug-2021, arg can be either Array:$buildings_info or id:$building_info['id']
     *                                      for-consitency as used in $more_from_bccnet['complex_buildings']
     * @return array                    [Array-of-ids eg:["8027","10223","8028","8030","8031"...] ]
     */
    public function getSiblings($building_info_arg){
		
        $building_info_id = (is_array($building_info_arg) && isset($building_info_arg['id']))?$building_info_arg['id']:$building_info_arg;
		$siblings = array();
		$siblings[] = $building_info_id ; //$building_info['id'];
		// $this->explore($building_info['id'], $siblings);
        $this->explore($building_info_id, $siblings);
		//    array_pop($siblings);
		return array_unique($siblings);
        
        // following-currently-disabled for efficiency [12-08-2021] till:
        // mapping-discovered/created from -bccondos.net/condo_id  to bcch/buildings/slug 

        $uniqueSiblings = array_unique($siblings);

        foreach($uniqueSiblings as $condo_id){
            $sql = "SELECT condos.* FROM bccondos.condos WHERE condos.id='{$condo_id}' LIMIT 0,1 ";
            $retArray[]= $this->getSqlFromBccNet($sql);
        }

        return $retArray;
	}
	public function explore($id, &$array){
		
		$sql = 'select condo1 from bccondos.condo_condo where condo2 = ' . $id;
		
		try {
				$sql = $this->db->prepare($sql);
				$sql->execute();
				$res = $sql->fetchAll(\PDO::FETCH_ASSOC);
				foreach ($res as $row) {
				if (!in_array($row['condo1'], $array)) {
					$array[] = $row['condo1'];
					$this->explore($row['condo1'], $array);
				}
			}
			
			$sql = 'select condo2 from bccondos.condo_condo where condo1 = ' . $id;
			//			echo $sql."<hr>";
			try {
				$sql = $this->db->prepare($sql);
				$sql->execute();
				$res2 = $sql->fetchAll(\PDO::FETCH_ASSOC);
				foreach($res2 as  $row ) {
					if (!in_array($row['condo2'], $array)) {
						$array[] = $row['condo2'];
						$this->explore($row['condo2'], $array);
					}
				}
			}catch (\PDOException $e) {
				exit($e->getMessage()."__13");
			}
		}catch (\PDOException $e) {
			exit($e->getMessage()."__14");
		}
		
		
	}
	public function getFloorPlan($building_info){				
		if($building_info['import_id']!='' && $building_info['les_building_id']!=''){
			$sql = "SELECT floorplan_list_newpar.slug as slugvfp,floorplan_list_newpar.suite,floorplan_list_newpar.address,CONCAT('https://www.vancouverfloorplans.com', floorplan_list_newpar.slug) AS floorplanurl,CONCAT('https://www.vancouverfloorplans.com/images/floorplanbccapi.php?id=',property_floor_plan.floor_plan_image_id,'&key=',floor_plan_image.master_key) AS floorplanimages,
			 floor_plan_image.data as fpblob,
			 floor_plan_image.name AS imgname,
			 property.bedrooms, squarefoot, floor_plan_image.session_key, floor_plan_image.master_key, not_available, property.id AS propertyID, mlsr_listings.listingid AS 'mls' 
			 FROM les_les.floorplan_list_newpar 
			 JOIN les_les.property ON (floorplan_list_newpar.property_id = property.id) 
			 JOIN les_les.property_floor_plan ON (property_floor_plan.property_id = property.id) 
			 JOIN floor_plan_image on (floor_plan_image.id = property_floor_plan.floor_plan_image_id)
			 LEFT JOIN pixilink_mlsr.mlsr_listings ON (mlsr_listings.suite_no = floorplan_list_newpar.suite AND mlsr_listings.internal_building_id = ".$building_info['import_id']." AND `status` = 'Active' AND reciprocity = 'Yes') WHERE floorplan_list_newpar.building_id = ".$building_info['les_building_id']." ORDER BY `floor` ASC, number ASC, suite ASC";
			try{
				$sql = $this->dbLES->prepare($sql);
				$sql->execute(array($building_info['id']));
				$res = $sql->fetchAll(\PDO::FETCH_ASSOC);
				if(count($res)>0){
					return $res;
				}
			}catch (\PDOException $e) {
				exit($e->getMessage()."__15");
			}					 
		}
	}
	public function getFloorPlateData($floor='',$building_info,$slugvfp){
		$vfpslug = explode('/',$slugvfp);
		if($building_info['import_id']!='' && $building_info['les_building_id']!=''){
			$sql = "select floor_plate.image AS floorid,CONCAT('Floor ".$floor."') AS floor,   floor_plan_image.name,CONCAT('https://www.vancouverfloorplans.com/".$vfpslug['1']."/floor".$floor."') AS floorplateurl,CONCAT('https://www.vancouverfloorplans.com/images/floorplanbccapi.php?id=',floor_plate.image,'&key=',master_key) AS floorplateimages from floor_plate join floor_plan_image on (floor_plate.image = floor_plan_image.id) where (floors like '".$floor.",%' or floors like '%,".$floor."' or floors like '".$floor."' or floors like '%,".$floor.",%') and floor_plate.building = ".$building_info['les_building_id'];
			try{
				$sql = $this->dbLES->prepare($sql);
				$sql->execute(array($building_info['id']));
				$res = $sql->fetchAll(\PDO::FETCH_ASSOC);
				if(count($res)>0){
					return $res;
				}
			}catch (\PDOException $e) {
				exit($e->getMessage()."__16");
			}					 
		}
	}
	public function getFloorPlate($building_info){		
		if($building_info['import_id']!='' && $building_info['les_building_id']!=''){
			$sql = "SELECT * from floor_plate where floors not in ('P1','P2','P3') and building = ".$building_info['les_building_id'];
			try{
				$sql = $this->dbLES->prepare($sql);
				$sql->execute(array($building_info['id']));
				$res = $sql->fetchAll(\PDO::FETCH_ASSOC);
				if(count($res)>0){
					return $res;
				}
			}catch (\PDOException $e) {
				exit($e->getMessage()."__17");
			}					 
		}
	}

    public function getBuildingResources($building_resources){
        $aLinksRaw = $this->remove_ultags($building_resources);
        $aLinks = array();
        foreach($aLinksRaw as $htmlString){
            $linkUrl = preg_match('/<a href=["\']?([^"\'>]+)["\']?/', $htmlString, $match);
            $linkUrl = $match[1]?:'';
            $linkTxt = preg_match('/>(.+)</a>/', $htmlString, $matchText);
            $aLinks[] = [
                'href'=> $linkUrl,
                'text'=> strip_tags($htmlString),
                // 'raw'=> $htmlString,
            ];
        }
        return $aLinks;
    }

    public function getSqlFromBccNet($sql){
        $retAry = [];
        try {
            $statement = $this->db->prepare($sql);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage()."__18");
            return $e->getMessage()."__19";
        }
        return $retAry;

    }

    public function getBccNetMedia($id){
        $sql = 'select * from bccondos.media where id = '.$id;
        return $this->getSqlFromBccNet($sql);
    }
    
    public function getBccNetFloorPlateMedia($id){
        $sql = "select id, name from bccondos.floor_plan_image where id = ".$id;
        return $this->getSqlFromBccNet($sql);
        //return $res['id']."_".$res['name'];
    }

    /**
     * [getBccNetJoinedMedia description]
     * @param  [type] $id   [description]
     * @param  [type] $type [type=images/maps/pdfmaps etc, 
     *                      blank returns all media related to the id]
     * @return [type]       [description]
     */
    public function getBccNetJoinedMedia($id, $type=null){
        $sql = "SELECT m.*,cm.* FROM `condo_media` `cm` 
        INNER JOIN `media` `m` 
        ON m.id=cm.media 
        WHERE 
        cm.`condo_id` IN ($id) 
        AND (cm.tag = '' OR cm.tag is NULL) 
        AND cm.visible='y' 
        ";
        if(!empty($type)){
            $sql.= " AND cm.`type`='$type' ";
        }
        return $this->getSqlFromBccNet($sql);
    }

    public function getBccNetPhotos($id){
        $sql = "SELECT title,media2,media from bccondos.condo_media where (tag = '' OR tag is null) and type = 'images'  and condo_id = " . $id . " AND visible='y' order by `order` asc, `id` asc ";
     
        $retArray = $this->getSqlFromBccNet($sql);
     
        foreach($retArray as &$row ){
            $row['media_details'] = $this->getBccNetMedia( $row['media']) ;
        }

        return $retArray;

    }


    public function getBccNetMapsImages($id){
        $sql = "SELECT title,media2,media from bccondos.condo_media where (tag = '' OR tag is null) and type = 'maps'  and condo_id = " . $id . " AND visible='y' order by `order` asc, `id` asc ";
     
        $retArray = $this->getSqlFromBccNet($sql);
        
        $count = 1;
        foreach($retArray as &$row ){
            $row['title'] = "Complex Site Map ".$count;
            $row['media_details'] = $this->getBccNetMedia( $row['media']) ;
            $count++;
        }

        return $retArray;

    }

    public function getBccNetPdfmaps($id){
        $retArray = [];
        
        $sql = "SELECT title,media2,media,type,condo_id from bccondos.condo_media where type = 'pdfmaps' and condo_id = " . $id . " ORDER BY FIELD(type, 'pdfmaps') ASC,typical DESC ";
        
        $retArray = $this->getSqlFromBccNet($sql);
        
        foreach($retArray as &$row ){
            $row['media_details'] = $this->getBccNetMedia( $row['media']) ;

            // $retArray[] = array('title' => $row['title'], 'highres' => $this->getBccNetMedia($row['media2']), 'lowres' => $this->getBccNetMedia($row['media']), 'original' => $this->getBccNetMedia($row['media']), 'type' => $row['type'], 'condo_id' => $row['condo_id']);
        }
        
        return $retArray;
    }

    public function getBccNetFloorPlate($id){
    	$sql = "SELECT  * FROM bccondos.floor_plate WHERE `building` = " . $id . "  order by ABS(floors)";

    	$retArray = $this->getSqlFromBccNet($sql);

    	foreach($retArray as &$row ){
    	    $image = $this->getBccNetFloorPlateMedia( $row['image']);
    	    //$row['media_details'] = $image;
    	    if(count($image) > 0){
    	        $row['media_details']['url'] = "https://bccondos.net/getFloorPlateImage.php?image=".$image[0]['id']."_".$image[0]['name'] ;
    	    }
    	}

    	return $retArray;


    }
    
	public function getBccNetNews($id=0, $offset=0, $limit=10){
        
        $tomorrow = mktime(0, 0, 0, date("m") - 2, date("d"), date("Y"));
        
        $lastmonthdate = date("Y/m/d", $tomorrow);

        if(empty($offset)){
            // $request = new Request();
            // if(!empty( $request->input('page') )) $offset = $request->input('page');
        }
        
        $sql = "SELECT `articlesurl`,`articlesname`,`articletitle`,`articledec`,`datearticles_d`,`datearticles_m`,`datearticles`,`source`, n.*  FROM bccondos.news `n` WHERE publishdate>='$lastmonthdate' ORDER BY publishdate DESC LIMIT $offset, $limit";
        
        $res = $this->getSqlFromBccNet($sql);

        return $res;        
    }


}
