#!/usr/bin/php
<?php

	/*
	* Blogestudio Fix Serialization	1.0
	* http://blogestudio.com
	* By Pau Iglesias
	*
	* Fixer script of length attributes for serialized strings (e.g. WP databases)
	*
	* Usage:
	*
	* 	/usr/bin/php replacer-serialized.php my-sql-file.sql 
	*
	* Inspiration and regular expression code base from David Coveney: 
	* http://davidcoveney.com/575/php-serialization-fix-for-wordpress-migrations/
	*
	* Knowed errors:
	*
	* - Memory size exhausted
	* Allowed memory size of 67108864 bytes exhausted (tried to allocate 35266489 bytes)
	* How to fix: update php.ini memory_limit to 512M or more, and restart cgi service or web server
	*
	*/
	
	// Unescape to avoid dump-text issues
	function unescape_mysql($value) {
		return str_replace(array("\\\\", "\\0", "\\n", "\\r", "\Z",  "\'", '\"'),
						   array("\\",   "\0",  "\n",  "\r",  "\x1a", "'", '"'), 
						   $value);
	}
	
	// Fix strange behaviour if you have escaped quotes in your replacement
	function unescape_quotes($value) {
		return str_replace('\"', '"', $value);
	}	

	// Check command line arguments
	if (isset($argv) && isset($argv[1])) {
	
		// Compose path from argument
		$path = dirname(__FILE__).'/'.$argv[1];
		if (file_exists($path)) {
		
			// Get file contents
			if ($fp = fopen($path, 'r')) {
			
				// Copy data
				$data = fread($fp, filesize($path));
				fclose($fp);

				// Replace serialized string values
				$data = preg_replace('!s:(\d+):(""|"((.*?)[^\\\\])");!e', "'s:'.strlen(unescape_mysql('$3')).':\"'.unescape_quotes('$3').'\";'", $data);
				
				// And finally write data
				if ($fp = fopen($path, 'w')) {
					fwrite($fp, $data);
					fclose($fp);
				}
			}
		}
	}

?>