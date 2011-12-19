<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace ArticleModule;

/**
 * @module(name="Articles")
 */
class FrontendPresenter extends \Frontend\BasePresenter
{

    /**
     * @view(name="List")
     */
    public function renderDefault()
    {
        $articles = $this->repositories->Article;
        $this->template->articles = $articles->find();
    }

    public function getDefaultViewPossibleParams()
    {
        return null;
    }

    /**
     * @view(name="Archive")
     */
    public function renderArchive()
    {
        $articles = $this->repositories->Article;
        $archive = array();

        // Make sure AS is upper case - required by Nette\Database
        $selection = $articles->find(null, 'date DESC');
        foreach ($selection as $article) {
            $archive[$article['date']->format('F Y')][$article['id']] = $article;
        }

        $this->template->archive = $archive;
    }

    public function getArchiveViewPossibleParams()
    {
        return null;
    }

    /**
     * @view(name="Detail")
     */
    public function renderDetail($name)
    {
        $articles = $this->repositories->Article;
        $this->template->article = $articles->find(array('name_webalized' => $name))->fetch();
    }

    public function getDetailViewPossibleParams()
    {
        return null;
    }

}
