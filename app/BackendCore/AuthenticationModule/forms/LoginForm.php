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

class LoginForm extends \Coolms\Form
{

	public function setup()
	{
		$this->getElementPrototype()->id("LoginForm");

		$this->addText('email', 'Email');
		$this->addPassword('password', 'Password');
		$this->addSubmit('login', 'Log in');
		$this['login']->getControlPrototype()->class('big');

		// Add CSRF protection
		$this->addProtection('Please send the form again, protection period expired.');

		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		try {
			$this->presenter->getUser()->setExpiration('+ 14 days', FALSE);
			// Try to authenticate
			$this->presenter->getUser()->login($form['email']->getValue(), $form['password']->getValue());

			$this->presenter->redirect(':Dashboard:Backend:');
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

}