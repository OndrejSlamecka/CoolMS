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
    /* -------------------------- DELETING USER ----------------------------- */

    public function actionConfirmDelete($id)
    {
        $this->template->user = null;

        if ($this->getUser()->isInRole('admin')) {
            $users = $this->repositories->User;
            $this->template->user = $users->find(array('id' => $id))->fetch();
            $this['confirmDeleteForm']->setDefaults(array('id' => $id));
        }
    }

    public function createComponentConfirmDeleteForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->addHidden('id');
        $form->addPassword('password', 'Password');
        $form->addSubmit('send', 'Confirm');
        $form->onSuccess[] = array($this, 'confirmDeleteFormSuccess');
        return $form;
    }

    public function confirmDeleteFormSuccess($form)
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->isInRole('admin')) {
            $form = $form->getValues();

            $enteredPasswordHash = Authenticator::hashPassword($loggedUser->getIdentity()->data['email'], $form['password']);
            $isRightPassword = $loggedUser->getIdentity()->data['password'] === $enteredPasswordHash;

            if ($isRightPassword) {
                if ($form['id'] != $loggedUser->getId()) {

                    $users = $this->repositories->User;
                    $users->remove(array('id' => $form['id']));

                    $this->flashMessage('User was deleted.');
                } else {
                    $this->flashMessage('I\'m curious why are people trying to delete their own account… seriously, tell me.');
                }
            } else {
                $this->flashMessage('Password verification was not successful.');
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
        $form = new \Application\Form($this, $name);
        $form->addText('email', 'Email')->addRule(Form::EMAIL, 'Email is not in the right format.');
        $form->addSubmit('send', 'Create');
        $form->onSuccess[] = array($this, 'newUserFormSuccess');
        return $form;
    }

    public function newUserFormSuccess($form)
    {
        // Following action: (email, then) Authentication:createPassword
        if ($this->getUser()->isInRole('admin')) {

            $form = $form->getValues();
            $users = $this->repositories->User;

            $user = array();
            $user['email'] = $form['email'];
            $user['role'] = 'user';

            if ($users->find(array('email' => $user['email']))->fetch()) {
                $this->flashMessage('An user with email ' . $user['email'] . ' already exists.');
                $this->redirect('new');
            }

            // Generate some noise just for sure
            $user['password'] = md5(uniqid(rand(10, 19), true));

            // Create token for future verification
            $token = Authenticator::createToken();
            $user['token'] = $token;
            $user['token_created'] = new \DateTime();

            // Prepare email
            $template = new \Nette\Templating\FileTemplate($this->getTemplatesFolder() . "/email/newUser.latte");
            $template->registerFilter(new \Nette\Latte\Engine);

            $template->site = $this->getHttpRequest()->getUrl()->getHostUrl();
            $template->link = $this->link('//:Authentication:Backend:createPassword', array('token' => $token, 'newuser' => 'true'));

            $host = $this->getHttpRequest()->getUrl()->getHost();

            $mail = new \Nette\Mail\Message();
            $mail->setFrom("Account creation <cms@$host>")
                    ->addTo($user['email'])
                    ->setHtmlBody($template);

            try {
                $mail->send();
                $users->save($user, 'id');
                $this->flashMessage('New account created. Follow the instructions in the given email.');
            } catch (\Nette\InvalidStateException $e) {
                if (strpos($e->getMessage(), "Failed to connect to mailserver"))
                    $this->flashMessage('Failed to send email with instructions due to problems with SMTP. Please let your administrator know.');
                else
                    throw $e;
            }

            $this->redirect('default');
        } // if $loggedUser->isInRole('admin')
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
                $form['password'] = Authenticator::hashPassword($this->getUser()->getIdentity()->email, $form['password']);

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
