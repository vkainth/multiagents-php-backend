<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Buildings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repository\ActivityRepository;


class NewsarticleController extends Controller
{

	protected $connection_360 = 'mysql_pixi360';
	protected $db;
	
	public function __construct(){
		// $app->useEnvironmentPath(
		// 	dirname(__DIR__.'/../../../../api_building/', 2)
		// );
		
		// $this->db = new PDO('mysql:host=BCNFORNEWS_DB_HOST;dbname=BCNFORNEWS_DB_DATABASE','BCNFORNEWS_DB_USERNAME','BCNFORNEWS_DB_PASSWORD');
		$this->db = mysqli_connect(env('BCNFORNEWS_DB_HOST'),env('BCNFORNEWS_DB_USERNAME'),env('BCNFORNEWS_DB_PASSWORD'),env('BCNFORNEWS_DB_DATABASE'));
		try {
			$this->db->set_charset("utf8mb4"); 
		} catch (Exception $e) {}
	}

	public function newsblog()
	{
	/*
		if(\Request::is('blog*')){
			return view('frontend.newsblog',['newsmode'=>'blogpostnews']);
		}elseif(\Request::is('news-blog*')){
			return view('frontend.newsblog',['newsmode'=>'news-blog','newsarticles'=>$this->getBccNetNews()]);
		}elseif(\Request::is('news-victoria*')){
			return view('frontend.newsblog',['newsmode'=>'news-victoria','newsarticles'=>$this->getBccNetNews()]);
		}elseif(\Request::is('news-mandarin*')){
			return view('frontend.newsblog',['newsmode'=>'news-mandarin','newsarticles'=>$this->getBccNetNews()]);
		}elseif(\Request::is('news') || \Request::is('news/*')){
			return view('frontend.newsblog',['newsmode'=>'news','newsarticles'=>$this->getBccNetNews()]);
		}
	*/
	}

	public function monthsArray(){
		return ['January','February','March','April','May','June','July','August','September','October','November','December'];
	}
	public function yearsArray(){
		$years = [];
		for($y = date('y'); $y>=2012; $y--){
			$years[]=$y;
		}
		return $years;
	}

	public function getNewsCategoriesArray(){
		$categoriesArray = [
			['name'=>'Uncategorized','catid'=>0],
			// ['name'=>'Other News Articles','catid'=>0],
			['name'=>'Blogrol','catid'=>2],
			['name'=>'Contributors','catid'=>3],
			['name'=>'Real Estate Related','catid'=>4],
			['name'=>'Blog Menu','catid'=>5],
			['name'=>'Strata Information','catid'=>6],
			['name'=>'Other News Articles','catid'=>7],
			['name'=>'Real Estate Legal Articles','catid'=>8],
			['name'=>'Technology Related Articles','catid'=>9],
			['name'=>'Commercial Real Estate Articles','catid'=>10],
		];

		/* COULD BE Dynamically fetched array of Categories-from-blog-table--grouped, but static for efficiency, as they don't change much*/

		foreach($categoriesArray as &$_cat){
			$_cat['catslug'] = str_replace(' ', '-', strtolower(trim($_cat['name']) ) );
		}

		return $categoriesArray;
	}

	/**
	 * [customUrlArgs -- function for testing, simply shows webpage with url-parameters ]
	 * @param  [type] $s1 [description]
	 * @param  [type] $s2 [description]
	 * @param  [type] $s3 [description]
	 * @return void     [prints arguments]
	 */
	public function customUrlArgs($s1,$s2,$s3){
		echo 'customUrlArgs: ';
		print_r( func_get_args() );
	}

	public function showNewsApi( $newsmode='general', $moreOps=[]){
		$moreOps['apimode']=true;
		return $this->showNews($newsmode,$moreOps);
	}

	public function showNewsVictoria( $moreOps=[]){
		return $this->showNews('victoria',$moreOps);
	}
	public function showNewsMandarin( $moreOps=[]){
		return $this->showNews('mandarin',$moreOps);
	}

	public function showNews( $newsmode='general', $moreOps=[]){

		$newsViewParams = $this->getNewsReadyArray($newsmode, $moreOps);

		if(Auth::user() && (!empty($newsViewParams['apimode']) || !empty(request()->input('apimode')) ) /*|| !empty(request()->input('sitemap'))*/ ){
			foreach($newsViewParams['newsarticles'] as &$_newsArticle) {
				$_newsArticle['post_content'] = 'trimmed-for-comfort';
				if(!empty($_newsArticle['post_name'])){
					$_newsArticle['route_url_for_post'] = route('news-blog-post_name',['post_name'=>$_newsArticle['post_name']?:'#' ]);
				}
			}
			/*if(!empty(request()->input('sitemap'))){
				return $newsViewParams;
			}*/
			@header('Content-Type: application/json');
			print_r(json_encode($newsViewParams,JSON_PRETTY_PRINT));
			return;
		}

		return view('frontend.newsblog',$newsViewParams);

		// return view('frontend.newsblog',['newsmode'=>$newsViewParams['newsmode'],'newsarticles'=>$newsViewParams['newsarticles']]);

	}

	public function getNewsReadyArray( $newsmode='general',$moreOps=[]){
		$newsViewParams = [];
		$newsViewParams['newsmode'] = '';
		// $newsViewParams['newsmode'] = ($newsmode=='')?'':($newsmode=='blog')?'blogpostnews':($newsmode=='victoria')?'victorianews':($newsmode=='mandarin')?'mandarinnews':'' ;

		// $newsViewParams['newsarticles'] = $this->getBccNetNews(null,null,$newsViewParams['newsmode']);

		// if($newsmode=='blog'){
		// 	$newsViewParams['newsmode']='blogpostnews';
		// }else
		if($newsmode=='blog'){
			$newsViewParams['newsmode']='blog';
		}elseif($newsmode=='victoria'){
			$newsViewParams['newsmode']='victoria';
		}elseif($newsmode=='mandarin'){
			$newsViewParams['newsmode']='mandarin';
		}else{ // }elseif($newsmode=='int' || $newsmode=='general' || $newsmode==''){
			$newsViewParams['newsmode']='general';
		}

		// $this->mode=$newsViewParams['newsmode']; // Creation of dynamic property $mode is deprecated!

		$newsViewParams['mode'] = $newsViewParams['newsmode'];

		$newsViewParams = array_merge($moreOps,$newsViewParams);
		$newsViewParams['newsarticles'] = $this->getBccNetNews($newsViewParams);

		return $newsViewParams;

	}

	public function showNewsBlog( $blogOps = []){
		if(is_array($blogOps)){
			$blogOps = array_merge($blogOps,['mode'=>'blog']);
		}else{
			$params = request()->route()->parameters();
			$blogOps = (array) $params;// compact('params');
		}

		$blogOps = array_merge($blogOps,['mode'=>'blog']);

		$blogOps['passedData']=$blogOps;
		return $this->showNews('blog',$blogOps);

	}

	public function showNewsBlogArchive( $year=0,$month=0,$page=0,$ops=[]){

		$ops['from'] = (($year>0)?$year:'1900')."-".(($month>0)?$month:'01')."-01";
		$ops['to'] = (($year>0)?$year: date('y') )."-".(($month>0)?$month:'12')."-31";
		$ops = array_merge($ops, ['year'=>$year, 'month'=>$month,'page'=>$page]);

		return $this->showNewsBlog($ops);
	}

	public function showSiteMap(){
        $request = request();

		$siteMapData = ['message'=>'under-construction'];

		$newsModesArray = ['blog','victoria','mandarin','general'];

		if(!empty(request()->route('newsmode') ) || !empty(request()->input('newsmode') ) ){
			$newsModesArray = [];
			$newsModesArray[]= request()->route('newsmode')?:request()->input('newsmode')?:'blog';

		}
		
		// $siteMapData = [];
		// foreach($newsModesArray as $_newsMode){
		// 	$newsViewParams = $this->getNewsReadyArray($_newsMode,['pageSize'=>99999999,'year'=>0,'month'=>0]);
		// 	foreach($newsViewParams['newsarticles'] as &$_newsArticle) {
		// 		$_newsArticle['post_content'] = 'trimmed-for-comfort';
		// 		if(!empty($_newsArticle['post_name'])){
		// 			$_newsArticle['route_url_for_post'] = route('news-blog-post_name',['post_name'=>$_newsArticle['post_name']?:'#' ]);
		// 		}else{
		// 			$_newsArticle['route_url_for_post'] = $_newsArticle['articlesurl'] ?? '#!0';
		// 		}
		// 	}
			
		// 	$siteMapData[$_newsMode]= $newsViewParams; 
		// }

		$response = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

		if( \Route::currentRouteName() == 'news-sitemap' ){

			$response .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
			foreach($newsModesArray as $_newsMode){
				$response .= "\n".'<sitemap><loc>'. route('news-mode-sitemap-xml',['newsmode'=>$_newsMode]) .'</loc></sitemap>';
			}
			$response .= "\n".'</sitemapindex>';

		}elseif( \Route::currentRouteName() == 'news-mode-sitemap-xml' ){

			$_newsMode = $request->route('newsmode');
			$newsViewParams = $this->getNewsReadyArray($_newsMode,['pageSize'=>99999999,'year'=>0,'month'=>0]);

			if( $_newsMode == 'blog'){

				foreach($newsViewParams['newsarticles'] as &$_newsArticle) {
					$_newsArticle['post_content'] = 'trimmed-for-comfort';
					if(!empty($_newsArticle['post_name'])){
						$_newsArticle['route_url_for_post'] = route('news-blog-post_name',['post_name'=>$_newsArticle['post_name']?:'#' ]);
					}else{
						$_newsArticle['route_url_for_post'] = $_newsArticle['articlesurl'] ?? '#!0';
					}
				}

				$response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
				foreach($newsViewParams['newsarticles'] as &$_newsArticle) {
					if($_newsArticle['route_url_for_post'][0]??'#' != '#' ){
						$response .= "\n".'<url><loc><![CDATA['. htmlentities( $_newsArticle['route_url_for_post']) .']]></loc></url>';
						// .'<lastmod>2018-06-04</lastmod>'
					}
				}
				$response .= "\n".'</urlset>';

			}elseif($_newsMode){

				$response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

				$_validNewsArticles = array_filter( $newsViewParams['newsarticles'], function($v){return !empty($v['articletitle']); });
				$_totalCount=count($_validNewsArticles);
				$_pageSize = 10;
				$_totalPages = ceil($_totalCount/$_pageSize);

				for($i=1; $i<=$_totalPages; $i++){
					$response .= "\n".'<url><loc><![CDATA['. route('news-list-mode',['newsmode'=>$_newsMode,'page'=>$i]) .']]></loc></url>';
				}

				// foreach($newsViewParams['newsarticles'] as $_newsArticle) {
				// 	if($_newsArticle['route_url_for_post'][0]??'#' != '#' ){
				// 		$response .= "\n".'<url><loc><![CDATA['. htmlentities( $_newsArticle['route_url_for_post']) .']]></loc></url>';
				// 	}
				// }
				
				$response .= "\n".'</urlset>';
			}

		}else{
			@header('Content-Type: text/html');
			abort(404);
		}

		return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
	}

	public function processRequest()
	{
		switch ($this->requestMethod) {
			case 'GET':
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
			// $statement = $this->db->prepare($sql);
			// $statement->execute();
			// $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			// $statement->execute();
			
			$res = $this->db->query($sql);
			$result = $res->fetch_all(MYSQLI_ASSOC);
			return $result;

		} catch (\Exception $e) {
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
	public function getBccNetNews($page=0,$pageSize=10,$mode='',$moreOps=['page'=>0, 'pagesize'=>10,'mode'=>'','blogid'=>'','from'=>'','to'=>'','since'=>'','source'=>'','newstitle'=>'']){

		$request = request();
		$request->validate([
			'newstitle' =>'min:3|regex:/(^([a-zA-z0-9\s]+)(\d+)?$)/u',
			'page' => 'nullable|integer|min:1',
		],[
			'page' => 'Invalid Page number',
			// 'newstitle' => 'Please enter valid Search-Term!',
		]);

		if(is_array($page)){
			// $moreOps = array_merge($page,$moreOps);
			$moreOps = array_merge($moreOps,$page);
			$page = (int) $moreOps['page'];
		}
		extract($moreOps);

		////////////////////////////////////////////////////////////////////////////
		// request()->parameter-for-query-setup[...] -configs [STARTS]--
		////////////////////////////////////////////////////////////////////////////
		if(empty($page) && !empty(request()->input('page')) ){
			$page = (int) trim(strip_tags(request()->input('page')));
		}
		foreach(array_merge($moreOps,array_fill_keys(['mode','page','year','month','categoryid','category','pageSize'],'')) as $var=>$val ){
			if(!empty(request()->input($var)) ){
				// $$var = $_GET[$var];
				$$var = strip_tags(request()->input($var));
			} 
		}
		if(!empty($year) && !empty($month)){
			$from ="$year-$month-01";
			$to   ="$year-$month-31";
		}
		////////////////////////////////////////////////////////////////////////////
		// request()->parameter-for-query-setup[...] -configs [ENDS]-- //
		////////////////////////////////////////////////////////////////////////////


		$limit = (int) ( ($pageSize>0) ? $pageSize : 10);
		$offset = 0;

		if($page>1){
			$offset = ($page-1) * $limit; 
		}
		
		$tomorrow = mktime(0, 0, 0, date("m") - 2, date("d"), date("Y"));        
		$lastmonthdate = date("Y/m/d", $tomorrow);
		$lastmonthdate = '2012/01/01';

		if(!empty($moreOps['mode'])){
			$mode = $moreOps['mode'];
		}

		$dbNewsTable = '`bccondos`.`news`';
		
		if(in_array( $mode, ['victorianews','victoria']) ){
			$dbNewsTable = '`bccondos`.`victorianew`';
		}else if(in_array($mode,['mandarinnews','mandarin'])){
			$dbNewsTable = '`bccondos`.`mandarinnews`';
		}else if(in_array($mode,['news','general'])){
			$dbNewsTable = '`bccondos`.`news`';
		}
		
		if(empty($since)){
			$since = (!empty($from))?$from:$lastmonthdate;
		}

		if(!empty($mode) && in_array($mode,['blogpostnews','blog'])){

			$whereClause = " WHERE `post_date`>='$since' ";

			if(!empty($blogid)){
				$whereClause .= " AND `id`='$blogid' ";
				// continue; 
			}
			if(!empty($post_name)){
				$whereClause .= " AND `post_name`='$post_name' ";
				// continue; 
			}

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

		if(in_array($mode,['blogpostnews','blog'])){
			$sql = $blogpostnewsSql;
		}

		$res = $this->getSqlFromBccNet($sql);

		// if(empty($mode)){$res = [];}
		
		return $res;

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

	public function getBccNetNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops=[]){
		$ops['from'] ="$year-$month-01";
		$ops['to']   ="$year-$month-31";
		return $this->getBccNetNews($page,$pageSize,'', $ops);
	}

	public function getBccNetVictoriaNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops=[]){
		$ops['from'] ="$year-$month-01";
		$ops['to']   ="$year-$month-31";
		return $this->getBccNetNews($page,$pageSize,'victorianews', $ops);
	}

	public function getBccNetMandarinNews($page=0, $pageSize=-1){
		return $this->getBccNetNews($page, $pageSize, 'mandarinnews');        
	}

	public function getBccNetMandarinNewsForYearMonth($year,$month,$page=0,$pageSize=-1,$ops=[]){
		$ops['from'] ="$year-$month-01";
		$ops['to']   ="$year-$month-31";
		return $this->getBccNetNews($page,$pageSize,'mandarinnews', $ops);
	}

	public function redirectToShowNews($newsmode='general'){
		return redirect()->route('news-list-mode', ['newsmode' => $newsmode]);
	}
}

















// ---------- Remove code below this ----------------------------------
// [Removed on:07-10-2021]
