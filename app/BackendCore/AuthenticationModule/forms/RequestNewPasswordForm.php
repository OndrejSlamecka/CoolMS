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
 * Following action: (email, then:) createPassword
 */
class RequestNewPasswordForm extends \Coolms\Form
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
		$this->getElementPrototype()->class('ajax');

		$this->addText('email', 'Email')->addRule(Form::EMAIL, 'You have to enter email');
		$this->addSubmit('request', 'Send new password');

		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		$form = $form->getValues();
		$users = $this->repository;

		// Find user by provided email
		$user = $users->select()->where('email', $form['email'])->fetch();

		// Does user exist?
		$userExists = empty($user);

		if ($userExists) {
			$this->presenter->template->emailSendingSuccessful = false;
			$this->presenter->template->errorMessage = "You have probably entered a wrong email. Is your address really {$form['email']}?";
		} else {

			$user = $user->toArray();

			// Create token for future verification
			$token = Authenticator::createToken();
			$user['token'] = $token;
			$user['token_created'] = new \DateTime();

			$users->save($user, 'id');

			// Prepare email
			$template = new \Nette\Templating\FileTemplate($this->presenter->getTemplatesFolder() . "/email/passwordRequest.latte");
			$template->registerFilter(new \Nette\Latte\Engine);

			$template->site = $this->presenter->getContext()->getService('httpRequest')->getUrl()->getHostUrl();
			$template->link = $this->presenter->link('//createPassword', array('token' => $token));

			$host = $this->presenter->getContext()->getService('httpRequest')->getUrl()->getHost();

			$mail = new \Nette\Mail\Message();
			$mail->setFrom("Password renewal <cms@{$host}>")
					->addTo($user['email'])
					->setHtmlBody($template);

			try {
				$mail->send();
				$this->presenter->template->emailSendingSuccessful = true;
			} catch (\Nette\InvalidStateException $e) {
				if (strpos($e->getMessage(), "Failed to connect to mailserver")) {
					$this->presenter->template->errorMessage = 'Failed to send email with instructions due to problems with SMTP. Please let administrator know.';
					$this->presenter->template->emailSendingSuccessful = false;
				} else
					throw $e;
			}
		} // if userExists

		$this->presenter->invalidateControl('requestNewPassword');
	}

}