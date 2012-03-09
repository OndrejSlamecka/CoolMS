<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace UserModule;

use Nette\Forms\Form,
	Backend\Authenticator;

class UserForm extends \Coolms\Form
{

	/** @var \NDBF\Repository */
	private $repository;

	public function __construct(\NDBF\Repository $repository)
	{
		parent::__construct();
		$this->repository = $repository;
	}

	public function setup()
	{
		$this->addHidden('id');

		$this->addPassword('password', 'Password');

		$this->addText('name', 'Name');

		$this->addSubmit('save', 'Save');
		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		$form = $form->getValues();

		$hasEditingRight = ($this->presenter->getUser()->isInRole('admin') || $this->presenter->getUser()->getIdentity()->getId() == $form['id']);

		if ($hasEditingRight) {
			unset($form['email']); // Chaning an email is not possible here

			if ($form['password'] === "")
				unset($form['password']);
			else
				$form['password'] = Authenticator::calculateHash($form['password'], $this->presenter->getUser()->getIdentity()->salt);

			$users = $this->repository;
			$user = $users->select()->where('id', $form['id'])->fetch()->toArray();
			foreach ($form as $key => $val) {
				$user[$key] = $val;
			}

			try {
				$users->save($user, 'id');
				$this->presenter->flashMessage('User data changed.');
			} catch (Exception $e) {
				$this->presenter->flashMessage('User data change was not successful.');
			}
			$this->presenter->redirect('default');
		}
	}

}