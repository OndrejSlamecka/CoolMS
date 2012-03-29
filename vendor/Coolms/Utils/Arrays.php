<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Coolms\Utils;

class Arrays
{

	public static function mapRecursive($callback, $array)
	{
		$result = array();
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$result[$key] = self::mapRecursive($callback, $value); // {__FUNCTION__}?
			} else {
				$result[$key] = $callback($value);
			}
		}

		return $result;
	}

}
