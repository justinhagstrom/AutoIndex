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

if (!defined('IN_AUTOINDEX') || !IN_AUTOINDEX)
{
	die();
}

/**
 * Subclass of item that specifically represents a directory.
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.1 (June 30, 2004)
 * @package AutoIndex
 */
class DirItem extends Item
{
	/**
	 * @var DirectoryList The list of this directory's contents
	 */
	private $temp_list;
	
	/**
	 * @return string Always returns 'dir', since this is a directory, not a file
	 */
	public function file_ext()
	{
		return 'dir';
	}
	
	/**
	 * @return int The total size in bytes of the folder (recursive)
	 */
	private function dir_size()
	{
		if (!isset($this -> temp_list))
		{
			$this -> temp_list = new DirectoryList($this -> parent_dir . $this -> filename);
		}
		return $this -> temp_list -> size_recursive();
	}
	
	/**
	 * @return int The total number of files in the folder (recursive)
	 */
	public function num_subfiles()
	{
		if (!isset($this -> temp_list))
		{
			$this -> temp_list = new DirectoryList($this -> parent_dir . $this -> filename);
		}
		return $this -> temp_list -> num_files();
	}
	
	/**
	 * @param string $path
	 * @return string The parent directory of $path
	 */
	public static function get_parent_dir($path)
	{
		$path = str_replace('\\', '/', $path);
		while (preg_match('#/$#', $path))
		//remove all slashes from the end
		{
			$path = substr($path, 0, -1);
		}
		$pos = strrpos($path, '/');
		if ($pos === false)
		{
			return '';
		}
		$path = substr($path, 0, $pos + 1);
		return (($path === false) ? '' : $path);
	}
	
	/**
	 * @param string $parent_dir
	 * @param string $filename
	 */
	public function __construct($parent_dir, $filename)
	{
		$filename = self::make_sure_slash($filename);
		parent::__construct($parent_dir, $filename);
		global $config, $subdir;
		$this -> downloads = '&nbsp;';
		if ($filename == '../')
		//link to parent directory
		{
			if ($subdir != '')
			{
				global $words;
				$this -> is_parent_dir = true;
				$this -> filename = $words -> __get('parent directory');
				$this -> icon = (ICON_PATH ? $config -> __get('icon_path')
				. 'back.png' : '');
				$this -> size = new Size(true);
				$this -> link = Url::html_output($_SERVER['PHP_SELF']) . '?dir='
				. Url::translate_uri(self::get_parent_dir($subdir));
				$this -> parent_dir = $this -> new_icon = '';
				$this -> a_time = $this -> m_time = false;
			}
			else
			{
				$this -> is_parent_dir = $this -> filename = false;
			}
		}
		else
		//regular folder
		{
			if (!@is_dir($this -> parent_dir . $filename))
			{
				throw new ExceptionDisplay('Directory <em>'
				. Url::html_output($this -> parent_dir . $filename)
				. '</em> does not exist.');
			}
			$this -> filename = substr($filename, 0, -1);
			$this -> icon = $config -> __get('icon_path') . 'dir.png';
			$this -> link = Url::html_output($_SERVER['PHP_SELF']) . '?dir='
			. Url::translate_uri(substr($this -> parent_dir, strlen($config -> __get('base_dir'))) . $filename);
		}
	}
	
	/**
	 * @param string $var The key to look for
	 * @return mixed The data stored at the key
	 */
	public function __get($var)
	{
		if (isset($this -> $var))
		{
			return $this -> $var;
		}
		if ($var == 'size')
		{
			$this -> size = new Size(SHOW_DIR_SIZE ? $this -> dir_size() : false);
			return $this -> size;
		}
		throw new ExceptionDisplay('Variable <em>' . Url::html_output($var)
		. '</em> not set in DirItem class.');
	}
}

?>