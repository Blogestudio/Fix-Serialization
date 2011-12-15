#!/usr/bin/php
<?php

/*
* Blogestudio Fix Serialization	1.1
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
* Versions:
* 
* 	1.0 2011-08-03 Initial release
* 	1.1 2011-08-18 Support for backslashed quotes, added some code warnings
*
* Knowed errors:
*
* - Memory size exhausted
* Allowed memory size of 67108864 bytes exhausted (tried to allocate 35266489 bytes)
* How to fix: update php.ini memory_limit to 512M or more, and restart cgi service or web server.
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
if (!(isset($argv) && isset($argv[1]))) {
	
	// Error
	echo 'Error: no input file specified'."\n\n";

// With arguments
} else {
	
	// Compose path from argument
	$path = dirname(__FILE__).'/'.$argv[1];
	if (!file_exists($path)) {
	
		// Error
		echo 'Error: input file does not exists'."\n";
		echo $path."\n\n";
	
	// File exists
	} else {
	
		// Get file contents
		if (!($fp = fopen($path, 'r'))) {
			
			// Error
			echo 'Error: can`t open input file for read'."\n";
			echo $path."\n\n";
		
		// File opened for read
		} else {
		
			// Copy data
			if (!($data = fread($fp, filesize($path)))) {

				// Error
				echo 'Error: can`t read entire data from input file'."\n";
				echo $path."\n\n";				
			
			// Data ok
			} else {
			
				// Replace serialized string values
				$data = preg_replace('!s:(\d+):([\\\\]?"[\\\\]?"|[\\\\]?"((.*?)[^\\\\])[\\\\]?");!e', "'s:'.strlen(unescape_mysql('$3')).':\"'.unescape_quotes('$3').'\";'", $data);
			}

			// Close file
			fclose($fp);
			
			// And finally write data
			if (!($fp = fopen($path, 'w'))) {

				// Error
				echo "Error: can't open input file for writing\n";
				echo $path."\n\n";
				
			// Open for write
			} else {
				
				// Write file data
				if (!fwrite($fp, $data)) {
					
					// Error
					echo "Error: can't write input file\n";
					echo $path."\n\n";
				}
				
				// Close file
				fclose($fp);
			}
		}
	}
}



?>