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

/**
 * Class responsible for handling all not logged users' requests
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{

	public function startup()
	{
		parent::startup();
		$this->setLayout('layout'); // This - default layout - was overriden in BasePresenter
	}

	/* ------------------------------- LOGIN -------------------------------- */

	/**
	 * Login form component factory.
	 */
	protected function createComponentLoginForm()
	{
		return new LoginForm();
	}

	public function renderLogin()
	{
		//dump( Authenticator::hashPassword( 'email', 'password' ) ); // Printing for development purposes
	}

	/* ------------------------------ LOGOUT -------------------------------- */

	public function actionLogout()
	{
		$this->getUser()->logout(TRUE);
		$this->redirect(':Authentication:Backend:login');
	}

	/* ----------------------- REQUEST NEW PASSWORD ------------------------- */

	public function createComponentRequestNewPasswordForm()
	{
		return new RequestNewPasswordForm($this->repositories->User);
	}

	public function actionCreatePassword($token, $newuser = false)
	{
		// Following action: newPasswordSuccess!

		/*
		 * Whether token is right is tested by fetching user by token
		 * It's validity is tested by Authenticator::isTokenValid()
		 *   with token creation time provided from user's row in database
		 */

		// If user hasn't submitted new password (i.e. he comes from email)
		if (!$this['newPassword']->isSubmitted()) {

			$users = $this->repositories->User;
			$user = $users->find(array('token' => $token))->fetch();

			if (!$user) {
				$this->flashMessage('Verification failure. Please check if you have copied the link right.');
				$this->redirect('login');
			}

			$user = $user->toArray();

			if (!Authenticator::isTokenValid($user['token_created'])) {
				$this->flashMessage('Verification failure. You have waited too long before reseting password. Please use "I forgot my password" again.');
				$this->redirect('login');
			}

			// Create new token
			$token = Authenticator::createToken();
			$user['token'] = $token;
			$user['token_created'] = new \DateTime();
			$users->save($user, 'email');

			$token_sess = $this->getSession('token');
			$token_sess->value = $token;
		} else {
			if ($newuser) {
				$userinfo = $this->getSession('userinfo');
				$userinfo->newUser = true;
			}
		}
	}

	public function createComponentNewPassword($name)
	{
		return new NewPasswordForm($this->repositories->User);
	}

}