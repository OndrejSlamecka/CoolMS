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

use \Nette\Application\UI\Form;
use Backend\Authenticator;

/**
 * Class responsible for handling all logged users' requests
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{

	/**
	 * Don't call without setting onSuccess
	 * @param type $name
	 * @return \Application\Form
	 */
	public function createComponentConfirmIdentityForm($name)
	{
		$form = new \Application\Form($this, $name);
		$form->addHidden('id');
		$form->addPassword('password', 'Password');
		$form->addSubmit('send', 'Confirm');
		return $form;
	}

	/* -------------------------- EMAIL CHANGE ------------------------------ */

	// Users can only change their own emails

	public function createComponentChangeEmailForm($name)
	{
		return new ChangeEmailForm($this->repositories->User);
	}

	/* -------------------------- DELETING USER ----------------------------- */

	// Only supreme admin can delete user

	public function actionConfirmDelete($id)
	{
		$this->template->user = null;

		if ($this->getUser()->isInRole('admin')) {
			$users = $this->repositories->User;
			$this->template->user = $users->find(array('id' => $id))->fetch();
			$this['confirmIdentityForm']->setDefaults(array('id' => $id));
			$this['confirmIdentityForm']->onSuccess[] = array($this, 'confirmDeleteFormSuccess');
		}
	}

	public function confirmDeleteFormSuccess($form)
	{
		$user = $this->getUser();
		if (!$user->getAuthenticator()->authenticateAdmin($user, $form['password']->getValue())) {
			$this->flashMessage('Password verification was not successful.');
		} else {
			$user_to_delete_id = $form['id']->getValue();
			if ($user_to_delete_id != $user->getId()) {

				$users = $this->repositories->User;
				$users->delete(array('id' => $user_to_delete_id));

				$this->flashMessage('User was deleted.');
			} else {
				$this->flashMessage('I\'m curious why are people trying to delete their own account… seriously, tell me.');
			}
		}

		$this->redirect('default');
	}

	/* ------------------------ CHANGE ADMINISTRATOR ------------------------ */

	public function actionMakeAdmin($id)
	{
		if ($this->getUser()->isInRole('admin')) {
			$users = $this->repositories->User;

			$user = $users->find(array('id' => $id))->fetch();
			$admin = $users->find(array('id' => $this->getUser()->getIdentity()->getId()))->fetch();

			if ($user) {
				$user = $user->toArray();
				$admin = $admin->toArray();

				$admin['role'] = 'user';
				$user['role'] = 'admin';

				try {
					$users->save($admin, 'id');
					$users->save($user, 'id');
					$this->flashMessage('Supreme administrator is now ' . $user['email'] . '. You have to re-login now.');
				} catch (Exception $e) {
					$this->flashMessage('Supreme administrator change was not successful.');
				}
				$this->redirect('Authentication:logout');
			}
		}
	}

	/* --------------------------- DEFAULT - list --------------------------- */

	public function renderDefault()
	{
		$this->template->users = $user = $this->repositories->User->find();
	}

	/* --------------------------- CREATING USER ---------------------------- */

	public function createComponentNewUserForm($name)
	{
		return new NewUserForm($this->repositories->User);
	}

	/* ---------------------- EDITING, RENDERING USER ----------------------- */

	public function renderProfile($id)
	{
		$users = $this->repositories->User;

		$user = $users->find(array('id' => $id))->fetch();

		$user = $user->toArray();
		unset($user['password']);
		$this['userForm']->setDefaults($user);
		$this->template->user = $user;

		$loggedUser = $this->getUser();
		$this->template->hasEditingRight = ($loggedUser->isInRole('admin') || $loggedUser->getIdentity()->getId() == $user['id']);
	}

	public function createComponentUserForm($name)
	{
		$form = new \Application\Form($this, $name);

		$form->addHidden('id');
		/* $form->addText('email','Email')
		  ->addRule(Form::EMAIL, 'Email is not correct.'); */
		$form->addPassword('password', 'Password');

		$form->addText('name', 'Name');

		$form->addSubmit('save', 'Save');
		$form->onSuccess[] = array($this, 'userFormSuccess');
		return $form;
	}

	public function userFormSuccess($form)
	{
		$form = $form->getValues();

		$hasEditingRight = ($this->getUser()->isInRole('admin') || $this->getUser()->getIdentity()->getId() == $form['id']);

		if ($hasEditingRight) {

			unset($form['email']); // Chaning an email is not possible for now, see /development_notes.txt

			if ($form['password'] === "")
				unset($form['password']);
			else
				$form['password'] = Authenticator::calculateHash($form['password'], $this->getUser()->getIdentity()->salt);

			$users = $this->repositories->User;
			$user = $users->find(array('id' => $form['id']))->fetch()->toArray();
			foreach ($form as $key => $val) {
				$user[$key] = $val;
			}

			try {
				$users->save($user, 'id');
				$this->flashMessage('User data changed.');
			} catch (Exception $e) {
				$this->flashMessage('User data changed was not successful.');
			}
			$this->redirect('default');
		}
	}

}
