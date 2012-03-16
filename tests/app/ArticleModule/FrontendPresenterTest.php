<?php

class ArticlePresenterTest extends PHPUnit_Framework_TestCase
{

    private $instance;

    public function setUp()
    {
        parent::setUp();
        $presenter = new \ArticleModule\FrontendPresenter();
		$presenter->setContext(\Nette\Environment::getContext());
        $presenter->autoCanonicalize = FALSE;
		$this->instance = $presenter;
    }

    public function testRenderDefault()
    {
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array());
        $response = $this->instance->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

    public function testRenderArchive()
    {
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array('action' => 'Archive'));
        $response = $this->instance->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

    public function testRenderDetail()
    {
        // TODO: 'name' parameter (value 'some-article') should be universal, not just for default database data
        $request = new \Nette\Application\Request(':Article:Frontend', 'GET', array('action' => 'Detail', 'name' => 'some-article'));
        $response = $this->instance->run($request);

        self::assertInstanceOf(
                'Nette\Application\Responses\TextResponse', $response
        );
    }

}