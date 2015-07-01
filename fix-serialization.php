#!/usr/bin/php
<?php

/*
* Blogestudio Fix Serialization	1.2
* Fixer script of length attributes for serialized strings (e.g. Wordpress databases)
* License: GPL version 3 or later - http://www.gnu.org/licenses/gpl.txt
* By Pau Iglesias
* http://blogestudio.com
* 
* Inspiration and regular expression code base from David Coveney:
* http://davidcoveney.com/575/php-serialization-fix-for-wordpress-migrations/
* 
* Usage:
* Uncompressed Files
*  cat dump.sql | php fix-serialization.php > fixed-dump.sql
* Compressed Files
*  gunzip -c dump.sql.gz | php fix-serialization.php | gzip > fixed-dump.sql.gz
* Versions:
* 
* 	1.0 2011-08-03 Initial release
* 	1.1 2011-08-18 Support for backslashed quotes, added some code warnings
* 	1.2 2011-09-29 Support for null or zero length strings after preg_replace is called, and explain how to handle these errors
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

while (false !== ($line = fgets(STDIN))) {
	$do_preg_replace = true;

    // Replace serialized string values
    $data = preg_replace('!s:(\d+):([\\\\]?"[\\\\]?"|[\\\\]?"((.*?)[^\\\\])[\\\\]?");!e', "'s:'.strlen(unescape_mysql('$3')).':\"'.unescape_quotes('$3').'\";'", $line);
	
	fwrite(STDOUT,$data);
}



?>
