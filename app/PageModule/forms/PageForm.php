<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace PageModule;

use Nette\Forms\Form;

class PageForm extends \Coolms\Form
{
	/** @var \NDBF\Repository */
	private $repository;

	/** @var \Nette\Http\SessionSection */
	private $sessionSection;

	public function __construct(\NDBF\Repository $repository, \Nette\Http\SessionSection $sessionSection)
	{
		parent::__construct();
		$this->repository = $repository;
		$this->sessionSection = $sessionSection;
	}

	public function setup()
	{
		$this->getElementPrototype()->class('savable');

		$this->addHidden('id');

		$this->addText('name_webalized', 'Name in URL', 30)
				->getControlPrototype()->class('name_webalized');

		$this->addText('name', 'Name')
				->getControlPrototype()->class('name_webalized_source');

		$this->addTextarea('text', 'Text', 60, 30);
		$this['text']->getControlPrototype()->class('wysiwyg');

		$this->addText('template', 'Template', 30);

		$this->addSubmit('save', 'Save')
				->getControlPrototype()->class('emphasized');

		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		$page = $form->getValues();

		if ($page['id'] === '')
			$page['id'] = null;
		if ($page['name_webalized'] === '')
			$page['name_webalized'] = \Nette\Utils\Strings::webalize($page['name']);
		else
			$page['name_webalized'] = \Nette\Utils\Strings::webalize($page['name_webalized']); // Never trust user input

		$pages = $this->repository;

		try {
			if ($this->presenter->isAjax()) {
				$this->sessionSection->autosave = $page;
			} else {
				unset($this->sessionSection->autosave);
				$pages->save($page, 'id');
			}

			$this->parent->flashMessage('Page saved');
		} catch (\Exception $e) {
			$this->parent->flashMessage('Page was not saved. Please try again and then contact the administrator');
		}

		$this->presenter->redirect('default');
	}

}