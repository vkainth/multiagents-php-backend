<?php

namespace Src\Controller;

use Src\TableGateways\BuildingGateway;

class BuildingController
{

    private $db;
    private $dbMLS;
    private $dbLES;
    private $requestMethod;
    private $strataNo;
    private $task;

    private $streetNo;
    private $condoId;
    private $identifierSuccessfull;

    private $buildingGateway;

    public function __construct($db, $dbMLS, $dbLES, $requestMethod, $strataNo, $task = false, $streetNoOrCondoId=null)
    {
        $this->db = $db;
        $this->dbMLS = $dbMLS;
        $this->dbLES = $dbLES;
        $this->requestMethod = $requestMethod;
        $this->strataNo = $strataNo;
        $this->task = $task;
        if(!empty($task) && $task=='trybcnwithid'){
            $this->condoId = $streetNoOrCondoId;
            $this->streetNo = null;
        }else{
            $this->condoId = null;
            $this->streetNo = $streetNoOrCondoId;
        }

        $this->buildingGateway = new BuildingGateway($db, $dbMLS, $dbLES);
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->strataNo) {
                    if($this->task && $this->task == 'floorplan'){
                       $response = $this->getFloorplan($this->strataNo);
                    }else if($this->task && $this->task == 'fetch_all'){
                       $response = $this->getAllBuildings($this->strataNo);
                    }else{
                        $response = $this->getBuilding($this->strataNo);
                    }
                } else if(!empty($this->condoId)) {
                    $response = $this->getBuilding();
                } else {
                    $response = $this->notFoundResponse();
                };
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
            default:
                $response = $this->notFoundResponse();
                break;
        }
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    /**
     * [printTestCacheDir safe to delete : test-created on 14-09-2021 ]
     * @return [type] [description]
     */
    public function printTestCacheDir14092021(){
        echo 'Dir: '. DIR_CACHE;
    }


    /**
     * [getBuildingInfo :First-try-with-condos.id, then with street+strata, then only strata [added-on: 10-09-2021] ]
     * @return [type] [description]
     */
    private function getBuildingInfo($strataNo){
        /* To- resume-previous-working (as before:14-09-2021) [STARTS] */
        // $building_info = $this->buildingGateway->getBuildingObject($strataNo);
        
        // following-block (disabled :2024-08-13) -> to allow condoId-based-fetching
        if(!empty(trim($strataNo,'-')))
        $building_info = $this->buildingGateway->getBuildingObject($strataNo, ($this->streetNo?:null) ); // (enabled-streetNo param on 04-10-2021)
        $this->identifierSuccessfull = $strataNo; // to resume-previous-working [14-09-2021]
        if(!empty($building_info)) return $building_info;
        
        /* To- resume-previous-working (as before:14-09-2021) [ENDS] */


        $building_info = [];

        if(!empty($this->condoId)){
            $building_info = $this->buildingGateway->getBuildingObjectWithCondoId($this->condoId);
        }
        if(empty($building_info)){
            if(!empty($this->streetNo)){
                $building_info = $this->buildingGateway->getBuildingObject($this->$strataNo,$this->streetNo);
            }else{
                $building_info = $this->buildingGateway->getBuildingObject($this->$strataNo);
            }
            $this->identifierSuccessfull = $this->strataNo; // 'strata_'.$this->strataNo; [deactivated-14-09-2021]
        }else{
            $this->identifierSuccessfull = $this->condoId; // 'condoid_'.$this->condoId; [deactivated-14-09-2021]
            // if(!empty($building_info['strata_plan'])){ $this->strataNo = $building_info['strata_plan'];  } // if-needed-to-update strata-on-condos.id basis,maybe in future
        }
        return $building_info;
    }

    /**
     * @return mixed
     */
    private function getBuilding($strataNo)
    {
        $building_info = $this->getBuildingInfo($strataNo);

        $building_condo_info = array();
        $technical_info = array();
        $condo_suites = array();
        $amenities = array();
        $amenities_info = array();
        $features = array();
        $maintenance = array();
        $restrictions = array();
        $construction_info = array();
        $floor_plans = array();
        $condo_mediaMap=array();
        $strata_plan_documents=null;
        $developers_brochure=null;
        $features_brochure_WM=null;
        $disclosure_amendment1=null;
        $disclosure_amendment2=null;
        $disclosure_amendment3=null;
        $builidngnewsarticleswm=null;
        $colliersmappdf=null;

        if ( !empty($building_info) && count($building_info) > 0) {
            $building_info = $building_info[0];
            //echo '<pre>';
            //print_r( $building_info);
            //echo '</pre>';
            $subarea        = ucwords($this->buildingGateway->getName('subareas', $building_info['subarea']));
            $area            = ucwords($this->buildingGateway->getName('areas', $building_info['area']));
            $board_name        = ucwords($this->buildingGateway->getName('boards', $building_info['board']));
            $strata_minutes    = $this->buildingGateway->getStrata_Minutes($building_info['id']);
            //$photos			= $this->buildingGateway->getPhotos($building_info['id']);
            //$condo_media	= $this->buildingGateway->getCondoMedia($building_info['id']);
            $condo_mediaMap				= $this->buildingGateway->getCondoMedia($building_info['id']);
			$building_info['siblings']	= $this->buildingGateway->getSiblings($building_info);

            if ($area == '') {
                $area = $building_info['city'];
            }

            $amenities                = $this->getAmenities($building_info);
            $features                = $this->getFeatures($building_info);
            $building_condo_info    = $this->getCondoInformation($building_info, $subarea, $area, $board_name);
            $restrictions            = $this->getRestrictions($building_info);
            $technical_info            = $this->getTechnicalInfo($building_info);
            $construction_info        = $this->getConstructionInfo($building_info);
            $maintenance            = $this->getMaintenance($building_info);
           // $floor_plans			= $this->buildingGateway->getFloorPlan($building_info);
            //$condo_suites            = $this->buildingGateway->getCondoSuites($building_info['id'], $building_info['strata_no'], $building_info['street_no'], $building_info['street_name']);
            
            $strata_plan_documents	= $this->buildingGateway->getDocuments($building_info['strataplan_wm']);//brochure_wm_scr
            $developers_brochure	= $this->buildingGateway->getDocuments($building_info['brochure_wm']);//brochure_wm
            $features_brochure_WM	= $this->buildingGateway->getDocuments($building_info['features_brochure_wm']);//features_brochure_wm
            $disclosure_amendment1	= $this->buildingGateway->getDocuments($building_info['disclosure_amendment1_wm']);//disclosure_amendment1_wm
            $disclosure_amendment2	= $this->buildingGateway->getDocuments($building_info['disclosure_amendment2_wm']);//disclosure_amendment2_wm
            $disclosure_amendment3	= $this->buildingGateway->getDocuments($building_info['disclosure_amendment3_wm']);//disclosure_amendment3_wm
            $builidngnewsarticleswm	= $this->buildingGateway->getDocuments($building_info['building_news_articles']);//building_news_articleswm
            $colliersmappdf			= $this->buildingGateway->getDocuments($building_info['colliersmap']);//colliersmap

			$documents =array("strata_plan_documents"=>$strata_plan_documents,"developers_brochure"=>$developers_brochure,"features_brochure_WM"=>$features_brochure_WM,"disclosure_amendment1"=>$disclosure_amendment1,"disclosure_amendment2"=>$disclosure_amendment2,"disclosure_amendment3"=>$disclosure_amendment3,"builidngnewsarticleswm"=>$builidngnewsarticleswm,"colliersmappdf"=>$colliersmappdf);

		
            $building_description	= ($building_info['description'])?$building_info['description']:null;
            $condo_suites			= $this->buildingGateway->getCondoSuites($building_info['id'],$building_info['strata_no'],$building_info['street_no'],$building_info['street_name']);


            // ADDED on/after : 29th-Mar-2021
            $more_from_bccnet=[
                'condo_id' => (int) $building_info['id'],
                'street_no' => (int) $building_info['street_no'],
                'bccnet_slug' => $building_info['slug'], // added: 2-12-2021
                'building_resources' => $this->buildingGateway->getBuildingResources($building_info['resources']),
                'bccnet_photos' => $this->buildingGateway->getBccNetPhotos($building_info['id']),
                'bccnet_maps_images' => $this->buildingGateway->getBccNetMapsImages($building_info['id']),
                'bccnet_pdfmaps' => $this->buildingGateway->getBccNetPdfmaps($building_info['id']),
                'bccnet_joined_media' => $this->buildingGateway->getBccNetJoinedMedia($building_info['id']),
                'maps_targets' => [$building_info['targeturl'],$building_info['targeturlstreetmap'],$building_info['targeturlgoogle']],
                'floor_plate' => $this->buildingGateway->getBccNetFloorPlate($building_info['id']),
                'complex_buildings' => $this->buildingGateway->getSiblings($building_info['id']),
                // 'bccnet_news' => $this->buildingGateway->getBccNetNews($building_info['id']),
                // 'news' => $this->buildingGateway->getBccNetNews($building_info['id']),
            ];

            /* Addition (29-09-2021) [STARTS]*/
            $related_companies = [ 
                'management' => $this->getBcnCompanyDetails($building_info['strata_mgmt_company']), 
                'marketer' => $this->getBcnCompanyDetails($building_info['marketer']), 
                'developer' => $this->getBcnCompanyDetails($building_info['developer']), 
                'designer' => $this->getBcnCompanyDetails($building_info['designer']), 
                'architect' => $this->getBcnCompanyDetails($building_info['architect']), 
            ];
            /* Addition (29-09-2021) [ENDS]*/


            // foreach($floor_plans as $key=>$val){
            //     $val['blobdata'] =base64_encode($val['fpblob']);
            //     unset($val['fpblob']);
            //     $floor_data[]=$val;
            // }
            
        }
        return $this->successResponse(
            ['data' =>
            ['building' =>
            [
                'id' => $this->identifierSuccessfull?:null,
                'building_condo_info' => $building_condo_info,
                'technical_info' => $technical_info,
                'amenities' => $amenities,
                'features' => $features,
                'condo_suites' => $condo_suites,
                'maintenance' => $maintenance,
                'restrictions' => $restrictions,
                'construction_info' => $construction_info,
                'building_description'	=> $building_description,
						'strata_plan_documents'	=> $strata_plan_documents,
                        'developers_brochure'	=> $developers_brochure,
                        'condo_mediaMap'		=> $condo_mediaMap,			
                        'building_documents'	=> $documents,
                        'strata_minutes'		=> $strata_minutes,

                'more_from_bccnet'=>$more_from_bccnet,
                'related_companies' => $related_companies, // [added:29-09-2021]

            ]]]
        );
    }

    /**
     * [getBuilding description]
     * @param  [type] $strataNo [description]
     * @return [type]           [description]
     */
    private function getAllBuildings($strataNo)
    {

        // if(!empty($this->streetNo)){
        //     $building_info = $this->buildingGateway->getBuildingObject($strataNo,$this->streetNo);
        // }else{
        //     $building_info = $this->buildingGateway->getBuildingObject($strataNo);
        // }
        $building_info = $this->buildingGateway->getBuildingObject($strataNo);

        $building_condo_info = array();
        $technical_info = array();
        $condo_suites = array();
        $amenities = array();
        $amenities_info = array();
        $features = array();
        $maintenance = array();
        $restrictions = array();
        $construction_info = array();
        $floor_plans = array();
        $condo_mediaMap=array();
        $strata_plan_documents=null;
        $developers_brochure=null;
        $features_brochure_WM=null;
        $disclosure_amendment1=null;
        $disclosure_amendment2=null;
        $disclosure_amendment3=null;
        $builidngnewsarticleswm=null;
        $colliersmappdf=null;

        if (count($building_info) <= 0) {
            return $this->successResponse(['data'=>[ 'buildings'=>[] ]]);
        }

        $retArray = [];
        foreach($building_info as $building_info){
            // $building_info = $building_info[0];
            //echo '<pre>';
            //print_r( $building_info);
            //echo '</pre>';
            $subarea        = ucwords($this->buildingGateway->getName('subareas', $building_info['subarea']));
            $area            = ucwords($this->buildingGateway->getName('areas', $building_info['area']));
            $board_name        = ucwords($this->buildingGateway->getName('boards', $building_info['board']));
            $strata_minutes    = ucwords($this->buildingGateway->getStrata_Minutes($building_info['id']));
            //$photos           = $this->buildingGateway->getPhotos($building_info['id']);
            //$condo_media  = $this->buildingGateway->getCondoMedia($building_info['id']);
            $condo_mediaMap             = $this->buildingGateway->getCondoMedia($building_info['id']);
            $building_info['siblings']  = $this->buildingGateway->getSiblings($building_info);

            if ($area == '') {
                $area = $building_info['city'];
            }

            $amenities                = $this->getAmenities($building_info);
            $features                = $this->getFeatures($building_info);
            $building_condo_info    = $this->getCondoInformation($building_info, $subarea, $area, $board_name);
            $restrictions            = $this->getRestrictions($building_info);
            $technical_info            = $this->getTechnicalInfo($building_info);
            $construction_info        = $this->getConstructionInfo($building_info);
            $maintenance            = $this->getMaintenance($building_info);
           // $floor_plans          = $this->buildingGateway->getFloorPlan($building_info);
            //$condo_suites            = $this->buildingGateway->getCondoSuites($building_info['id'], $building_info['strata_no'], $building_info['street_no'], $building_info['street_name']);
            
            $strata_plan_documents  = $this->buildingGateway->getDocuments($building_info['strataplan_wm']);//brochure_wm_scr
            $developers_brochure    = $this->buildingGateway->getDocuments($building_info['brochure_wm']);//brochure_wm
            $features_brochure_WM   = $this->buildingGateway->getDocuments($building_info['features_brochure_wm']);//features_brochure_wm
            $disclosure_amendment1  = $this->buildingGateway->getDocuments($building_info['disclosure_amendment1_wm']);//disclosure_amendment1_wm
            $disclosure_amendment2  = $this->buildingGateway->getDocuments($building_info['disclosure_amendment2_wm']);//disclosure_amendment2_wm
            $disclosure_amendment3  = $this->buildingGateway->getDocuments($building_info['disclosure_amendment3_wm']);//disclosure_amendment3_wm
            $builidngnewsarticleswm = $this->buildingGateway->getDocuments($building_info['building_news_articles']);//building_news_articleswm
            $colliersmappdf         = $this->buildingGateway->getDocuments($building_info['colliersmap']);//colliersmap

            $documents =array("strata_plan_documents"=>$strata_plan_documents,"developers_brochure"=>$developers_brochure,"features_brochure_WM"=>$features_brochure_WM,"disclosure_amendment1"=>$disclosure_amendment1,"disclosure_amendment2"=>$disclosure_amendment2,"disclosure_amendment3"=>$disclosure_amendment3,"builidngnewsarticleswm"=>$builidngnewsarticleswm,"colliersmappdf"=>$colliersmappdf);

        
            $building_description   = ($building_info['description'])?$building_info['description']:null;
            $condo_suites           = $this->buildingGateway->getCondoSuites($building_info['id'],$building_info['strata_no'],$building_info['street_no'],$building_info['street_name']);


            // ADDED on/after : 29th-Mar-2021
            // continue to avoid load..
            
            $more_from_bccnet='disabled-until-required';
            
            $more_from_bccnet=[
                'condo_id' => (int) $building_info['id'],
                'street_no' => (int) $building_info['street_no'],
                'building_resources' => $this->buildingGateway->getBuildingResources($building_info['resources']),
                'bccnet_photos' => $this->buildingGateway->getBccNetPhotos($building_info['id']),
                'bccnet_maps_images' => $this->buildingGateway->getBccNetMapsImages($building_info['id']),
                'bccnet_pdfmaps' => $this->buildingGateway->getBccNetPdfmaps($building_info['id']),
                'bccnet_news' => $this->buildingGateway->getBccNetNews($building_info['id']),
                'bccnet_joined_media' => $this->buildingGateway->getBccNetJoinedMedia($building_info['id']),
                'maps_targets' => [$building_info['targeturl'],$building_info['targeturlstreetmap'],$building_info['targeturlgoogle']],
            ];
            

            // foreach($floor_plans as $key=>$val){
            //     $val['blobdata'] =base64_encode($val['fpblob']);
            //     unset($val['fpblob']);
            //     $floor_data[]=$val;
            // }
            

            $retArray []= 
            [
                'id' => $strataNo,
                'building_condo_info' => $building_condo_info,
                'technical_info' => $technical_info,
                'amenities' => $amenities,
                'features' => $features,
                'condo_suites' => $condo_suites,
                'maintenance' => $maintenance,
                'restrictions' => $restrictions,
                'construction_info' => $construction_info,
                'building_description'  => $building_description,
                'strata_plan_documents' => $strata_plan_documents,
                'developers_brochure'   => $developers_brochure,
                'condo_mediaMap'        => $condo_mediaMap,         
                'building_documents'    => $documents,
                'strata_minutes'        => $strata_minutes,

                'more_from_bccnet'=>$more_from_bccnet,

            ];

        }

        return $this->successResponse(
            [
                'data' =>
                ['buildings' =>$retArray]
            ]
    );
    }

    /**
     * @param $building_info
     * @return array[]
     */
    private function getMaintenance($building_info)
    {
        $maint_fees_inc = array();
        if ($building_info['maint_fees_inc'] != '') {
            $maint_fees_inc = explode(',', $building_info['maint_fees_inc']);
        }
        return ['includes' => $maint_fees_inc];
    }
    
    /**
     * getBcnCompanyDetails gets id-based-data from `bccondos`.`company` table for `condos`-table fields like: strata_mgmt_company,marketer,architect,developer.. (Function updated:29-09-2021 [renamed from get_ManagementCompany])
     * @param  int      $strata_mgmt_company [description]
     * @return array
     */
    private function getBcnCompanyDetails($strata_mgmt_company)
    {
        //updated to return assoc-array with empty-string-values if-no-records-found (instead of null) [29-09-2021]
        // return $this->buildingGateway->get_strataManagement($strata_mgmt_company);
        $company = $this->buildingGateway->get_strataManagement($strata_mgmt_company);
        if(empty($company)){
            $company = ['name'=>'','phone'=>'','email'=>'','website'=>''];
        }
        return $company;
    }

    /**
     * @param $building_info
     * @return array
     */
    private function getConstructionInfo($building_info)
    {
        $construction_info = array(
            'year_built' => $building_info['yearbuilt'],
            'levels' => $building_info['levels'],
            'construction' => $building_info['construction'],
            'rain_screen' => $building_info['rain_screen'],
            'roof' => $building_info['roof'],
            'foundation' => $building_info['foundation'],
            'exterior_finish' => $building_info['exterior_finish'],
        );
        return $construction_info;
    }

    /**
     * [getTechnicalInfo (added-details-for: developer,architect [updated:29-09-2021])
     * @param  [type] $building_info [description]
     * @return [type]                [description]
     */
    private function getTechnicalInfo($building_info)
    {
        $developer        = $this->getBcnCompanyDetails($building_info['developer']);
        $architect        = $this->getBcnCompanyDetails($building_info['architect']);
        $developer_name    = '';
        $developer_email    = '';
        $developer_phone    = '';
        $architect_name    = '';
        $architect_email    = '';
        $architect_phone    = '';
        if (count($developer) > 0) {
            $developer_name    = $developer['name'];
            $developer_email    = $developer['email'];
            $developer_phone    = $developer['phone'];
        }
        if (count($architect) > 0) {
            $architect_name        = $architect['name'];
            $architect_email    = $architect['email'];
            $architect_phone    = $architect['phone'];
        }
        $technical_info = array(
            'floors'                => $building_info['levels'],
            'units_in_development'    => $building_info['units_in_development'],
            'units_in_strata'    => $building_info['units_in_strata'],
            'subcategories'    => $building_info['stratasubcategories'],
            'property_types'        => $building_info['title_to_land'],
            
            'developer_name'    => $developer_name,
            'developer_email'    => $developer_email,
            'developer_phone'    => $developer_phone,
            
            'architect_name'    => $architect_name,
            'architect_email'   => $architect_email,
            'architect_phone'   => $architect_phone,

        );
        return $technical_info;
    }

    /**
     * @param $building_info
     * @return array
     */
    private function getRestrictions($building_info)
    {
        $restrictions = array();
        if ($building_info['bylaw_restrictions'] != '') {
            $restrictions['info'] = explode(',', $building_info['bylaw_restrictions']);
        }
        $restrictions['pets'] = [
            'no_pets' => $building_info['no_pets'],
            'dogs' => $building_info['dogs'],
            'cats' => $building_info['cats'],
        ];
        $restrictions['other'] = [
            'restrictions_updated_month' => $building_info['restrictions_updated_month'],
            'restrictions_updated_year' => $building_info['restrictions_updated_year'],
        ];
        return $restrictions;
    }

    /**
     * @param $building_info
     * @param $subarea
     * @param $area
     * @param $board_name
     * @return array
     */
    /**
     * getCondoInformation format and fetch-joined information generally based on `bccondos`.`condos` table (Function-doc updated:29-09-2021)
     * @param  array $building_info [description]
     * @param  string $subarea       [description]
     * @param  string $area          [description]
     * @param  string $board_name    [description]
     * @return array                [description]
     */
    private function getCondoInformation($building_info, $subarea, $area, $board_name)
    {
        $address = str_replace(array('  ', '  '), ' ', $building_info['street_no'] . ' ' . $this->getStreetDir($building_info['street_dir']) . ' ' . $building_info['street_name'] . ' ' . $building_info['street_type']);
        $company        = $this->getBcnCompanyDetails($building_info['strata_mgmt_company']);
        $management_company    = '';
        $management_company_phone    = '';
        if (count($company) > 0) {
            $management_company            = $company['name'];
            $management_company_phone    = $company['phone'];
        }
        $building_condo_info = array(
            'name'            => $building_info['name'],
            'description' => $building_info['description'],
            'description_2' => $building_info['description_671new'],
            'address'        => ucwords(strtolower($address)),
            'city'            => $building_info['city'],
            'postal_code'    => $building_info['postalcode'],
            'levels'        => $building_info['levels'],
            'suites'        => $building_info['units_in_strata'],
            'status'        => $building_info['status'],
            'built'            => $building_info['yearbuilt'],
            'title_to_land'    => $building_info['title_to_land'],
            'building_type'    => $building_info['stratasubcategories'],
            'strata_plan'    => $building_info['strata_no'],
            'subarea'        => $subarea,
            'area'            => $area,
            'board_name'    => $board_name,
            'management_company'        => $management_company,
            'management_company_phone'    => $management_company_phone,
            'management_companycontactpersonname'    => $building_info['managementcompanycontactpersonname'],
            'management_companycontactpersonemail'    => $building_info['managementcompanycontactpersonemail'],
            'management_companycontactpersonofficenumber'    => $building_info['managementcompanycontactpersonofficenumber'],
            'on_site_manager'        => $building_info['concierge_manager_name'],
            'on_site_manager_phone'    => $building_info['onsite_manager_phone'],
            'on_site_manager_email'    => $building_info['onsite_manager_email'],
            'concierge_name'        => $building_info['concierge_name'],
            'concierge_email'        => $building_info['concierge_email'],
            'concierge_phone'        => $building_info['concierge_phone'],
            
            // 'Bldg'                    => $building_info['id'], // disabled: 29-09-2021

            /* Adding-meta-info (16-09-2021) [STARTS] */
            'meta_tag'        => $building_info['meta_tag'],
            'meta_tag_title'        => $building_info['meta_tag_title'],
            'meta_tag_keywords'        => $building_info['meta_tag_keywords'],
            /* Adding-meta-info (16-09-2021) [ENDS] */

            /* Adding-more-info (29-09-2021) [STARTS] */
            /*For-official-site and related info [STARTS]*/
            'strata_info_html' => $building_info['strata_info'],
            'amenities_info_html' => $building_info['amenities_info'],
            'sales_url' => $building_info['sales_url'], 
            'sales_phone' => $building_info['sales_phone'],
            'sales_email' => $building_info['sales_email'],
            'sales_address' => $building_info['sales_address'],
            /*For-official-site and related info [ENDS]*/

            'other_name'        => $building_info['other_name'],
            'other_email'        => $building_info['other_email'],
            'other_phone'        => $building_info['other_phone'],
            'contingency_fund' => $building_info['contingency_fund'],
            'contingency_fund_month' => $building_info['contingency_fund_month'],
            'contingency_fund_year' => $building_info['contingency_fund_year'],
            'bldg'                    => $building_info['id'],
            'video_link'            => $building_info['building_intro_video_url'],

            /*Testing-related_companies starts*/
            /*
            'related_companies' => [ 
                'management' => $this->getBcnCompanyDetails($building_info['strata_mgmt_company']), 
                'marketer' => $this->getBcnCompanyDetails($building_info['marketer']), 
                'developer' => $this->getBcnCompanyDetails($building_info['developer']), 
                'designer' => $this->getBcnCompanyDetails($building_info['designer']), 
                'architect' => $this->getBcnCompanyDetails($building_info['architect']), 
            ],*/
            /*Testing-related_companies ends*/

            /* Adding-more-info (29-09-2021) [ENDS] */

        );
        return $building_condo_info;
    }

    /**
     * @param $strataNo
     * @return string[]
     */
    private function getAmenities($building_info)
    {
        $amenities = array();
        $amenities_info = array();
        if ($building_info['amenities'] != '') {
            $amenities = explode(',', $building_info['amenities']);
        }
        if ($building_info['amenities_info'] != '') {
            $amenities_info = $this->buildingGateway->remove_ultags($building_info['amenities_info']);
        }
        $count = 0;
        foreach($amenities as $amenity){
            $amenities[$count] = ucwords($amenity);
            $count++;
        }
        $amenities = ['data' => $amenities, 'info' => $amenities_info];
        return $amenities;
    }

    /**
     * @param $building_info
     * @return array|string[]
     */
    private function getFeatures($building_info)
    {
        $features = array();
        if ($building_info['features'] != '') {
            $features = $this->buildingGateway->remove_ultags($building_info['features']);
        }
        return $features;
    }
    
    //     private function getFloorplan($strataNo){
    //     $building_info =$this->buildingGateway->getBuildingObject($strataNo,'floorplans');
    //     $building_condo_info=array();
    //     $floor_data=array();
    //     $floor_plans=array();
    //     if(count($building_info)>0){
    //         $building_info=$building_info[0];
    //         $subarea					= $this->buildingGateway->getName('subareas', $building_info['subarea']);
    //         $area						= $this->buildingGateway->getName('areas', $building_info['area']);
    //         $board_name					= $this->buildingGateway->getName('boards', $building_info['board']);
    //         $building_condo_info	= $this->getCondoInformation($building_info, $subarea, $area, $board_name);
    //         $floor_plans			= $this->buildingGateway->getFloorPlanBlob($building_info);
    //         foreach($floor_plans as $key=>$val){
    //             //$val['blobdata'] =base64_encode($val['fpblob']);
    //             unset($val['fpblob']);
    //             $floor_data[]=$val;
    //         }
    //     }
    //     return $this->successResponse(
    //         ['data' =>
    //             ['building' =>
    //                 [
    //                     'id'=>$strataNo,
    //                     'building_condo_info'	=> $building_condo_info,
    //                     'floor_plans'	=>$floor_data
    //                 ]
    //             ]
    //         ]
    //     );
    // }
    
    private function getFloorplan($strataNo){       
        // $building_info =$this->buildingGateway->getBuildingObject($strataNo,'floorplans');		
        $building_info =$this->buildingGateway->getBuildingObject($strataNo);//,'floorplans');-- bcoz 2nd ARG was NEVER used before, Now its 'StreetNo'      
		$building_condo_info=array();
		$floorplate_data=array();
		$floor_data=array();
		$floor_plans=array();
		$floor_plate=array();
		$floor_plates=array();
		$floor_platesdata=array();
		if(count($building_info)>0){
			$building_info=$building_info[0];
			$subarea					= $this->buildingGateway->getName('subareas', $building_info['subarea']);
			$area						= $this->buildingGateway->getName('areas', $building_info['area']);
			$board_name					= $this->buildingGateway->getName('boards', $building_info['board']);
			$building_condo_info		= $this->getCondoInformation($building_info, $subarea, $area, $board_name);
			$floor_plans				= $this->buildingGateway->getFloorPlan($building_info);
			$floor_plate				= $this->buildingGateway->getFloorPlate($building_info);
			$slugvfp ='';
			foreach($floor_plans as $key=>$val){	
				$slugvfp =$val['slugvfp'];
				$val['blobdata'] ="";//base64_encode($val['fpblob']);
				unset($val['fpblob']);
				if($val['imgname']=='nofloorplan.jpg'){
					$val['floorplanimages']=null;
				}
				unset($val['slugvfp']);
				unset($val['imgname']);
				$floor_data[]=$val;
			}
			foreach($floor_plate as $keyss=>$row){
				if ($row['floors'] != '') {
					$floorparts = explode(',', $row['floors']);
					for($i=0; $i<sizeof($floorparts); $i++) {
						if (!in_array($floorparts[$i], $floorplate_data)) {
							$floor_plates[]       = $this->buildingGateway->getFloorPlateData($floorparts[$i],$building_info,$slugvfp);
							$floorplate_data[]	 = array($floorparts[$i]);
						}
					}
				}
			}
			foreach($floor_plates as $keys=>$vals){
				foreach($vals as $key1s=>$val1s){
					if($val1s['name']=='nofloorplan.jpg'){
						$val1s['floorplateurl']=null;
					}
					unset($val1s['name']);
					$floor_platesdata[]=$val1s;
				}
			}
		}
        return $this->successResponse(
            ['data' =>
                ['building' =>
                    [
                        'id'=>$strataNo,
						'building_condo_info'	=> $building_condo_info,
                        'floor_plans'	=>$floor_data,
                        'floor_plates'	=>$floor_platesdata                     
                    ]
                ]
            ]
        );
    }


    /**
     * @param $result
     * @return mixed
     */
    private function successResponse($result)
    {
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    /**
     * @return mixed
     */
    private function unprocessableEntityResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => 'Invalid input'
        ]);
        return $response;
    }
    

    /**
     * @return mixed
     */
    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = "Invalid Request";
        return $response;
    }

    /**
     * @param $street
     * @return string
     */
    private function getStreetDir($street)
    {
        switch ($street) {
            case 'NORTH':
                return 'N';
                break;
            case 'SOUTH':
                return 'S';
                break;
            case 'EAST':
                return 'E';
                break;
            case 'WEST':
                return 'W';
                break;
            case 'SOUTHWEST':
                return 'SW';
                break;
            case 'SOUTHEAST':
                return 'SE';
                break;
            case 'NORTHWEST':
                return 'NW';
                break;
            case 'NORTHEAST':
                return 'NE';
                break;
            default:
                return '';
        }
    }
}
