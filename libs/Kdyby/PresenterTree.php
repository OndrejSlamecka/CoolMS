<?php

namespace Kdyby;

use Nette;
use Nette\Caching\Cache;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <hosiplan@kdyby.org>
 */
class PresenterTree extends Nette\Object
{
	const ALL = TRUE;
	const DIRECT = FALSE;
//	const TREE = 'tree'; // what does this mean? bad memmory :(


	/** @var Nette\Caching\Cache */
	private $cache;
        
	/** @var Nette\DI\IContainer */
	private $context;


	public function __construct(Nette\DI\IContainer $context)
	{
		$this->context = $context;
                
		$cache = $this->getCache();
		if (!$this->isActual($cache)) {
			$cache->save('presenters', $this->buildPresenterTree());
			$cache->save('modules', $this->buildModuleTree($cache['presenters']));
			$cache->save('actions', $this->buildActionTree($cache['presenters']));
			$this->isActual(TRUE);
		}
	}



	/**
	 * @param Nette\Caching\Cache|bool $cache
	 * @return bool
	 */
	private function isActual($cache = NULL)
	{
		$classes = $this->getRobotLoader()->getIndexedClasses();
		$hash = md5(serialize($classes));

		if ($cache === TRUE) {
			return $this->getCache()->save('hash', $hash);
		}

		if ($cache['hash'] != $hash) {
			return FALSE;
		}

		return TRUE;
	}



	/**
	 * @return array
	 */
	private function buildPresenterTree()
	{
		$classes = array_keys($this->getRobotLoader()->getIndexedClasses());
		$tree = array();

		foreach ($i = new \RegexIterator(new \ArrayIterator($classes), "~.*Presenter$~") as $class) {
			$nettePath = Strings::split(substr($class, 0, -9), '~Module\\\\~i');
			$presenter = array_pop($nettePath);
                        
			$module = strlen($module = $this->formatNettePath($nettePath)) ? substr($module, 1) : NULL;
			$presenterInfo = new PresenterInfo($presenter, $module, $class);
                        
                        if( $class === 'NetteModule\MicroPresenter' )
                                continue;

                        $ref = $presenterInfo->getPresenterReflection();  
			if (!$ref->isAbstract() && $presenterInfo->isPublic() ) {
				$t =& $tree['byModule'];
				foreach ($nettePath as $step) {
					$t[$step] = isset($t[$step]) ? $t[$step] : array();
					$t =& $t[$step];
				}

				$t[$presenter] = $presenterInfo;

				$steps = array();
				foreach ($nettePath as $step) {
					$steps[] = $step;
					$module = substr($this->formatNettePath($steps), 1);
					$relative = substr($this->formatNettePath(array_diff($nettePath, $steps), $presenter), 1);

					$tree['all'][NULL][':'.$module.':'.$relative] = $presenterInfo;
					$tree['all'][$module][$relative] = $presenterInfo;
				}
			}
		}

		ksort($tree['all']);

		return $tree;
	}



	/**
	 * @return array
	 */
	private function buildModuleTree($presenters)
	{
		$tree = array();

		$modules = array();
		foreach ($presenters['all'][NULL] as $fullPath => $presenter) {
			if (!in_array($presenter->module, $modules)) {
				$modules[] = $presenter->module;
			}
		}

		foreach ($modules as $module) {
			$nettePath = explode(':', $module);
			$module = array_pop($nettePath);

			$t =& $tree['byModule'];
			foreach ($nettePath as $step) {
				$t[$step] = isset($t[$step]) ? $t[$step] : array();
				$t =& $t[$step];
			}

			$t = is_array($t) ? $t : array();
			$t[] = $module;
		}

		return $tree;
	}



	/**
	 * @return array
	 */
	private function buildActionTree($presenters)
	{
		$tree = array();

		foreach ($presenters['all'][NULL] as $fullPath => $presenter) {
			$ref = $presenter->getPresenterReflection();
                        
			$presenterInstance = new $presenter->presenterClass($this->context);
			$templateViewPattern = $presenterInstance->formatTemplateFiles(substr($fullPath, 1), '*');

			$views = array();
			foreach ($templateViewPattern as $pattern) {
				$filePattern = Strings::split(basename($pattern), '~\*~');
				if (is_dir(dirname($pattern))) {
					foreach (Nette\Utils\Finder::findFiles(basename($pattern))->in(dirname($pattern)) as $view) {
						$views[] = Strings::replace($view->getFilename(), array(
							'~^'.preg_quote($filePattern[0]).'~' => '',
							'~'.preg_quote($filePattern[1]).'$~' => ''
						));
					}
				}
			}

			$actions = array();
			foreach ($views as $view) {
				$actions[$view] = $fullPath.':'.lcfirst($view);
			}

			$methods = array_map(function($method) {
				return $method->name;
			}, $ref->getMethods( \Nette\Reflection\Method::IS_PUBLIC));

			$methods = array_filter($methods, function($method){
				return in_array(substr($method, 0, 6), array('action', 'render'));
			});

			$allowed = array();
			foreach ($methods as $method) {
				$method = $ref->getMethod($method);
				$action = lcfirst(substr($method->name, 6));

				if (!$method->hasAnnotation('hideInTree')) {
					if (!isset($allowed[$action])) {
						$allowed[$action] = $fullPath.':'.$action;
					}

				} else {
					$allowed[$action] = FALSE;
				}
			}

			$actions = array_filter(array_merge($actions, $allowed), function ($action) { return (bool)$action; });

			if ($actions) {
				$tree['byPresenterClass'][$presenter->presenterClass] = array_flip($actions);

				$t =& $tree['byModule'];
				foreach (Strings::split($presenter->module, '~:~') as $step) {
					$t[$step] = isset($t[$step]) ? $t[$step] : array();
					$t =& $t[$step];
				}

				$t[$presenter->name] = array_flip($actions);
			}
		}

		return $tree;
	}



	/**
	 * @param Strings $nettePath
	 * @param bool $all
	 * @return array
	 */
	public function getPresenters($nettePath = NULL, $all = FALSE)
	{       
		if ($all === FALSE && $nettePath === NULL) {                        
			return isset($this->cache['presenters']['all']['']) ? $this->cache['presenters']['all'][''] : NULL;
		}
                
                $nettePath = trim($nettePath, ':');

		$tree = $this->cache['presenters']['byModule'];
		foreach (Strings::split($nettePath, '~:~') as $step) {
			if (!isset($tree[$step])) {
				return NULL;
			}

			$tree =& $tree[$step];
		}

		return array_filter($tree, function($item) { return !is_array($item); });
	}



	/**
	 * @param Kdyby\PresenterInfo $presenter
	 * @return array
	 */
	public function getPresenterActions(PresenterInfo $presenter)
	{
		return $this->cache['actions']['byPresenterClass'][$presenter->getPresenterClass()];
	}



	/**
	 * @param Strings $nettePath
	 * @return array
	 */
	public function getActions($nettePath)
	{
		$nettePath = trim($nettePath, ':');

		if (!$nettePath) {
			return NULL;
		}

		$presenters = array();
		$tree = $this->cache['actions']['byModule'];
		foreach (Strings::split($nettePath, '~:~') as $step) {
			if (!isset($tree[$step])) {
				return NULL;
			}

			$tree =& $tree[$step];
		}

		return array_filter($tree, function($item) { return !is_array($item); });
	}



	/**
	 * @param Strings $nettePath
	 * @param bool $all
	 * @return array
	 */
	public function getModules($nettePath = NULL)
	{
		$nettePath = trim($nettePath, ':');

		if (!$nettePath) {
			return array_filter($this->cache['modules']['byModule'], function($item) { return !is_array($item); });
		}

		$presenters = array();
		$tree = $this->cache['modules']['byModule'];
		foreach (Strings::split($nettePath, '~:~') as $step) {
			if (!isset($tree[$step])) {
				return NULL;
			}

			$tree =& $tree[$step];
		}

		return array_filter($tree, function($item) { return !is_array($item); });
	}



	/**
	 * @param array $steps
	 * @param Strings $presenter
	 * @return Strings
	 */
	private function formatNettePath($steps, $presenter = NULL)
	{
		return "" . ($steps ? ':'.implode(':', $steps) : NULL) . ($presenter ? ':'.$presenter : NULL);
	}



	/**
	 * @return Nette\Caching\Cache
	 */
	private function getCache()
	{
		if ($this->cache === NULL) {
			$this->cache = Nette\Environment::getCache("Kdyby.Presenter.Tree");
		}

		return $this->cache;
	}



	/** 
	 * @return Nette\Loaders\RobotLoader
	 */
	private function getRobotLoader()
	{
		return $this->getContext()->getService("robotLoader");
	}



	/**
	 * @return Nette\Context
	 */
	public function getContext()
	{
		return $this->context;
	}  


	/**
	 * @return Kdyby\PresenterTree
	 */
	public static function createPresenterTree()
	{
		return new static;
	}

}