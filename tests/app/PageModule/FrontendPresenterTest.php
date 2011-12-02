<?php

class PagePresenterTest extends PHPUnit_Framework_TestCase
{

    public function testRenderDefault()
    {
        $presenter = new \PageModule\FrontendPresenter;
        $presenter->autoCanonicalize = FALSE;
        $presenter->setContext(\Nette\Environment::getContext());
        $request = new \Nette\Application\Request(':Page:Frontend', 'GET',array());
        $response = $presenter->run($request); 

        
        self::assertInstanceOf(
            'Nette\Application\Responses\TextResponse',
            $response
        );
    }

}