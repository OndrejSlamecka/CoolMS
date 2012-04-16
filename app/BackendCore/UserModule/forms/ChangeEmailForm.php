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

class ChangeEmailForm extends \Coolms\Form
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
		$this->addEmail('email', 'New email')
				->addRule(Form::EMAIL, 'Email has to be in proper format.');
		$this->addPassword('password', 'Confirm password');

		$this->addSubmit('save', 'Change');

		$this->onSuccess[] = callback($this, 'success');
	}

	public function success($form)
	{
		/* Authentication */
		try {
			$loggedUser = $this->presenter->getUser();
			$loggedUser->getAuthenticator()->authenticate(array($loggedUser->getIdentity()->email, $form['password']->getValue()));
		} catch (Nette\Security\AuthenticationException $e) {
			$this->flashMessage('Password verification was not successful.');
			$this->redirect('default');
		}

		/* Email change */
		$users = $this->repository;
		$user = array(
			'id' => $loggedUser->getIdentity()->getId(),
			'email' => $form['email']->getValue(),
		);

		try {
			$loggedUser->getIdentity()->email = $user['email'];
			$users->save($user, 'id');
			$this->presenter->flashMessage('Your email change was successful.');
		} catch (\Nette\InvalidStateException $e) {
			$this->presenter->flashMessage('Your email change was not successful, sorry.');
		}

		$this->presenter->redirect('default');
	}

}
