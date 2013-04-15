<?php
// increase memory limit
ini_set('memory_limit', '1028M');

// no time limit
set_time_limit(0);

// partial string to search first line for
//$hack_str = '<?php /**/ eval(base64_decode(';
$hack_str = '<?php eval(gzinflate(base64_decode(';

// Change $the_dir to the relative path you'd like to start searching/fixing in. 
// You can use this if the script is timing out (or just move the script into subdirectories).
$the_dir = './';

function get_infected_files( $dir ) {
	global $hack_str;
	$dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	$d = opendir($dir);
	$files = array();
	if ( $d ) {
		while ( $f = readdir($d) ) {
			$path = $dir . $f;
				
			if ( is_dir($path) ) {
				if ( $f != '.' && $f != '..' ) {
					$more_files = get_infected_files($path);
					if ( count($more_files) > 0 ) {
						$files[] = $more_files;
					}
				}
			}
			else {
				if ( strpos($f, '.php') !== false ) {
					$contents = explode("\n", file_get_contents($path));
					if ( strpos($contents[0], $hack_str, 0) !== false ) {
						$files[] = $path;
					}
				}
			}
		}
	}
	return $files;
}

//function print_files( $files ) {
	//if ( count($files) > 0 ) {
		//foreach ( $files as $file ) {
			//if ( is_array($file) ) {
				//print_files($file);
			//}
			//else {
				//echo $file . '<br />';
			//}
		//}
	//}

function fix_files( $files ) {
	global $hack_str;
	foreach ( $files as $file ) {
		if ( is_array($file) ) {
			fix_files($file);
		}
		else { 
			$contents = explode("\n", file_get_contents($file));
			unset($contents[0]);
			$f = fopen($file, 'w');
			if ( $f ) {
				$the_content = implode($contents, "\n");
				$the_content = preg_replace('/^\\s/', '', $the_content); // remove any leading whitespace.
				fwrite($f, $the_content, strlen($the_content));
				fclose($f);
				//echo "Removed first line containing <code>" .  htmlentities($hack_str) ."</code>from $file...<br />";
			}
		} 
	}
}

function get_count( $files ) {
	$count = count($files);
	foreach ( $files as $file ) {
		if ( is_array($file) ) {
			$count--; // remove this because it's a directory
			$count += get_count($file);
		}
		else {
			$count ++;
		}
	}
	return $count / 2;
}

$files = get_infected_files($the_dir);

if ( count($files) > 0 ) :

	//if ( $_POST['do_fix'] ) :
		fix_files( $files );
		die();
	endif; 	
	//print_files($files);
?>