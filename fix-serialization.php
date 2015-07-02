#!/usr/bin/php
<?php

/*
* Blogestudio Fix Serialization	1.2-1
* Fixer script of length attributes for serialized strings (e.g. Wordpress databases)
* License: GPL version 3 or later - http://www.gnu.org/licenses/gpl.txt
* By Pau Iglesias
* http://blogestudio.com
* 
* Inspiration and regular expression code base from David Coveney:
* http://davidcoveney.com/575/php-serialization-fix-for-wordpress-migrations/
* 
* Usage:
*
*	-In place file processing
* 	/usr/bin/php fix-serialization.php my-sql-file.sql
*
*	-Stream processing Examples
*
*	--Uncompressed to uncompressed
*	cat my-sql-file.sql | /usr/bin/php fix-serialization.php --stream > my-sql-fixed-file.sql
*
*	--Compressed to compressed
*	gunzip < my-sql-file.sql.gz | /usr/bin/php fix-serialization.php --stream | gzip > my-sql-fixed-file.sql.gz
*
*	--Direct import into mysql from compressed
*	gunzip < my-sql-file.sql.gz | /usr/bin/php fix-serialization.php --stream | mysql -uuname -p "database name"
*	
* Versions:
* 
* 	1.0 2011-08-03 Initial release
* 	1.1 2011-08-18 Support for backslashed quotes, added some code warnings
* 	1.2 2011-09-29 Support for null or zero length strings after preg_replace is called, and explain how to handle these errors
* 	1.2-1 2015-07-02 (jbrule) Added stream processing support to avoid the memory allocation issue when working with large database files (tested up to 6GB).
* 
* Knowed errors:
*
* - Memory size exhausted
* Allowed memory size of 67108864 bytes exhausted (tried to allocate 35266489 bytes)
* How to fix: update php.ini memory_limit to 512M or more, and restart cgi service or web server
*
* - Function preg_replace returns null or 0 length string
* If preg_last_error = PREG_BACKTRACK_LIMIT_ERROR (value 2), increase pcre.backtrack_limit in php.ini (by default 100k, change to 2M by example)
* Same way for others preg_last_error codes: http://www.php.net/manual/en/function.preg-last-error.php
* 
* TODO next versions
* 
* - Check if needed UTF-8 support detecting and adding u PCRE modifier
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

function replace_serialized_string_values($data){
	// Replace serialized string values
				
	return preg_replace('!s:(\d+):([\\\\]?"[\\\\]?"|[\\\\]?"((.*?)[^\\\\])[\\\\]?");!e', "'s:'.strlen(unescape_mysql('$3')).':\"'.unescape_quotes('$3').'\";'", $data);
}

//Setup arguments to flag stream processing
$options = getopt("s::",array("stream::"));

//Check if data is being feed in via a pipe. Process line by line and output via stdout if that is the case.
if((key_exists("s",$options) || key_exists("stream",$options)) && $fh = fopen('php://stdin','r')) {
	stream_set_blocking($fh, false);
	
	$stdin = '';
  
	while (false !== ($stdin = fgets($fh))) {

		//Note. $stdin is a single line of the dumpfile. In testing this appears to be fine as the mysqldump field values do not appear to span past a single line.
	
		// Replace serialized string values
		$data = replace_serialized_string_values($stdin);
		
		fwrite(STDOUT,$data);
		
	}
	fclose($fh); 
} elseif (!(isset($argv) && isset($argv[1]))) { // Check command line arguments
	
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
			
			// Initializations
			$do_preg_replace = false;
		
			// Copy data
			if (!($data = fread($fp, filesize($path)))) {

				// Error
				echo 'Error: can`t read entire data from input file'."\n";
				echo $path."\n\n";
			
			// Check data
			} elseif (!(isset($data) && strlen($data) > 0)) {

				// Warning
				echo "Warning: the file is empty or can't read contents\n";
				echo $path."\n\n";
			
			// Data ok
			} else {

				// Tag context
				$do_preg_replace = true;

				// Replace serialized string values
				$data = replace_serialized_string_values($data);
			}

			// Close file
			fclose($fp);
			
			// Check data
			if (!(isset($data) && strlen($data) > 0)) {
				
				// Check origin
				if ($do_preg_replace) {

					// Error
					echo "Error: preg_replace returns nothing\n";
					if (function_exists('preg_last_error')) echo "preg_last_error() = ".preg_last_error()."\n";
					echo $path."\n\n";
				}
			
			// Data Ok
			} else {

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
}
