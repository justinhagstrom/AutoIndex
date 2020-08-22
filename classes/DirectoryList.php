<?php

/**
 * @package AutoIndex
 *
 * @copyright Copyright (C) 2002-2004 Justin Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL)
 *
 * @link http://autoindex.sourceforge.net
 */

/*
   AutoIndex PHP Script is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   AutoIndex PHP Script is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!defined('IN_AUTOINDEX') || !IN_AUTOINDEX) die;




/**
 * Maintains an array of all files and folders in a directory. Each entry is
 * stored as a string (the filename).
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.1 (June 30, 2004)
 * @package AutoIndex
 */
class DirectoryList implements Iterator {

	/**
	 * @var string The directory this object represents
	 */
	protected $dir_name;

	/**
	 * @var array The list of filesname in this directory (strings)
	 */
	protected $contents;

	/**
	 * @var int The size of the $contents array
	 */
	private $list_count;

	//begin implementation of Iterator
	/**
	 * @var int $i is used to keep track of the current pointer inside the array when implementing Iterator
	 */
	private $i;

	/**
	 * @return string The element $i currently points to in the array
	 */
	public function current() {
		if ($this->i < count($this->contents)) {
			return $this->contents[$this->i];


		}
		return false;
	}

	/**
	 * Increments the internal array pointer, then returns the value
	 * at that new position.
	 *
	 * @return string The current position of the pointer in the array
	 */
	public function next() {
		$this->i++;
		return $this->current();

	}

	/**
	 * Sets the internal array pointer to 0
	 */
	public function rewind() {
		$this->i = 0;

	}

	/**
	 * @return bool True if $i is a valid array index
	 */
	public function valid() {
		return ($this->i < count($this->contents));

	}

	/**
	 * @return int Returns $i, the key of the array
	 */
	public function key() {

		return $this -> i;
	}
	//end implementation of Iterator

	private $dirs = null;
	public function is_dir($item) {
		if ($item == '..') return true;
		if ($this->dirs === null) {
			$dirs = $this->cache_file('dirs');
			if ($dirs === null) {
				$this->dirs = array();
				foreach ($this->contents as $name) {
					if ($name == '..') continue;
					if (@filetype($this->dir_name.$name) == 'dir') {
						$this->dirs[$name] = true;
					}
				}
				$this->cache_file('dirs', implode("\n", array_keys($this->dirs)));
			} else {
				$this->dirs = array_fill_keys(explode("\n", $dirs), true);
			}
		}
		return isset($this->dirs[$item]);
	}

	/**
	 * @return int The total size in bytes of the folder (recursive)
	 */
	public function size_recursive() {
		$total_size = $this->cache_file('size');
		if ($total_size === null) {
			$total_size = 0;
			foreach ($this as $current) {
				if ($current == '..') continue;
				$t = $this -> dir_name . $current;
				if ($this->is_dir($current)) {



					$temp = new DirectoryList($t);
					$total_size += $temp -> size_recursive();
				} else {



					$total_size += @filesize($t);
				}
			}
			$this->cache_file('size', $total_size);
		}
		return doubleval($total_size);
	}

	private $dir_mtime = null;
	private $dir_md5 = null;
	private function cache_file($prefix, $value = null) {
		if ($this->dir_md5 === null) $this->dir_md5 = md5($this->dir_name);
		$file = CACHE_STORAGE_DIR.'.ht_'.$this->dir_md5.'_'.$prefix;
		if ($value === null) {
			if (@file_exists($file)) {
				$mtime = @filemtime($file);
				if ($mtime > time() - 3600) {
					if ($this->dir_mtime === null) $this->dir_mtime = filemtime($this->dir_name);
					if ($mtime >= $this->dir_mtime) {
						return @file_get_contents($file);
					}
				}
			}
		} else { // atomic write
			$tmp = $file.'_'.microtime(true).'_'.mt_rand();
			file_put_contents($tmp, $value);
			rename($tmp, $file);
		}
		return null;
	}

	/**
	 * @return int The total number of files in this directory (recursive)
	 */
	public function num_files() {
		$count = $this->cache_file('count');
		if ($count === null) {
			$count = 0;
			foreach ($this as $current) {
				if ($current == '..') continue;
				if ($this->is_dir($current)) {
					$temp = new DirectoryList($this->dir_name.$current);




					$count += $temp -> num_files();
				} else {



					$count++;
				}
			}
			$this->cache_file('count', $count);
		}
		return intval($count);
	}

	/**
	 * @param string $string The string to search for
	 * @param array $array The array to search
	 * @return bool True if $string matches any elements in $array
	 */
	public static function match_in_array($string, $array) {
		$regex = array();
		static $replace = array (

			'\*' => '[^\/]*',
			'\+' => '[^\/]+',
			'\?' => '[^\/]?');
		foreach ($array as $m) $regex[] .= preg_quote(Item::get_basename($m), '/');
		$regex = '/^('.strtr(implode('|', $regex), $replace).')$/i';
		if ($string === null) return $regex;
		return preg_match($regex, Item::get_basename($string));




	}

	/**
	 * @param string $t The file or folder name
	 * @param bool $is_file
	 * @return bool True if $t is listed as a hidden file
	 */
	public static function is_hidden($t, $is_file = true) {
		global $you, $hidden_files, $show_only_these_files;
		if ($t == '.' || $t == '') return true;
		if ($you -> level >= ADMIN) return false; //allow admins to view hidden files
		if (count($show_only_these_files)) {
			if (!is_bool($is_file)) $is_file = !$is_file->is_dir($t);
			if ($is_file) {
				if (self::$show_only_these_files === null) {
					self::$show_only_these_files = self::match_in_array(null, $show_only_these_files);
				}
				return !preg_match(self::$show_only_these_files, $t);
			}
		}
		if (self::$hidden_files === null) {
			self::$hidden_files = self::match_in_array(null, $hidden_files);
		}
		return preg_match(self::$hidden_files, $t);
	}
	private static $show_only_these_files = null;
	private static $hidden_files = null;

	/**
	 * @param string $var The key to look for
	 * @return mixed The data stored at the key
	 */
	public function __get($var) {
		if ($var == 'list_count') return count($this->contents);
		if (isset($this -> $var)) {

			return $this -> $var;
		}
		throw new ExceptionDisplay('Variable <em>'.Url::html_output($var).'</em> not set in DirectoryList class.');

	}

	/**
	 * @param string $path
	 */
	public function __construct($path) {

		$path = Item::make_sure_slash($path);
		if (!@is_dir($path)) {
			throw new ExceptionDisplay('Directory <em>'.Url::html_output($path).'</em> does not exist.');


		}
		$temp_list = @scandir($path);
		if ($temp_list === false) {
			throw new ExceptionDisplay('Error reading from directory <em>'.Url::html_output($path).'</em>.');
		}
		$this->dir_name = $path;
		$this->contents = $temp_list;
		$contents = array();
		foreach ($temp_list as $t) {
			if (!self::is_hidden($t, $this)) {
				$contents[] = $t;
			}
		}
		$this->contents = $contents;
		$this->i = 0;
	}
}

