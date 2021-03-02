<?php
/**
 * Compound Language Filter
 *
 * @version       1.0.0
 * @package       compoundlanguagefilter
 * @author        Alexandre ELISÉ <contact@alexandre-elise.fr>
 * @copyright (c) 2009-2021 . Alexandre ELISÉ . Tous droits réservés.
 * @license       GPL-2.0-and-later GNU General Public License v2.0 or later
 * @link          https://coderparlerpartager.fr
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

JLoader::register('PlgSystemLanguageFilter', JPATH_PLUGINS . '/system/languagefilter/languagefilter.php');

/**
 * Class PlgSystemCompoundLanguageFilter
 */
class PlgSystemCompoundLanguageFilter extends PlgSystemLanguageFilter
{
	/**
	 * The routing mode.
	 *
	 * @var    boolean
	 * @since  2.5
	 */
	protected $mode_sef;
	
	/**
	 * Available languages by sef.
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $sefs;
	
	/**
	 * Available languages by language codes.
	 *
	 * @var    array
	 * @since  2.5
	 */
	protected $lang_codes;
	
	/**
	 * The current language code.
	 *
	 * @var    string
	 * @since  3.4.2
	 */
	protected $current_lang;
	
	/**
	 * The default language code.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $default_lang;
	
	/**
	 * The logged user language code.
	 *
	 * @var    string
	 * @since  3.3.1
	 */
	private $user_lang_code;
	
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.3
	 */
	protected $app;
	
	
	/**
	 * What you configured in this plugin params
	 * for example:
	 * source language: German (de)
	 * destination languages: Austria (at), Australia (au)
	 *
	 * @var \stdClass $mapping_compound_languages
	 */
	private $mapping_compound_languages;
	
	
	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array    $config   An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		// load com_languages lang files as it used in this plugin params
		$factory_language = Factory::getLanguage();
		$factory_language->load('com_languages', JPATH_ADMINISTRATOR, null, true, true);
		
		// include offloaded method of helper
		JLoader::register('CompoundLanguageFilterHelper', JPATH_PLUGINS . '/system/compoundlanguagefilter/helpers/CompoundLanguageFilterHelper.php', true);
		
		
		parent::__construct($subject, $config);
		
		$this->app = JFactory::getApplication();
		
		// store custom language mappings for later use
		$this->mapping_compound_languages = $this->params->get('compound_languages');
		
		// Setup language data.
		$this->mode_sef = $this->app->get('sef', 0);
		
		$getLanguagesSef = JLanguageHelper::getLanguages('sef');
		
		$getCompoundLanguagesSef = ArrayHelper::pivot((array) $this->mapping_compound_languages, 'sef');
		
		$getLanguagesLangCode         = JLanguageHelper::getLanguages('lang_code');
		$getCompoundLanguagesLangCode = ArrayHelper::pivot((array) $this->mapping_compound_languages, 'lang_code');
		
		// merge two assoc arrays using native joomla regitries
		$registryLanguagesSef         = new Joomla\Registry\Registry($getLanguagesSef);
		$registryCompoundLanguagesSef = new Joomla\Registry\Registry($getCompoundLanguagesSef);
		$registryLanguagesSef->merge($registryCompoundLanguagesSef, true);
		
		$registryLanguagesLangCode         = new Joomla\Registry\Registry($getLanguagesLangCode);
		$registryCompoundLanguagesLangCode = new Joomla\Registry\Registry($getCompoundLanguagesLangCode);
		$registryLanguagesLangCode->merge($registryCompoundLanguagesLangCode, true);
		
		$objectListLanguageSef = (array) $registryLanguagesSef->toObject();
		
		$objectListLanguageCode = (array) $registryLanguagesLangCode->toObject();
		
		$this->sefs       = CompoundLanguageFilterHelper::addExtraData($objectListLanguageSef);
		$this->lang_codes = CompoundLanguageFilterHelper::addExtraData($objectListLanguageCode);
		
		
		$this->default_lang = (string) JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		
		// If language filter plugin is executed in a site page.
		if ($this->app->isClient('site'))
		{
			$levels = JFactory::getUser()->getAuthorisedViewLevels();
			
			foreach ($this->sefs as $sef => $language)
			{
				
				// @todo: In Joomla 2.5.4 and earlier access wasn't set. Non modified Content Languages got 0 as access value
				
				if ($language->access && !in_array($language->access, $levels))
				{
					unset($this->lang_codes[$language->lang_code], $this->sefs[$language->sef]);
				}
			}
		}
		// If language filter plugin is executed in an admin page (ex: JRoute site).
		else
		{
			// Set current language to default site language, fallback to en-GB if there is no content language for the default site language.
			$this->current_lang = isset($this->lang_codes[$this->default_lang]) ? $this->default_lang : 'en-GB';
			
		}
	}
	
	public function onBeforeRender()
	{
		if ($this->app->isClient('site'))
		{
			// Include the languages functions only once
			JLoader::register('ModLanguagesHelper', JPATH_PLUGINS . '/system/compoundlanguagefilter/helpers/ModLanguagesHelper.php', true);
			
			//force render modified version of the language list in mod_lanquages
			ModLanguagesHelper::getList(new Registry(ModuleHelper::getModule('mod_languages')->params));
		}
	}
	
	
	/**
	 * Set the language cookie
	 *
	 * @param   string  $languageCode  The language code for which we want to set the cookie
	 *
	 * @return  void
	 *
	 * @since   3.4.2
	 */
	private function setLanguageCookie($languageCode)
	{
		// If is set to use language cookie for a year in plugin params, save the user language in a new cookie.
		if ((int) $this->params->get('lang_cookie', 0) === 1)
		{
			// Create a cookie with one year lifetime.
			$this->app->input->cookie->set(
				JApplicationHelper::getHash('language'),
				$languageCode,
				time() + 365 * 86400,
				$this->app->get('cookie_path', '/'),
				$this->app->get('cookie_domain', ''),
				$this->app->isHttpsForced(),
				true
			);
		}
		// If not, set the user language in the session (that is already saved in a cookie).
		else
		{
			JFactory::getSession()->set('plg_system_languagefilter.language', $languageCode);
		}
	}
	
	/**
	 * Get the language cookie
	 *
	 * @return  string
	 *
	 * @since   3.4.2
	 */
	private function getLanguageCookie()
	{
		// Is is set to use a year language cookie in plugin params, get the user language from the cookie.
		if ((int) $this->params->get('lang_cookie', 0) === 1)
		{
			$languageCode = $this->app->input->cookie->get(JApplicationHelper::getHash('language'));
		}
		// Else get the user language from the session.
		else
		{
			$languageCode = JFactory::getSession()->get('plg_system_languagefilter.language');
		}
		
		// Let's be sure we got a valid language code. Fallback to null.
		if (!array_key_exists($languageCode, $this->lang_codes))
		{
			$languageCode = null;
		}
		
		return $languageCode;
	}
	
	/**
	 * @param   \JRouter  $router
	 * @param   \JUri     $uri
	 *
	 * @return array|false[]|null[]|string[]|void
	 */
	public function parseRule(&$router, &$uri)
	{
		// Did we find the current and existing language yet?
		$found = false;
		
		// Are we in SEF mode or not?
		if ($this->mode_sef)
		{
			$path  = $uri->getPath();
			$parts = explode('/', $path);
			
			$sef = StringHelper::strtolower($parts[0]);
			
			// Do we have a URL Language Code ?
			if (!isset($this->sefs[$sef]))
			{
				// Check if remove default URL language code is set
				if ($this->params->get('remove_default_prefix', 0))
				{
					if ($parts[0])
					{
						// We load a default site language page
						$lang_code = $this->default_lang;
					}
					else
					{
						// We check for an existing language cookie
						$lang_code = $this->getLanguageCookie();
					}
				}
				else
				{
					$lang_code = $this->getLanguageCookie();
				}
				
				// No language code. Try using browser settings or default site language
				if (!$lang_code && $this->params->get('detect_browser', 0) == 1)
				{
					$lang_code = JLanguageHelper::detectLanguage();
				}
				
				if (!$lang_code)
				{
					$lang_code = $this->default_lang;
				}
				
				if ($lang_code === $this->default_lang && $this->params->get('remove_default_prefix', 0))
				{
					$found = true;
				}
			}
			else
			{
				// We found our language
				$found     = true;
				$lang_code = $this->sefs[$sef]->source_language ?: $this->sefs[$sef]->lang_code;
				
				// If we found our language, but its the default language and we don't want a prefix for that, we are on a wrong URL.
				// Or we try to change the language back to the default language. We need a redirect to the proper URL for the default language.
				if ($lang_code === $this->default_lang && $this->params->get('remove_default_prefix', 0))
				{
					// Create a cookie.
					$this->setLanguageCookie($lang_code);
					
					$found = false;
					array_shift($parts);
					$path = implode('/', $parts);
				}
				
				// We have found our language and the first part of our URL is the language prefix
				if ($found)
				{
					array_shift($parts);
					
					// Empty parts array when "index.php" is the only part left.
					if (count($parts) === 1 && $parts[0] === 'index.php')
					{
						$parts = array();
					}
					
					$uri->setPath(implode('/', $parts));
				}
			}
		}
		// We are not in SEF mode
		else
		{
			$lang_code = $this->getLanguageCookie();
			
			if (!$lang_code && $this->params->get('detect_browser', 1))
			{
				$lang_code = JLanguageHelper::detectLanguage();
			}
			
			if (!isset($this->sefs[$lang_code]))
			{
				$lang_code = $this->default_lang;
			}
		}
		
		$lang = $uri->getVar('lang', $lang_code);
		
		if ($this->mode_sef && isset($this->lang_codes[$lang]))
		{
			// We found our language
			$found     = true;
			$lang_code = $this->lang_codes[$lang]->lang_code;
		}
		elseif (!$this->mode_sef && isset($this->sefs[$lang]))
		{
			// We found our language
			$found     = true;
			$lang_code = $this->sefs[$lang]->lang_code;
		}
		
		// We are called via POST or the nolangfilter url parameter was set. We don't care about the language
		// and simply set the default language as our current language.
		if ($this->app->input->getMethod() === 'POST'
			|| $this->app->input->get('nolangfilter', 0) == 1
			|| count($this->app->input->post) > 0
			|| count($this->app->input->files) > 0)
		{
			$found = true;
			
			if (!isset($lang_code))
			{
				$lang_code = $this->getLanguageCookie();
			}
			
			if (!$lang_code && $this->params->get('detect_browser', 1))
			{
				$lang_code = JLanguageHelper::detectLanguage();
			}
			
			if (!isset($this->lang_codes[$lang_code]))
			{
				$lang_code = $this->default_lang;
			}
		}
		
		// We have not found the language and thus need to redirect
		if (!$found)
		{
			// Lets find the default language for this user
			if (!isset($lang_code) || !isset($this->lang_codes[$lang_code]))
			{
				$lang_code = false;
				
				if ($this->params->get('detect_browser', 1))
				{
					$lang_code = JLanguageHelper::detectLanguage();
					
					if (!isset($this->lang_codes[$lang_code]))
					{
						$lang_code = false;
					}
				}
				
				if (!$lang_code)
				{
					$lang_code = $this->default_lang;
				}
			}
			
			if ($this->mode_sef)
			{
				// Use the current language sef or the default one.
				if ($lang_code !== $this->default_lang
					|| !$this->params->get('remove_default_prefix', 0))
				{
					$path = $this->lang_codes[$lang_code]->sef . '/' . $path;
				}
				
				$uri->setPath($path);
				
				if (!$this->app->get('sef_rewrite'))
				{
					$uri->setPath('index.php/' . $uri->getPath());
				}
				
				$redirectUri = $uri->base() . $uri->toString(array('path', 'query', 'fragment'));
			}
			else
			{
				$uri->setVar('lang', $this->lang_codes[$lang_code]->sef);
				$redirectUri = $uri->base() . 'index.php?' . $uri->getQuery();
			}
			
			// Set redirect HTTP code to "302 Found".
			$redirectHttpCode = 302;
			
			// If selected language is the default language redirect code is "301 Moved Permanently".
			if ($lang_code === $this->default_lang)
			{
				$redirectHttpCode = 301;
				
				// We cannot cache this redirect in browser. 301 is cachable by default so we need to force to not cache it in browsers.
				$this->app->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
				$this->app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
				$this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
				$this->app->setHeader('Pragma', 'no-cache');
				$this->app->sendHeaders();
			}
			
			// Redirect to language.
			$this->app->redirect($redirectUri, $redirectHttpCode);
		}
		
		// We have found our language and now need to set the cookie and the language value in our system
		$array              = array('lang' => $lang_code);
		$this->current_lang = $lang_code;
		
		// Set the request var.
		$this->app->input->set('language', $lang_code);
		$this->app->set('language', $lang_code);
		$language = JFactory::getLanguage();
		
		if ($language->getTag() !== $lang_code)
		{
			$language_new = JLanguage::getInstance($lang_code, (bool) $this->app->get('debug_lang'));
			
			foreach ($language->getPaths() as $extension => $files)
			{
				if (strpos($extension, 'plg_system') !== false)
				{
					$extension_name = substr($extension, 11);
					
					$language_new->load($extension, JPATH_ADMINISTRATOR)
					|| $language_new->load($extension, JPATH_PLUGINS . '/system/' . $extension_name);
					
					continue;
				}
				
				$language_new->load($extension);
			}
			
			JFactory::$language = $language_new;
			$this->app->loadLanguage($language_new);
		}
		
		// Create a cookie.
		if ($this->getLanguageCookie() !== $lang_code)
		{
			$this->setLanguageCookie($lang_code);
		}
		
		
		return empty($array) ? parent::parseRule($router, $uri) : $array; // TODO: Change the autogenerated stub
	}
	
	
}
