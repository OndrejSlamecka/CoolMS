<?php

class ArticlePresenterTest extends PHPUnit_Framework_TestCase
{

    public function testRenderDefault()
    {
        $presenter = new \ArticleModule\FrontendPresenter(\Nette\Environment::getContext());
        $presenter->autoCanonicalize = FALSE;
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array());
        $response = $presenter->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

    public function testRenderArchive()
    {
        $presenter = new \ArticleModule\FrontendPresenter(\Nette\Environment::getContext());
        $presenter->autoCanonicalize = FALSE;
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array('action' => 'Archive'));
        $response = $presenter->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

    public function testRenderDetail()
    {
        $presenter = new \ArticleModule\FrontendPresenter(\Nette\Environment::getContext());
        $presenter->autoCanonicalize = FALSE;
        // TODO: 'name' parameter (value 'some-article') should be universal, not just for default database data
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array('action' => 'Detail', 'name' => 'some-article'));
        $response = $presenter->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

}