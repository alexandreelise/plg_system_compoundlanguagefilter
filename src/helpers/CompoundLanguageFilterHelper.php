<?php
/**
 * CompoundLanguageFilterHelper
 * Helper class to offload plugin functionnality
 *
 *
 * @version       1.0.0
 * @package       CompoundLanguageFilterHelper
 * @author        Alexandre ELISÉ <contact@alexandre-elise.fr>
 * @copyright (c) 2009-2021 . Alexandre ELISÉ . Tous droits réservés.
 * @license       GPL-2.0-and-later GNU General Public License v2.0 or later
 * @link          https://coderparlerpartager.fr
 */

abstract class CompoundLanguageFilterHelper
{
	/**
	 * Add link property to object in array if it does not exists yet
	 * Used in mod_languages to change url when changing language
	 *
	 * @param   array  $objectList
	 *
	 * @return array
	 */
	public static function addExtraData(array $objectList)
	{
		$output = $objectList;
		
		foreach ($output as &$item)
		{
			if (is_object($item))
			{
				if (!isset($item->link))
				{
					$item->link = '';
				}
			}
			elseif (is_array($item) && (count($item) >= 1))
			{
				$item = (object) $item;
				foreach ($item as &$subItem)
				{
					$subItem = (object) $subItem;
					
					if (!isset($subItem->link))
					{
						$subItem->link = '';
					}
				}
				
			}
		}
		
		return $output;
	}
}
