<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace DashboardModule;

/**
 * Dashboard presenter
 */
class BackendPresenter extends \Backend\BasePresenter
{

	public function renderDefault()
	{
		$this->template->nPages = $this->repositories->Page->count();

		$this->template->nArticles = $this->repositories->Article->count();
		$this->template->lastArticle = $this->repositories->Article->select()->order('id DESC')->limit(1)->fetch();

		/* New user info message */
		$userinfo = $this->getSession('userinfo');
		$this->template->newUser = $userinfo->newUser;
		if ($userinfo->newUser) {
			unset($userinfo->newUser);
		}
	}

}
