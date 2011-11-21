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

use Nette\Forms\Form;

/**
 * Article presenter
 *
 * @author Ondrej Slamecka
 */
class ArticlePresenter extends BasePresenter
{        
    
    public function actionDelete( $id )
    {
        $pages = $this->repositories->Page;
        
        $page = $pages->find( array( 'id' => $id ) )->fetch();
        
        if( !$page ){            
            $this->flashMessage( 'Stránka nebyla nalezena :o(' );
            $this->redirect( 'default' );
        }
        
        try{
            $pages->remove( array( 'id' => $id ) );
            $this->flashMessage('Stránka odstraněna.');
        }catch( Exception $e ){
            $this->flashMessage('Něco se pokazilo. Zkuste prosím provést akci znova');
        }
        $this->redirect( 'default' );
    }    
    
    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout('wysiwyg_layout');
    }
    
    public function renderEdit( $id )
    {
        $pages = $this->repositories->Page;

        $this->template->page = $page = $pages->find( array( 'name_webalized' => $id ) )->fetch();
	

        if( !$page ){
            $this->flashMessage( 'Požadovaná stránka nebyla nelezena. ' );
            $this->redirect( 'default' );
        }

        $arr = $page->toArray();

        $this[ 'pageForm' ]->setDefaults( $arr );

    }

    public function renderDefault()
    {
	$article = $this->repositories->Article;
	$this->template->articles = $article->find();
    }

    public function createComponentArticleForm($name)
    {
	$form = new \App\Form($this, $name);
	$form->getElementPrototype()->id( 'pageForm' );

        $form->addHidden('id');
        
	$form->addText( 'name_webalized', 'Tvar v URL' );
        
	$form->addText( 'name', 'Název' );
	$form['name']->getControlPrototype()->class( 'ribbon' );

	$form->addTextarea( 'text', 'Text', 60, 30 );
        $form['text']->getControlPrototype()->class( 'wysiwyg' );
        
	$form->addSubmit( 'save', 'Save' );

	$form->onSuccess[] = array( $this, 'articleSubmit' );

	return $form;
    }

    public function articleSubmit($form)
    {
	$article = $form->getValues();                

        if( $article['id'] === ''){
            $article['id'] = null;             
            $article['date'] = new \DateTime;
        }
        if( $article['name_webalized'] === '' )
            $article['name_webalized'] = \Nette\Utils\Strings::webalize( $article['name'] );
        
	$articles = $this->repositories->Article;

	try{
	    $articles->save( $article, 'id' );
	    $this->flashMessage( 'Article saved.' );
	}catch( Exception $e ){
	    $this->flashMessage( 'Article was not saved. Please try again and then contact the administrator.');
	}

	$this->redirect( 'default' );
    }

}
