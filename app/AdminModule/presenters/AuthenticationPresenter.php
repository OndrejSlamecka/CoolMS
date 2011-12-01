<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace AdminModule;

use Nette\Environment;
use Nette\Forms\Form;

/**
 * Class responsible for handling all not logged users' requests
 * 
 * @author Ondrej Slamecka
 */
class AuthenticationPresenter extends BasePresenter
{
    /********************************* LOGIN **********************************/

    /**
     * Login form component factory.
     */
    protected function createComponentLoginForm($name)
    {
        $form = new \App\Form($this, $name);
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

            $this->redirect('Dashboard:');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function renderLogin()
    {
        //dump( Authenticator::hashPassword( 'email', 'password' ) ); // Printing for development purposes
    }

    /********************************* LOGOUT *********************************/

    public function actionLogout()
    {
        $this->getUser()->logout(TRUE);
        $this->redirect('Authentication:login');
    }

    /************************** REQUEST NEW PASSWORD **************************/

    public function createComponentRequestNewPasswordForm($name)
    {
        $form = new \App\Form($this, $name);
        $form->getElementPrototype()->class('ajax');

        $form->addText('email', 'Email')->addRule(Form::EMAIL, 'Musíte zadat email.');
        $form->addSubmit('request', 'Poslat nové heslo');

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

        if (empty($user)) {
            $this->flashMessage('A user with given email does not exist');
            $this->redirect('login');
        }

        $this->template->confirmationEmailSent = false;
        $this->template->userExists = false;
        $this->template->givenEmail = $form['email'];

        // If user exists // TODO: Rewrite, user existance verified earlier // TODO: Do good testing, earlier verifying code breaks messages in login.latte
        if (!empty($user)) {

            $this->template->userExists = true;


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
            $mail->setFrom("Obnova hesla <cms@{$host}>")
                    ->addTo($user['email'])
                    ->setHtmlBody($template)
                    ->send();

            $this->template->confirmationEmailSent = true;
        }

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
        $form = new \App\Form($this, $name);
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
        $user['password'] = Authenticator::hashPassword($user['email'], $form['password']);
        $user['token'] = null;
        $user['token_created'] = null;
        $users->save($user, 'email');

        $this->flashMessage('Password was set. Now you can log in!');
        $this->redirect('login');
    }

}