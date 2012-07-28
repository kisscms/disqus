<?php
// FIX - to include the base OAuth lib not in alphabetical order
require_once( realpath("../") . "/app/plugins/oauth/helpers/kiss_oauth.php" );

/* Discus for KISSCMS */
class Disqus_OAuth extends KISS_OAuth_v2 {
	
	function  __construct( $api="disqus", $url="https://disqus.com/api/oauth/2.0" ) {
		
		$this->url = array(
			'authorize' 		=> $url ."/authorize/", 
			'access_token' 		=> $url ."/access_token/", 
			'refresh_token' 	=> $url ."/refresh_token/"
		);
		
		parent::__construct( $api, $url );
		
	}
	
	// additional params not covered by the default OAuth implementation
	public function access_token( $params, $request=array() ){
		
		$request = array(
			"params" => array( "grant_type" => "authorization_code" )
		);
		
		parent::access_token($params, $request);

	}
	
	public function refresh_token($request=array()){
		
		$request = array(
			"params" => array( "grant_type" => "refresh_token" )
		);
		
		parent::refresh_token($request);
	}
	
	function save( $response ){
		
		// erase the existing cache
		$disqus = new Disqus();
		$disqus->deleteCache();
		
		// convert string into an array
		$auth = json_decode( $response, TRUE );
		
		if( is_array( $auth ) && array_key_exists("expires_in", $auth) )
			// variable expires is the number of seconds in the future - will have to convert it to a date
			$auth['expiry'] = date(DATE_ISO8601, (strtotime("now") + $auth['expires_in'] ) );
		
		// save to the user session 
		$_SESSION['oauth']['disqus'] = $auth;
		
	}
	
}