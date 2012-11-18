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
 * Generates a thumbnail of an image file.
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.0 (May 22, 2004)
 * @package AutoIndex
 */
class Image
{
	/**
	 * @var string Name of the image file
	 */
	private $filename;
	
	/**
	 * @var int The height of the thumbnail to create (width is automatically determined)
	 */
	private $height;
	
	/**
	 * Outputs the jpeg image along with the correct headers so the
	 * browser will display it. The script is then exited.
	 */
	public function __toString()
	{
		$thumbnail_height = $this -> height;
		$file = $this -> filename;
		if (!@is_file($file))
		{
			header('HTTP/1.0 404 Not Found');
			throw new ExceptionDisplay('Image file not found: <em>'
			. Url::html_output($file) . '</em>');
		}
		switch (FileItem::ext($file))
		{
			case 'gif':
			{
				$src = @imagecreatefromgif($file);
				break;
			}
			case 'jpeg':
			case 'jpg':
			case 'jpe':
			{
				$src = @imagecreatefromjpeg($file);
				break;
			}
			case 'png':
			{
				$src = @imagecreatefrompng($file);
				break;
			}
			default:
			{
				throw new ExceptionDisplay('Unsupported file extension.');
			}
		}
		if ($src === false)
		{
			throw new ExceptionDisplay('Unsupported image type.');
		}
		
		header('Content-Type: image/jpeg');
		header('Cache-Control: public, max-age=3600, must-revalidate');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600)
		. ' GMT');
		$src_height = imagesy($src);
		if ($src_height <= $thumbnail_height)
		{
			imagejpeg($src, '', 95);
		}
		else
		{
			$src_width = imagesx($src);
			$thumb_width = $thumbnail_height * ($src_width / $src_height);
			$thumb = imagecreatetruecolor($thumb_width, $thumbnail_height);
			imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumb_width,
				$thumbnail_height, $src_width, $src_height);
			imagejpeg($thumb);
			imagedestroy($thumb);
		}
		imagedestroy($src);
		die();
	}
	
	/**
	 * @param string $file The image file
	 */
	public function __construct($file)
	{
		if (!THUMBNAIL_HEIGHT)
		{
			throw new ExceptionDisplay('Image thumbnailing is turned off.');
		}
		global $config;
		$this -> height = (int)$config -> __get('thumbnail_height');
		$this -> filename = $file;
	}
}

?>