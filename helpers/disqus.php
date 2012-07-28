<?php
/* Discus for KISSCMS */

// if the Remote_API class is not loaded consider uncommenting this line: 
//require_once( getPath("helpers/remote_api.php") );

class Disqus extends Remote_API {
	
	private $key;
	private $secret;
	private $token;
	private $refresh_token;
	private $url;
	private $oauth;
	private $cache;
	public $api;
	public $me;
	
	function  __construct() {
		
		$this->api = "disqus";
		$this->url = "https://disqus.com/api/3.0/";
		
		$this->key = $GLOBALS['config']['disqus']['key'];
	 	$this->secret = $GLOBALS['config']['disqus']['secret'];
		
		$this->me = ( empty($_SESSION['oauth']['disqus']['user_id']) ) ? false : $_SESSION['oauth']['disqus']['user_id'];
	 	
		$this->token = ( empty($_SESSION['oauth']['disqus']['access_token']) ) ? false : $_SESSION['oauth']['disqus']['access_token'];
	 	$this->refresh_token = ( empty($_SESSION['oauth']['disqus']['refresh_token']) ) ? false : $_SESSION['oauth']['disqus']['refresh_token'];
	 	
		// check the expiry of the token
		$this->checkToken();
		
	}
	
	
	function get( $service, $params=array() ){
		// save the params locally for further modification
		$options = $params;
		// Disqus has a special way for setting the offset
		if( array_key_exists("offset", $options) ){ 
			unset($options['offset']);
			$cursor = $this->getCache( "cursor_". $service, $params );
			if( array_key_exists('cursor', $cursor) ) $options['cursor'] = $cursor['next'];
		}
		$query = http_build_query( $options );
		
		//$url = $this->url . $service .".json?api_key=". $this->key ."&api_secret=". $this->secret ."&". $query;
		$url = $this->url . $service .".json?access_token=". $this->token ."&api_key=". $this->key ."&". $query;
		$http = new Http();
		$http->execute( $url );
		
		// encode the string in JSON
		$result = ($http->error) ? die($http->error) : json_decode( $http->result);
		
		// failsafe in case the API request is null
		if( is_null($result) ) return false;
		
		// pick only the selected set of results
		if( $result->response ) { 
			// cache result
			if( isset($result->cursor) ) $this->setCache( "cursor_". $service, $params, $result->cursor );
			
			// FIX: some requests return the data in an object...
			switch ($service){ 
				case "users/listActivity":
					$this->setCache( $service, $params, $result->response, "object->id" );
				break;
				//case "users/listFollowing":
				//	$this->setCache( $service, $params, $result->response, "name" );
				//break;
				default:
					$this->setCache( $service, $params, $result->response, "id" );
				break;
			}
			
			// return the array from the cache
			return $result->response; 
			
		} else { 
			return false;
		}
	}
	
	function post( $service, $params=array() ){
		$url = $this->url . $service .".json";
		$params = array_merge( $params, array(
											"access_token" => $this->token, 
											"api_key" => "$this->key", 
											//"api_secret" => "$this->secret", 
										) );
		$http = new Http();
		$http->setMethod('POST');
		$http->setParams( $params );
		$http->execute( $url );
		
		return ($http->error) ? die($http->error) : json_decode( $http->result);
		
	}
	
	function following(){
		$params=array( "user"=> $this->me );
		// return the cache under conditions
		if( $this->checkCache("users/listFollowing", $params) ){ 
			$following = $this->getCache("users/listFollowing", $params);
		} else {
			$following = $this->get( "users/listFollowing", $params);
		}
		
		return $following;

	}
	
	function checkToken(){
		
		if( empty($_SESSION['oauth']['disqus']) ) return false;
		
		// check if we need to refresh the token
		$expires_in = ( !empty($_SESSION['oauth']['disqus']['expiry']) ) ? strtotime($_SESSION['oauth']['disqus']['expiry']) - strtotime("now") : 0; // seconds
		
		if( $expires_in < 600 ){
			
			$oauth = new Disqus_OAuth();
			// update the token
			$oauth->refresh_token();
			// update the token
			$this->token = $_SESSION['oauth']['disqus']['access_token'];
		}
		 
	}
	
	function isFollowing( $post ){
		// return false if there is no user id
		if( empty($post->author->id) ) return false;
		// set the parameters of the request
		$params=array( "user"=> $this->me );
		// we are assuming that followers have been cached already 
		$following = $this->getCache("users/listFollowing", $params); 
		// security measure in case the following request failed
		if( empty( $following ) ) return false;
		
		//variables
		$id = $post->author->id;
		
		foreach( $following  as $user){
			// return true if the ids match
			if ( $id == $user->id ) return true;
		}
		
		return false;
	}
	
	function isMine( $post ){
		// return false if there is no user id
		if( empty($post->author->id) ) return false;
		// variables
		$id = $post->author->id;
		$me = $this->me;
		
		return ( $id == $me );
	}
	
	
	/*
	function listThread( $id ){
		$url = $this->url ."threads/listPosts.json?api_key=". $this->key ."&thread=".$id;
		$http = new Http();
		$http->execute( $url );
		return ($http->error) ? die($http->error) : json_decode( $http->result);
	}
	
	function listPosts( $user, $limit ){
		$url = $this->url ."users/listPosts.json?api_key=". $this->key ."&user=". $user ."&limit=". $limit ."&related=thread";
		$http = new Http();
		$http->execute( $url );
		//($http->error) ? die($http->error) : $result = json_decode( $http->result);
		return ($http->error) ? die($http->error) : json_decode( $http->result);
	}
	*/
	
}