<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace AuthenticationModule;

use Nette\Forms\Form,
	Backend\Authenticator;

/**
 * Following action: login
 */
class NewPasswordForm extends \Coolms\Form
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
		$this->addPassword('password', 'New password');
		$this->addSubmit('save', 'Save');
		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		$form = $form->getValues();
		$token_sess = $this->getSession('token');
		$token = $token_sess->value;

		$users = $this->repository;
		$user = $users->select()->where('token', $token)->fetch();

		if (!$user || !Authenticator::isTokenValid($user['token_created'])) {
			$this->presenter->flashMessage('Verification failure. Please use "I forgot my password" again.');
			$this->presenter->redirect('login');
		}

		$user = $user->toArray();
		$user['password'] = Authenticator::calculateHash($form['password'], $user['salt']);
		$user['token'] = null;
		$user['token_created'] = null;
		$users->save($user, 'id');

		$this->presenter->flashMessage('Password was set. Now you can log in!');
		$this->presenter->redirect('login');
	}

}
