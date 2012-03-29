<?php

class PagePresenterTest extends PHPUnit_Framework_TestCase
{

    public function testRenderDefault()
    {
        $presenter = new \PageModule\FrontendPresenter(\Nette\Environment::getContext());
		//$presenter->setContext(\Nette\Environment::getContext());
        $presenter->autoCanonicalize = FALSE;
        $request = new \Nette\Application\Request(':Page:Frontend', 'GET', array());
        $response = $presenter->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

}