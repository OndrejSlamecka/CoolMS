<?php

/**
 * Taken from Nette sandbox.
 */

namespace ErrorModule;

use Nette\Diagnostics\Debugger,
	Nette\Application as NA;

/**
 * Error presenter.
 */
class ErrorPresenter extends \Coolms\BasePresenter
{

	/**
	 * @param  Exception
	 * @return void
	 */
	public function renderDefault($exception)
	{
		if ($this->isAjax()) { // AJAX request? Just note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();
		} elseif ($exception instanceof NA\BadRequestException) {
			$code = $exception->getCode();
			$this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx'); // load template 403.latte or 404.latte or ... 4xx.latte
		} else {
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}
	}

}
