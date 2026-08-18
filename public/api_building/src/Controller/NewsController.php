<?php

namespace Src\Controller;

use Src\TableGateways\BuildingGateway;

class NewsController
{

    private $db;
    private $dbMLS;
    private $dbLES;
    private $requestMethod;
    private $strataNo;
    private $task;

    private $page;
    private $streetNo;

    private $buildingGateway;

    public function __construct($db, $dbMLS, $dbLES, $requestMethod='GET', $page=0)
    {
        $this->db = $db;
        $this->dbMLS = $dbMLS;
        $this->dbLES = $dbLES;
        $this->requestMethod = $requestMethod;
        // $this->strataNo = $strataNo;
        $this->task = 'getnews';

        $this->page = $page;
        // $this->request = $request;

        $this->buildingGateway = new BuildingGateway($db, $dbMLS, $dbLES);
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                // $retAry = [ 'data'=>'test' ];
                $retAry = [ 'data'=>['news'=>$this->getBccNetNews()] ];
                $response = $this->successResponse($retAry);
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
     * [getSqlFromBccNet description]
     * @param  [type] $sql [the SQL-prepared-statement]
     * @return [type]      [description]
     */
    public function getSqlFromBccNet($sql){
        $retAry = [];
        try {
            $statement = $this->db->prepare($sql);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result; // if(empty($result))return $sql;
        } catch (\PDOException $e) {
            exit($e->getMessage());
            return $e->getMessage();
        }
        return $retAry;
    }


    /**
     * [getBccNetNews description]
     * @param  integer $id     [description]
     * @param  integer $offset [description]
     * @param  integer $limit  [description]
     * @return array          [array of news-objects]/
     */
    public function getBccNetNews($page=0, $pageSize=10,$mode='', $moreOps=['from'=>'','to'=>'','since'=>'','source'=>'','newstitle'=>'']){
        //////////////////////////////////////////////////////////////////////////////////////////////////
        //$_GET[...] -configs BEGIN--
        //Using direct-$_GET[...] *temprorarily here, untill moved to Route:api..../api/path/news/..... //
        //////////////////////////////////////////////////////////////////////////////////////////////////
        if(empty($page) && !empty($_GET['page']) ){
            $page = (int) trim($_GET['page']);
        }
        foreach(array_merge($moreOps,array_fill_keys(['mode','page','year','month','categoryid','category'],'')) as $var=>$val ){
            if(!empty($_GET[$var]) ){
                $$var = $_GET[$var];
            } 
        }
        if(!empty($year) && !empty($month)){
        	$from ="$year-$month-01";
        	$to   ="$year-$month-31";
        }
        //////////////////////////////
        //$_GET[...] -configs END-- //
        //////////////////////////////

        
        $limit = ($pageSize>0) ? (int) $pageSize : 10;
        $offset = 0;

        if($page>1){
            $offset = ($page-1) * $limit; 
        }
        
        $tomorrow = mktime(0, 0, 0, date("m") - 2, date("d"), date("Y"));        
        $lastmonthdate = date("Y/m/d", $tomorrow);

        $dbNewsTable = '`bccondos`.`news`';
        if(in_array( $mode, ['victorianews','victoria']) ){
            $dbNewsTable = '`bccondos`.`victorianew`';
        }else if($mode=='mandarinnews'){
        	$dbNewsTable = '`bccondos`.`mandarinnews`';
        }
        
        if(empty($since)){
            $since = (!empty($from))?$from:$lastmonthdate;
        }

        if(!empty($mode) && $mode=='blogpostnews'){

            $whereClause = " WHERE `post_date`>='$since' ";

            if(!empty($to)){
                $whereClause .= " AND `post_date`<='$to' ";
            }
            if(!empty($newstitle)){
                // $newstitle = str_replace('-','_%',$newstitle);
                $whereClause .= " AND `post_title` LIKE '%".$newstitle."%' ";
            }
            if(!empty($categoryid)){
                $whereClause .= " AND `term_taxonomy_id`='$categoryid' ";
            }
            if(!empty($category)){
                $whereClause .= " AND `term_name`='$category' ";
            }

        }else{

            $whereClause = " WHERE `publishdate`>='$since' ";

            if(!empty($to)){
                $whereClause .= " AND `publishdate`<='$to' ";
            }

            if(!empty($source)){
                $whereClause .= " AND `source`='$source' ";
            }
            if(!empty($newstitle)){
                // $newstitle = str_replace('-','_%',$newstitle);
                $whereClause .= " AND `articletitle` LIKE '%".$newstitle."%' ";
            }
        }


        $sql = "SELECT `articlesurl`,`articlesname`,`articletitle`,`articledec`,`datearticles_d`,`datearticles_m`,`datearticles`,`source`, `n`.*  FROM $dbNewsTable `n` $whereClause ORDER BY `publishdate` DESC LIMIT $offset, $limit";

        $blogpostnewsSql = "
        SELECT  lt.`name` AS `term_name`,`display_name` AS `article_author`, DATE(`post_date`) AS `publishdate`, `post_date_gmt` AS `publishdate_gmt`, `post_excerpt` AS `articledec`, `post_title` AS `articletitle`, `guid` AS `articlesurl`,

        /* `post_content` AS `articledec`, */ 
        r.object_id,r.term_taxonomy_id, t.post_author,u.display_name,u.*, t.* 
        FROM `les_posts` `t` 
        RIGHT JOIN `les_users` `u` ON u.ID=t.post_author
        RIGHT JOIN `les_term_relationships` `r` ON r.object_id=t.ID
        RIGHT JOIN `les_terms` `lt` ON lt.term_id = r.term_taxonomy_id
        $whereClause
        /* GROUP BY t.post_author, r.term_taxonomy_id  */
        ORDER BY t.post_date DESC  LIMIT $offset, $limit;
        ";

        if($mode =='blogpostnews'){
            $sql = $blogpostnewsSql;
        }
        
        $res = $this->getSqlFromBccNet($sql);

        return $res; //$res?:$sql;

    }


    /**
     * [getBccNetVictoriaNews description]
     * @param  integer $page     [description]
     * @param  integer $pageSize [Set to -1, so to retain the "changes of default values" from 'getBccNetNews()']
     * @return mixed            [description]
     */    
    public function getBccNetVictoriaNews($page=0, $pageSize=-1){
        return $this->getBccNetNews($page, $pageSize, 'victorianews');        
    }

    public function getBccNetNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops){
        $ops['from'] ="$year-$month-01";
        $ops['to']   ="$year-$month-31";
        return $this->getBccNetNews($page,$pageSize,'', $ops);
    }

    public function getBccNetVictoriaNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops){
        $ops['from'] ="$year-$month-01";
        $ops['to']   ="$year-$month-31";
        return $this->getBccNetNews($page,$pageSize,'victorianews', $ops);
    }

    public function getBccNetMandarinNews($page=0, $pageSize=-1){
        return $this->getBccNetNews($page, $pageSize, 'mandarinnews');        
    }

    public function getBccNetMandarinNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops){
        $ops['from'] ="$year-$month-01";
        $ops['to']   ="$year-$month-31";
        return $this->getBccNetNews($page,$pageSize,'mandarinnews', $ops);
    }





}
