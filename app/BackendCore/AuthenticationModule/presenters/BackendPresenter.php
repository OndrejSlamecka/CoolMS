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

use Nette\Environment;
use Nette\Forms\Form;
use Backend\Authenticator;

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
    protected function createComponentLoginForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->id("LoginForm");

        $form->addText('email', 'Email');
        $form->addPassword('password', 'Password');
        $form->addSubmit('login', 'Log in');
        $form['login']->getControlPrototype()->class('big');

        // Add CSRF protection
        $form->addProtection('Please send the form again, protection period expired.');

        $form->onSuccess[] = array($this, 'loginFormSubmitted');


        return $form;
    }

    public function loginFormSubmitted($form)
    {
        try {
            $this->getUser()->setExpiration('+ 14 days', FALSE);
            // Try to authenticate
            $this->getUser()->login($form['email']->getValue(), $form['password']->getValue());

            $this->redirect(':Dashboard:Backend:');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
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

    public function createComponentRequestNewPasswordForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('ajax');

        $form->addText('email', 'Email')->addRule(Form::EMAIL, 'You have to enter email');
        $form->addSubmit('request', 'Send new password');

        $form->onSuccess[] = array($this, 'requestNewPasswordFormSubmit');
        return $form;
    }

    public function requestNewPasswordFormSubmit($form)
    {
        // Following action: (email, then:) createPassword

        $form = $form->getValues();
        $users = $this->repositories->User;

        // Find user by provided email
        $user = $users->find(array('email' => $form['email']))->fetch();

        // Does user exist?
        $userExists = empty($user);

        if ($userExists) {
            $this->template->emailSendingSuccessful = false;
            $this->template->errorMessage = "You have probably entered a wrong email. Is your address really {$form['email']}?";
        } else {

            $user = $user->toArray();

            // Create token for future verification
            $token = Authenticator::createToken();
            $user['token'] = $token;
            $user['token_created'] = new \DateTime();

            $users->save($user, 'id');

            // Prepare email
            $template = new \Nette\Templating\FileTemplate($this->getTemplatesFolder() . "/email/passwordRequest.latte");
            $template->registerFilter(new \Nette\Latte\Engine);

            $template->site = $this->getHttpRequest()->getUrl()->getHostUrl();
            $template->link = $this->link('//createPassword', array('token' => $token));

            $host = $this->getHttpRequest()->getUrl()->getHost();

            $mail = new \Nette\Mail\Message();
            $mail->setFrom("Password renewal <cms@{$host}>")
                    ->addTo($user['email'])
                    ->setHtmlBody($template);

            try {
                $mail->send();
                $this->template->emailSendingSuccessful = true;
            } catch (\Nette\InvalidStateException $e) {
                if (strpos($e->getMessage(), "Failed to connect to mailserver")) {
                    $this->template->errorMessage = 'Failed to send email with instructions due to problems with SMTP. Please let administrator know.';
                    $this->template->emailSendingSuccessful = false;
                } else
                    throw $e;
            }
        } // if userExists

        $this->invalidateControl('requestNewPassword');
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
        $form = new \Application\Form($this, $name);
        $form->addPassword('password', 'New password');
        $form->addSubmit('save', 'Save');
        $form->onSuccess[] = array($this, 'newPasswordSuccess');
        return $form;
    }

    public function newPasswordSuccess($form)
    {
        // Following action: login

        $form = $form->getValues();
        $token_sess = $this->getSession('token');
        $token = $token_sess->value;

        $users = $this->repositories->User;
        $user = $users->find(array('token' => $token))->fetch();

        if (!$user || !Authenticator::isTokenValid($user['token_created'])) {
            $this->flashMessage('Verification failure. Please use "I forgot my password" again.');
            $this->redirect('login');
        }

        $user = $user->toArray();
        $user['password'] = Authenticator::calculateHash($form['password'], $user['salt']);
        $user['token'] = null;
        $user['token_created'] = null;
        $users->save($user, 'email');

        $this->flashMessage('Password was set. Now you can log in!');
        $this->redirect('login');
    }

}