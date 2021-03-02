<?php
/**
 * ModLanguagesHelper based on core Joomla! ModLanguagesHelper
 *
 * @version       1.0.0
 * @package       ModLanguagesHelper
 * @author        Alexandre ELISÉ <contact@alexandre-elise.fr>
 * @copyright (c) 2009-2021 . Alexandre ELISÉ . Tous droits réservés.
 * @license       GPL-2.0-and-later GNU General Public License v2.0 or later
 * @link          https://coderparlerpartager.fr
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

/**
 * Helper for mod_languages
 *
 * @since  1.6
 */
abstract class ModLanguagesHelper
{
	/**
	 * Gets a list of available languages
	 *
	 * @param   \Joomla\Registry\Registry  &$params  module params
	 *
	 * @return  array
	 */
	public static function getList(&$params)
	{
		$user      = JFactory::getUser();
		$lang      = JFactory::getLanguage();
		$languages = JLanguageHelper::getLanguages();
		$app       = JFactory::getApplication();
		$menu      = $app->getMenu();
		$active    = $menu->getActive();
		
		$compoundLanguageFilterParams = new Joomla\Registry\Registry(PluginHelper::getPlugin('system', 'compoundlanguagefilter')->params);
		
		// include offloaded method of helper
		JLoader::register('CompoundLanguageFilterHelper', JPATH_PLUGINS . '/system/compoundlanguagefilter/helpers/CompoundLanguageFilterHelper.php', true);
		
		if ($compoundLanguageFilterParams !== null)
		{
			
			$getCompoundLanguages = $compoundLanguageFilterParams->get('compound_languages');
			
			$registryLanguages         = new Joomla\Registry\Registry($languages);
			$registryCompoundLanguages = new Joomla\Registry\Registry($getCompoundLanguages);
			$registryLanguages->merge($registryCompoundLanguages, true);
			
			$languages = CompoundLanguageFilterHelper::addExtraData((array) $registryLanguages->toObject());
		}
		
		// Get menu home items
		$homes      = array();
		$homes['*'] = $menu->getDefault('*');
		
		foreach ($languages as $key => $item)
		{
			$currentLangCode = $item->lang_code;
			
			if (isset($item->source_language) && ($item->source_language !== $item->lang_code))
			{
				$currentLangCode = $item->source_language;
			}
			
			$default = $menu->getDefault($currentLangCode);
			
			
			if ($default && ($default->language === $currentLangCode))
			{
				$homes[$currentLangCode] = $default;
			}
			
		}
		
		
		// Load associations
		$assoc = JLanguageAssociations::isEnabled();
		
		if ($assoc)
		{
			if ($active)
			{
				$associations = MenusHelper::getAssociations($active->id);
			}
			
			// Load component associations
			$option = $app->input->get('option');
			$class  = ucfirst(str_replace('com_', '', $option)) . 'HelperAssociation';
			\JLoader::register($class, JPATH_SITE . '/components/' . $option . '/helpers/association.php');
			
			if (class_exists($class) && is_callable(array($class, 'getAssociations')))
			{
				$cassociations = call_user_func(array($class, 'getAssociations'));
			}
		}
		
		$levels    = $user->getAuthorisedViewLevels();
		$multilang = JLanguageMultilang::isEnabled();
		
		// Filter allowed languages
		foreach ($languages as $i => &$language)
		{
			// Do not display language without authorized access level
			if (isset($language->access) && $language->access && !in_array($language->access, $levels))
			{
				unset($languages[$i]);
			}
			else
			{
				//current language code assigned temporarily
				$currentLangCodeMultiLang = $language->lang_code;
				
				// if source language is defined (one to many relationship) use it
				// eg: es-ES  -> co-CO, ar-AR, cl-CL, ve-VE, etc...
				// otherwise use lang_code of the current item we are reading
				if (isset($language->source_language) && ($language->source_language !== $language->lang_code))
				{
					$currentLangCodeMultiLang = $language->source_language;
				}
				
				// use the computed value of $currentLangCodeMultiLang from now on
				// rather than $language->lang_code to make the modified language
				// filter work with custom one to many relationship multilingual system
				
				//for active language we need to compare with lang_code because it is unique to each language and "virtual languages" of the dropdown. Otherwise there would be more than one match. Hence no selection.
				
				// WATCH OUT! lang=es when non-sef and lang=es-ES when sef is enabled
				$uriLang = Factory::getApplication()->input->getCmd('lang');
				
				$language->active = (((int)Factory::getConfig()->get('sef', 0)) === 1) ? ($language->lang_code === $uriLang) : ($language->sef === $uriLang);
				
				// Fetch language rtl
				// If loaded language get from current JLanguage metadata
				if ($language->active)
				{
					$language->rtl = $lang->isRtl();
				}
				// If not loaded language fetch metadata directly for performance
				else
				{
					$languageMetadata = JLanguageHelper::getMetadata($currentLangCodeMultiLang);
					$language->rtl    = $languageMetadata['rtl'];
				}
				
				
				if ($multilang)
				{
					if (isset($cassociations[$currentLangCodeMultiLang]))
					{
						$language->link = JRoute::_($cassociations[$currentLangCodeMultiLang] . '&lang=' . $language->sef);
					}
					elseif (isset($associations[$currentLangCodeMultiLang]) && $menu->getItem($associations[$currentLangCodeMultiLang]))
					{
						$itemid         = $associations[$currentLangCodeMultiLang];
						$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
					}
					elseif ($active && $active->language == '*')
					{
						$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $active->id);
					}
					else
					{
						if ($language->active)
						{
							$language->link = JUri::getInstance()->toString(array('path', 'query'));
						}
						else
						{
							$itemid         = isset($homes[$currentLangCodeMultiLang]) ? $homes[$currentLangCodeMultiLang]->id : $homes['*']->id;
							$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
						}
					}
				}
				else
				{
					$language->link = JRoute::_('&Itemid=' . $homes['*']->id);
				}
			}
		}
		
		return $languages;
	}
}
