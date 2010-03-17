<?php
/**
 * This file defines xf_source_Loader, a utility for loading source files.
 * 
 * PHP version 5
 * 
 * @package xf
 * @subpackage source
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 */

require_once(dirname(__FILE__).'/../Object.php');
require_once(dirname(__FILE__).'/../errors/ArgumentError.php');
require_once(dirname(__FILE__).'/../errors/DefinitionError.php');
require_once(dirname(__FILE__).'/../system/Path.php');

/**
 * xf_source_Loader 
 *
 * This file (after the class definition) registers the autoloader necessary to load other necessary classes.
 * This class has a kind of internal path memory, simalar to php's built in inlude_path. It is kept internal because...
 * The paths can be relative to a base that is a variable and can change, and hence the paths relative to that base.
 * When the base changes, the paths are not used, but still saved in memory corresponding to that specific base.
 * This is the biggest difference between xf_source_Loader's autoload and php's spl_autoload.
 * The paths do not have to be and should not be absolute. Though the base is saved as absolute, it may be set relatively.
 *
 * NOTE: This class is not your standard load anything kind of class. Things loaded must be relative to the current base.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage source
 */
class xf_source_Loader extends xf_Object {
		
	// STATIC MEMBERS
	
	/**
	 * inc
	 *
	 * Shortcut to include or include_once
	 * Just specify the parameter to true to do a include_once, if false it does an include.
	 *
	 * @param string $file The file to include or include_once
	 * @parem bool $once Whether to do a include_once or not
	 * @return void
	 */
	public static function inc( $file, $once = false ) {
		if( $once ) include_once( $file ); else include( $file );
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @ignore
	 * This is the base directory that all paths in the path dictionary are relative from, defaults to this file's directory.
	 */
	protected $_base = null;
	/**
	 * @ignore
	 * This holds the relative paths from the base variable that this class searches to locate autoload and files.
	 * It also hold a memory of all the past base directories and their paths.
	 * This means that if you set a path, and you changed the base, and then change it back, it remembers all the paths you may have set previously.
	 */
	protected $_pathDict = array();
	/**
	 * @ignore
	 * This is an internal autload accepted extensions dictionary.
	 * It is used the same way that spl_autoload_extensions() it is used in the autload method.
	 * It is an associative array almost like the path dictionary, but only one level of keys.
	 */
	protected $_extDict = array( '.php' => 0 );
		
	/**
	 * CONSTRUCTOR
	 *
	 * Register the autoLoad xf_source_Loader method with the SPL ( see comments on that method )
	 * Any classes within the paths of this xf_source_Loader should now load automatically.
	 * Just make sure all your classes needed to load automatically are in an xf_source_Loader path.
	 *
	 * @param string $base
	 * @return void
	 */
	public function __construct( $base )
	{
		parent::__construct();
		$this->base = $base;
		// Does not work on object methods in PHP < 5.3
		//spl_autoload_register( array( $this, 'autoLoad' ) );
	}
	
	/**
	 * autoLoad
	 *
	 * Should be registered via spl_autoload_register to automagically load a class file.
	 * This registration is done with spl_autoload_register() after this class definition.
	 * To adhere to the SPL, this function SHOULD NOT return anything, and NOT OUTPUT anything.
	 * This is so that any other script calling class_exists() without setting the autoload argument will continue to function properly.
	 * Note: Most authors don't use "class_exists( $class, false )", because they assume autoload functions will be defined like this.
	 * Defining autoloads correctly means you can register as many as you want without interfering with eachother.
	 * Again, if this function returns or outputs anything whatsoever, it will most likely interfere with other scripts.
	 *
	 * @param string $class The class referenced before it was defined
	 * @return void
	 */
	public function autoLoad( $class ) {
		// First lets try the last successful autoLoad path AND extension
		if( !$loaded = $this->load( $class, xf_system_Path::join( key($this->paths), $class . key($this->exts) ) ) ) {
			// Iterate through the available autoLoadPaths from last to first.
			end( $this->paths );
			do {
				// Iterate through the available extensions from last to first.
				end( $this->paths );
				do {
					//if( $loaded = $this->load( xf_system_Path::join( key($this->paths), $class . key($this->exts) ) ) ) break; // BREAK THE LOOP
					if( $loaded = $this->load( $class, xf_system_Path::join( key($this->paths), $class . key($this->exts) ) ) ) break; // BREAK THE LOOP
					$name = basename( str_replace('_', xf_system_Path::DS, $class ) );
					//echo xf_system_Path::join( key($this->paths), $name . key($this->exts) );
					echo key($this->paths);
					echo $this->base;
					if( $loaded = $this->load( $class, xf_system_Path::join( key($this->paths), $name . key($this->exts) ) ) ) break; // BREAK THE LOOP
					
				} while ( prev($this->exts) !== false );
				if( $loaded ) break; // BREAK THE LOOP
			} while ( prev($this->paths) !== false );
		}
	}
	
	/**
	 * locate
	 *
	 * Locate a file or directory. If it is a file and is located, it returns the absolute path to that file.
	 * If it is a directory and it is located, this can also return the contents of that directory.
	 * If it is a directory, and it should work recursively, then it get all all contents of all the directories from the one specified.
	 * Optionally you can load all the files in all the ways specified. It does not try to load directories. But non-script files are fair game.
	 *
	 * @param string $filename A filename (this includes directory names) to search for.
	 * @param bool $load Do you want to load any files located?
	 * @param bool $dirContent Do you want to retrieve the contents of a directory, or just the absolute path?
	 * @param bool $listDirs Does this also list sub directories?
	 * @param bool $recursive Does this work recursively down the directory tree?
	 * @parem bool $once If loading, whether to do a include_once or not
	 * @return string|array|false Absolute path to file and/or directory, an Array of files and/or directories, an Array tree, or false
	 */
	public function locate( $filename = '', $load = false, $dirContent = true, $listDirs = true, $recursive = false, $once = true ) {
		// If filename is already an absolute path it won't change
		// If filename is blank then it defaults to the base
		$abs = xf_system_Path::join( $this->base, $filename );
		// check if the path exists
		if( $located = $this->inBase( $abs ) ) {
			// if located, then do a check if it is a directory or file
			if( !is_dir( $abs ) ) {
				// Load this file now?
				if( $load ) {
					$ext = '.'.array_pop( explode( '.', $abs ) );
					if( $this->isExt( $ext ) ) self::inc( $abs, $once );
				}
				// Return the absolute path to the file
				return $abs;
			} else if( $dirContent ) {
				// If it is a directory get the contents of it.
				$dirContents = array();
				$dirRsc = @opendir( $abs );
				while ( ($fileRsc = readdir($dirRsc)) !== false ) {
					// Trim dots from both sides because most system files and directories start with these,
					// and filenames should not have trailing dots anyway.
					$safe = trim( $fileRsc, '.' );
					if( empty( $safe ) && $safe != $fileRsc ) continue;
					// $safe made it through, so the file is safe to continue and return.
					$file = xf_system_Path::join( $abs, $safe );
					if ( is_dir( $file ) ) {
						// If it is another directory, do we search that one too, or just get the absolute path?
						$subLocated = ( $recursive ) ? $this->locate( xf_system_Path::join( $filename, $safe ), $load, $dirContent, $listDirs, $recursive, $once ) : $file;
						if( $listDirs ) $dirContents[$safe] = $subLocated;
						continue;
					} else if( is_file( $file ) ) {
						// Load this file now?
						if( $load ) {
							$ext = '.'.array_pop( explode( '.', $file ) );
							if( $this->isExt( $ext ) ) self::inc( $file, $once );
						}
						$dirContents[$safe] = $file;
					}
				}
				@closedir($dirRsc);
				// If the directory has no contents within it, then just return the absolute path to the directory.
				if( count($dirContents) == 0 ) return $abs;
				// Return an associative array of directory contents.
				// Filenames as keys, absolute paths as values.
				return $dirContents;
			} else {
				// Not getting directory contents, just return the absolute path to the directory. 
				return $abs;
			}
		}
		return $located;
    }
    
	/**
	 * load
	 *
	 * This is just a wrapper around the locate method.
	 * It ommits the load paramter and sets it to true on the locate method.
	 * The dirContent parameter also changes to default to false, but is still made available in the parameters here.
	 * It just makes sense to have this method available since this class is named 'xf_source_Loader'.
	 * Although the locate method is versatile, it might be hard to grasp, ergo the load method.
	 *
	 * @param string $filename A filename (this includes directory names) to search for.
	 * @param bool $dirContent Do you want to retrieve the contents of a directory, or just the absolute path?
	 * @param bool $recursive Does this work recursively down the directory tree?
	 * @parem bool $once If located, whether to do a include_once or not
	 * @return string|array|false Absolute path to file and/or directory, an Array of files and/or directories, an Array tree, or false
	 */
	public function load( $filename = '', $dirContent = false, $recursive = false, $once = true ) {
		return $this->locate( $filename, true, $dirContent, false, $recursive, $once );
	}
	
	/**
	 * loadClass
	 *
	 * This is a wrapper around the method load (Which does a include_once).
	 * Overall this just simplifies loading classes manually.
	 *
	 * @param string $class The qualified class name
	 * @param string $path The path where the class file should be located.
	 * @param bool $strict Should this display an error if the class was already defined? (Defaults to TRUE)
	 * return string|false Absolute path to file, or false on fail
	 */
	public function loadClass( $class, $path, $strict = true ) {
		// the second parameter in class_exists() is important. It means DON'T AUTOLOAD!
		if( !class_exists( $class, false ) ) {
			$file = xf_system_Path::join( $path, $class );
			// Iterate through the available extensions from last to first.
			end( $this->exts );
			do {
				// First try the regular class name with as the same as the filename
				$filename = xf_system_Path::join( $path, $class ) . key($this->exts);
				if( $loaded = $this->load( $filename ) ) return $loaded;
				// Now try pulling the classname from the package name
				$filename = xf_system_Path::join( $path, str_replace('_', xf_system_Path::DS, $class ) ) . key($this->exts);
				if( $loaded = $this->load( $filename ) ) return $loaded;
			} while ( prev($this->exts) !== false );
			// The error here is optional.
			if( $strict ) {
				throw new xf_errors_DefinitionError( 0, $class );
			}
			return false;
		}
		// The error here is optional.
		if( $strict ) {
			throw new xf_errors_DefinitionError( 1 , $class );
		}
		return $class;
	}
	
	/**
	 * inBase
	 *
	 * Checks if the specified path is within the current base.
	 * It optionally checks if the path is absolute to also check if it is within the base.
	 * file_exists() checks directory names (useful for path checking).
	 *
	 * @param string $p The relative or absolute path to check
	 * @param bool $absOnly Flag to only check absolute paths and return false if relative
	 * @return bool
	 */
	public function inBase( $p, $absOnly = true ) {
		if( xf_system_Path::isAbs( $p ) ) {
			// Make sure to convert both paths to POSIX style to avoid any regex errors
			if( preg_match( '|^'.xf_system_Path::toPOSIX( $this->base ).'|', xf_system_Path::toPOSIX( $p ) ) ) return file_exists( $p );
			return false;
		} else if( $absOnly ) {
			return false;
		}
		return file_exists( xf_system_Path::join( $this->base, $p ) );
	}
	
	/**
	 * isPath
	 *
	 * Check if path was added to the path dictionary.
	 * @param string $p The path to check
	 * @return bool
	 */
	public function isPath( $p ) {
		if( $p == $this->base ) return true;
		return array_key_exists( $p, $this->paths );
	}
	
	/**
	 * addPath
	 *
	 * Tries to add an entry to the path dictionary.
	 * This method automatically converts absolute paths to relative paths based on the current base.
	 * First it converts the absolute path to relative then tries to add that path instead.
	 * Note: Uses associative array for easy manipulation.
	 *
	 * @param string $p The path to add, relative to the includes folder.
	 * @param int $priority When autoloading this is the priority this path has in regards to all paths that have been added
	 * @return string|false The path added on success or false
	 */
	public function addPath( $p, $priority = 0 ) {
		if( ! (int) $priority ) {
			throw new xf_errors_ArgumentError( 3, 1, $priority, 'Expected Integer' );
			return false;
		}
		if( xf_system_Path::isAbs( $p ) ) {
			if( $this->inBase( $p ) ) return $this->addPath( xf_system_Path::replace( $p, $this->base ) );
		} else {
			if ( !$this->isPath( $p ) ) $this->removePath( $p );
			$this->_pathDict[$this->base][$p] = $priority;
			asort( $this->_pathDict[$this->base] );
			return $p;
		}
		return false;
	}
	
	/**
	 * removePath
	 *
	 * Tries to remove an entry from the path dictionary.
	 * Note: Uses associative array for easy manipulation.
	 *
	 * @param string $p The path to remove, relative to the includes folder.
	 * @return string|false The path removed on success or false
	 */
	public function removePath( $p ) {
		if( !$this->isPath( $p ) ) return false;
		unset( $this->_pathDict[$this->base][ $p ] );
		return $p;
	}
	
	/**
	 * isExt
	 *
	 * Check if extension was added to the extension dictionary.
	 *
	 * @param string $e The extension to check
	 * @return bool
	 */
	public function isExt( $e ) {
		return array_key_exists( $e, $this->exts );
	}
	
	/**
	 * addExt
	 *
	 * Tries to add an entry to the extension dictionary.
	 * Note: Uses associative array for easy manipulation.
	 *
	 * @param string $e The extension to add
	 * @return string|false The extension added on success or false
	 */
	public function addExt( $e, $priority = 0 ) {
		if( ! (int) $priority ) {
			throw new xf_errors_ArgumentError( 3, 1, $priority, 'Expected Integer' );
			return false;
		}
		if ( !$this->isExt( $e ) ) $this->removeExt( $p );
		$this->_extDict[$e] = $priority;
		asort( $this->_pathDict[$this->base] );
		return $e;
	}
	
	/**
	 * removeExt
	 *
	 * Tries to remove an entry from the extension dictionary.
	 * Note: Uses associative array for easy manipulation.
	 *
	 * @param string $e The extension to remove
	 * @return string|false The extension removed on success or false
	 */
	public function removeExt( $e ) {
		if( !$this->isExt( $e ) ) return false;
		unset( $this->_extDict[ $e ] );
		return $e;
	}
	
	/**
	 * getRelative
	 *
	 * This takes a path and returns the part of the path relative to the base, if it not not in the base it returns false.
	 *
	 * @param string $p The path to edit
	 * @return string|false The base relative path or false
	 */
	public function getRelative( $p ) {
		if( !$this->inBase( $p ) ) return false;
		if( xf_system_Path::isAbs( $p ) ) return xf_system_Path::replace( $p, $this->base );
		return $p;
	}
	
	// RESERVERED PROPERTIES
	
	/**
	 * exts
	 *
	 * Gets the current extensions which are the keys of the extension dictionary which is an associative array.
	 *
	 * @return array Indexed array of strings
	 */
	public function &get__exts() {
		return $this->_extDict;
	}
	
	/**
	 * paths
	 *
	 * Gets the current paths which are the keys of the path dictionary that is an associative array.
	 * Since the base can actually change, the path dictionary holds paths within a reference of the current base.
	 * If the reference key hasn't been added yet, this function actually does it.
	 * The logic is set up so that all the methods that need to reference the current base key directly,
	 * actaully call this function first in a round about way.
	 *
	 * @return array Indexed array of strings
	 */
	public function &get__paths() {
		if( !isset($this->_pathDict[$this->base]) ) {
			$this->_pathDict[$this->base] = array( '.' => 999 );
		}
		return $this->_pathDict[$this->base];
	}
	
	/**
	 * base
	 *
	 * Gets the base directory member.
	 * If it hasn't been set yet it should default to this file's directory.
	 *
	 * @return string
	 */
	public function get__base() {
		if( is_null( $this->_base ) ) $this->_base = dirname( __FILE__ );
		return $this->_base;
	}
	
	/**
	 * base
	 *
	 * Trys to set the base directory.
	 * If the provided path is not absolute it checks if it is in (only relative to) the current base.
	 * If it is, it appends that path to the base. If it is not then it fails.
	 * If the provided path is absolute, then it checks if it is real. If it is, success, if not fail.
	 * realpath() returns nothing if it is not a real path (useful for path checking).
	 * It also changes any symbolic links or 'accepted' file system paths to real absolute paths.
	 *
	 * @param string $p The path to set
	 * @return string|false The new base on success or false.
	 */
	public function set__base( $p ) {
		if( xf_system_Path::isAbs( $p ) ) {
			if( $abs = realpath( $p ) ) return $this->_base = $abs;
		} else if( $this->inBase( $p, false ) ) {
			if( $abs = realpath( xf_system_Path::join( $this->base, $p ) ) ) return $this->_base = $abs;
		}
		return false;
	}
}
?>