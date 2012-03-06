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

/**
 * Following action: (email, then) Authentication:createPassword
 */
class NewUserForm extends \Application\Form
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
		$this->addText('email', 'Email')
				->addRule(Form::EMAIL, 'Email is not in the right format.');
		$this->addSubmit('send', 'Create');
		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		if ($this->presenter->getUser()->isInRole('admin')) {

			$form = $form->getValues();
			$users = $this->repository;

			$user = array();
			$user['email'] = $form['email'];
			$user['role'] = 'user';

			if ($users->select()->where('email', $user['email'])->fetch()) {
				$this->presenter->flashMessage('An user with email ' . $user['email'] . ' already exists.');
				$this->presenter->redirect('new');
			}

			$user['password'] = mt_rand(); // some noise...
			$user['salt'] = mt_rand();

			// Create token for future verification
			$token = Authenticator::createToken();
			$user['token'] = $token;
			$user['token_created'] = new \DateTime();

			// Prepare email
			$template = new \Nette\Templating\FileTemplate($this->presenter->getTemplatesFolder() . "/email/newUser.latte");
			$template->registerFilter(new \Nette\Latte\Engine);

			$template->site = $this->getHttpRequest()->getUrl()->getHostUrl();
			$template->link = $this->presenter->link('//:Authentication:Backend:createPassword', array('token' => $token, 'newuser' => 'true'));

			$host = $this->presenter->getContext()->getService('httpRequest')->getUrl()->getHost();

			$mail = new \Nette\Mail\Message();
			$mail->setFrom("Account creation <cms@$host>")
					->addTo($user['email'])
					->setHtmlBody($template);


			try {
				$mail->send();
				$users->save($user, 'id');
				$this->presenter->flashMessage('New account created. Follow the instructions in the given email.');
			} catch (\Nette\InvalidStateException $e) {
				if (strpos($e->getMessage(), "Failed to connect to mailserver"))
					$this->presenter->flashMessage('Failed to send email with instructions due to problems with SMTP. Please let your administrator know.');
				else
					throw $e;
			}

			$this->presenter->redirect('default');
		} // if $loggedUser->isInRole('admin')
	}

}