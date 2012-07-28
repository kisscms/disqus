<?php


//===============================================
// Configuration
//===============================================

if( class_exists('Config') && method_exists(new Config(),'register')){ 

	// Register variables
	Config::register("disqus", "key", "0000000");
	Config::register("disqus", "secret", "AAAAAAAAA");

}

?>