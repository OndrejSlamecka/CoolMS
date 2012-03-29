<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Coolms;

/**
 * Base class for all application presenters.
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	/** @var Nette\Http\Session */
	protected $sessionSection = null;

	public function startup()
	{
		parent::startup();

		// If this is a module (a colon is contained) create session section
		if ($modulDelimiter = strpos($this->getName(), ':')) {
			$module = substr($this->getName(), 0, $modulDelimiter);
			$this->sessionSection = $this->getSession($module . 'ModuleSession');
		}
	}

	public function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(array('Coolms\Helpers', 'loader'));
		return $template;
	}

	final public function getRepositories()
	{
		return $this->getService('ndbf.repositoryManager');
	}

	/**
	 * Overrides Nette\Application\UI\Presenter method for cases where layout is hard set
	 */
	public function formatLayoutTemplateFiles()
	{
		if (!empty($this->layout) && strpos($this->layout, $this->context->parameters['appDir']) !== false)
			return array($this->layout);
		else
			return parent::formatLayoutTemplateFiles();
	}

	public function getTemplatesFolder()
	{
		$dir = dirname(dirname($this->getReflection()->getFileName()));
		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));

		return "$dir/templates/$presenter";
	}

}
