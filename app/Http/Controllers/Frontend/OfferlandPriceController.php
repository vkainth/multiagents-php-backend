<?php

namespace App\Http\Controllers\Frontend;

use App\Models\OfferlandPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Log;

class OfferlandPriceController extends Controller
{
	
	// protected $connection = 'mysql';

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function store()
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\OfferlandPrice  $offerlandPrice
	 * @return \Illuminate\Http\Response
	 */
	public function show(OfferlandPrice $offerlandPrice)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\OfferlandPrice  $offerlandPrice
	 * @return \Illuminate\Http\Response
	 */
	public function edit(OfferlandPrice $offerlandPrice)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \App\Models\OfferlandPrice  $offerlandPrice
	 * @return \Illuminate\Http\Response
	 */
	public function update(OfferlandPrice $offerlandPrice)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\OfferlandPrice  $offerlandPrice
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(OfferlandPrice $offerlandPrice)
	{
		//
	}











	/**
	 * [getCsvData description]
	 * @param  [type] $fileName [description]
	 * @param  [type] $format   [description]
	 * @return [type]           [description]
	 */
	public function getCsvData($fileName=null,$format=null){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://public-s3.offerland.ca/offer-share/67082254433814534046.csv',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'X-API-KEY: 6zjp8PXpKW4CNgPNYw9UW8x4JbqMbyZm6JhjyFPA'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		// $err = curl_error($curl);
		// return $err?:$response;
		
		$response = explode("\n", trim($response));
		$response = array_map('str_getcsv',  $response);
		$csv_headers = array_shift($response);

		$csv_headers[0] = $csv_headers[0]?:"csvSNo"; // First-Field is blank-(refering to)-serial-Number
		foreach($csv_headers as &$_header){
			$_header = Str::snake($_header); 
		}

		// $response = array_map(function($data) use ($csv_headers) {return array_combine($csv_headers,$data);},$response);
		// return [ 'header'=>$csv_headers , 'data'=> array_chunk($response,10)[0]];
		

		// $csv_data_chunks = array_chunk($response, 10);
		
		$readyResponse = array_map(function($data) use ($csv_headers) {
			if($data[0]==null){return ;}
			$_ary= array_combine($csv_headers,$data); 
			unset($_ary['csv_s_no']);
				// $_ary['ml_no'] = $_ary['ml_no'].'test';
			$_ary['estimated_date_time'] = date('Y-m-d H:i:s',$_ary['estimated_date_time']);
			return $_ary;
		},$response);
		//     },$csv_data_chunk);

		if(request()->input('create','')=='vk3489hdf7834aw678haklhsf'){

			$inserted = 0;
			$failed = 0;
			/*
			// Alternative-approach --without-chunks, if there is duplicacy on(ml_no, estimatedDateTime) in-only-few-records failuing--the-whole-chunk
			foreach($readyResponse as $key => $readyData){
				try {
					$insertedData = OfferlandPrice::create($readyData);
					$inserted++ ; //Insertion-successfull;
				} catch (\Illuminate\Database\QueryException $e) {
					//var_dump($e->errorInfo);
					$failed++; // Possibly-dublicate found
				}
			}
			*/
			

			// Could-be-Disabled --because a single-duplicate-record fails the whole chunk
			$chunkSize = 100;
			$ready_chunks = array_chunk($readyResponse, $chunkSize);
			foreach($ready_chunks as $readyData){
				try {
					// $insertedData = OfferlandPrice::create($readyData);
					$insertedData = OfferlandPrice::insert($readyData);
					$inserted++ ; //Insertion-successfull;
				} catch (\Illuminate\Database\QueryException $e) {
					// var_dump($e->errorInfo);
					$failed++; // Possibly-dublicate found
				}
			}

			// $insertedRows = ($inserted-1)*$chunkSize+count(end($ready_chunks));

			$retData = ['chunk_size'=>$chunkSize,'inserted_chunks'=>$inserted, 'failed_chunks'=>$failed];

			return $retData;

		}
		
		return $readyResponse;

	}


	public function dbImportCsvDataFromApiCall($fileName=null){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://public-s3.offerland.ca/offer-share/67082254433814534046.csv',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'X-API-KEY: 6zjp8PXpKW4CNgPNYw9UW8x4JbqMbyZm6JhjyFPA'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		// $err = curl_error($curl);
		// return $err?:$response;
		
		$response = explode("\n", trim($response));
		$response = array_map('str_getcsv',  $response);
		$csv_headers = array_shift($response);

		$csv_headers[0] = $csv_headers[0]?:"csvSNo"; // First-Field is blank-(refering to)-serial-Number
		foreach($csv_headers as &$_header){
			$_header = Str::snake($_header); 
		}

		// $response = array_map(function($data) use ($csv_headers) {return array_combine($csv_headers,$data);},$response);
		// return [ 'header'=>$csv_headers , 'data'=> array_chunk($response,10)[0]];
		

		// $csv_data_chunks = array_chunk($response, 10);
		
		$readyResponse = array_map(function($data) use ($csv_headers) {
			if($data[0]==null){return ;}
			$_ary= array_combine($csv_headers,$data); 
			unset($_ary['csv_s_no']);
				// $_ary['ml_no'] = $_ary['ml_no'].'test';
			$_ary['estimated_date_time'] = date('Y-m-d H:i:s',$_ary['estimated_date_time']);
			return $_ary;
		},$response);
		//     },$csv_data_chunk);



		// if(request()->input('create','')=='vk3489hdf7834aw678haklhsf'){
		// just-perform [no-input-check--for command-line]:

		$inserted = 0;
		$failed = 0;
		/*
		// Alternative-approach --without-chunks, if there is duplicacy on(ml_no, estimatedDateTime) in-only-few-records failuing--the-whole-chunk
		foreach($readyResponse as $key => $readyData){
			try {
				$insertedData = OfferlandPrice::create($readyData);
				$inserted++ ; //Insertion-successfull;
			} catch (\Illuminate\Database\QueryException $e) {
				//var_dump($e->errorInfo);
				$failed++; // Possibly-dublicate found
			}
		}
		*/
		

		// Could-be-Disabled --because a single-duplicate-record fails the whole chunk
		$chunkSize = 100;
		$ready_chunks = array_chunk($readyResponse, $chunkSize);
		foreach($ready_chunks as $readyData){
			try {
				// $insertedData = OfferlandPrice::create($readyData);
				$insertedData = OfferlandPrice::insert($readyData);
				$inserted++ ; //Insertion-successfull;
			} catch (\Illuminate\Database\QueryException $e) {
				// var_dump($e->errorInfo);
				$failed++; // Possibly-dublicate found
			}
		}

		// $insertedRows = ($inserted-1)*$chunkSize+count(end($ready_chunks));

		$retData = ['chunk_size'=>$chunkSize,'inserted_chunks'=>$inserted, 'failed_chunks'=>$failed, 'at_time'=>date("Y-m-d h:i:sA")];

		@file_put_contents(storage_path('/logs/offerlandprice_sync.log'), json_encode($retData)."\n", FILE_APPEND );
		// Log::info( json_encode($retData) );

		return $retData;

	}


	public function getCsvDataFromLocalFile($fileName=null,$format=null){
		$fileName = empty($fileName)?'2021_06_17.csv':$fileName;
		$file = storage_path('offerland_csvs/'.$fileName);
		$wholeData = [];

		if (($handle = fopen($file, "r")) !== FALSE) {

			if (($data_headers = fgetcsv($handle, 1000, ",")) !== FALSE) {
				// to skip--first-headerline
			}
			
			// $data_headers = ['ml_no','offer_value','estimated_date_time'];
			foreach($data_headers as &$_header){
				$_header = Str::snake($_header);
			}

			if (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				// $wholeData[] = $data;
				$readyData = array_combine($data_headers, $data);
				$readyData['estimated_date_time'] = date('Y-m-d H:i:s',$readyData['estimated_date_time']);
				$wholeData[] = $readyData;

				if(request()->input('create','')!=''){

					// $readyData['ml_no'] = $readyData['ml_no'].'tr';
					// $readyData = array_map('addslashes', $readyData);
					// dd($readyData);
					// $readyData['ml_no'] = "'".$readyData['ml_no'].'test'."'";
					
					//The-query-debugged-works--But-MUST-be-privillege_issue-command denied to user 'bccondosandhomes'@'192.168.0.5' 
					// array_walk($readyData, function(&$val,$key){$val = "'$val'";});
					// $insertedData = DB::table('pixilink_mlsr.offerland_prices')->insert($readyData);
					$insertedData = OfferlandPrice::create($readyData);
					$wholeData[] = $insertedData;

				}

			}

			fclose($handle);
		}
		return $wholeData;
		return file_exists( $file )?'yes':'no';
	}


	public function perfromDbImportCsvDataFromApiCall ($ml_no=null) {
		if(empty($ml_no)) return OfferlandPrice::take(50)->get();

		if($ml_no=='csv'){
			return $this->getCsvData();
		}elseif ($ml_no=='test-create') {
			$readyData = ['ml_no'=>'test','offer_value'=>-200,'estimated_date_time'=>'2020-01-02 10:00:00' ];
			$insertedData = OfferlandPrice::create($readyData);
			return json_encode([$insertedData,$readyData]);
		}

		$offerlandPriceObj = OfferlandPrice::where('ml_no',$ml_no)->orderByDesc('estimated_date_time')->first();//->offer_value;
		return $offerlandPriceObj?$offerlandPriceObj->offer_value:$offerlandPriceObj;
	}

	public function testFunction ($ml_no=null) {
		if(Auth::user() && substr( Auth::user()->email, -13)=='@pixilink.com'){
			\Config::set('app.debug', true);
		}

		if($ml_no=='csv' && request()->input('create','')=='vk3489hdf7834aw678haklhsf'){
			return response($this->dbImportCsvDataFromApiCall() );//->json();
		}else{
			dd($this->perfromDbImportCsvDataFromApiCall($ml_no));
		}

	}


}
