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

use \Nette\Utils\Strings;

/**
 * File manager's presenter
 * TODO: Refactor. sanitizePath, getRelativePath, getFullPath, getFolderAbove, recursiveRemoveDir should be moved into some model
 * 
 * @author Ondrej Slamecka
 */
class FilePresenter extends BasePresenter
{

    const MODE_SEARCH = 'search';
    const MODE_LIST = 'list';
    
    private $mode;
    
    
    
    public function sanitizePath($path)
    {       
        // Use just '/' everywhere
        $path = Strings::replace($path, '~\\\~', '/');        
        // Use just one separator... 
        $path = Strings::replace($path, '~\/\/~', '/');  
        // Remove ..
        $path = Strings::replace($path, '~\.\.~', '');
        
        if($path==='')
            $path = '/';
        
        return $path;
    }
    
    public function getRelativePath( $path = null )
    {
        return '/files' . $path;
    }
    
    public function getFullPath($path = null)
    {        
        return $this->context->params['wwwDir']. $this->getRelativePath($path);
    }       
    
    public function getFolderAbove($path)
    {
        // If not base folder
        if( $path !== '/' ){
            $folder_above = Strings::match( $path, "~(.*)/.*$~");
            if( is_array( $folder_above ) )
                $folder_above = array_pop( $folder_above );
            else // If it is not array, it is empty - just one dir over base                
                $folder_above = '/';  
        }else
            $folder_above = '/';
        
        return $folder_above;
    }
    
    public function prepareBreadcrumbs($path)
    {
        if( empty($path) || $path === '/' )
            return array( '/' => '/' );
        
        $splitPath = explode('/', $path); 
        array_shift($splitPath); // Remove first empty string (before first "/")
        $pathStack = '';
        $breadcrumbs = array(); 
        
        foreach( $splitPath as $piece ){
            
            $pathStack .= '/'.$piece;  
            $breadcrumbs[$pathStack] = $piece;
        }
        
        return array( '/' => '/' ) + $breadcrumbs;
    }
    
    public function setTemplateVariables($path)
    {                
        if( $this->mode === self::MODE_SEARCH ){
            $this->template->path = '/';
            $this->template->fullpath = $this->getFullPath();
            $this->template->folder_above = '/';
            $this->template->breadcrumbs = null;
            
            // In search mode path is filename
            $this->template->items = \Nette\Utils\Finder::findFiles( '*'. $path . '*' )->from( $this->getFullPath() );            
        }else{
            $this->template->path = $path = $this->sanitizePath($path);
            $this->template->fullpath = $fullpath = $this->getFullPath($path);
            $this->template->folder_above = $this->getFolderAbove($path);
            $this->template->breadcrumbs = $this->prepareBreadcrumbs($path);
            $this->template->items = \Nette\Utils\Finder::find('*')->in( $fullpath );    
        }
    }
    
    /*************/
    
    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);        
        $template->mode = self::MODE_LIST;
        return $template;
    }

    public function handleEdit($path)
    {        
        $this->template->editingItem = $path;
        
        // We want to show folder above
        $path = $this->getFolderAbove($path);
        
        $this->setTemplateVariables($path);
               
        $this['renameForm']->setDefaults( array( 'old_name' => $this->template->editingItem ) );
        
        $this->invalidateControl('FileList');
    }
    
    
    private function recursiveRemoveDir($dir)
    {
        /* http://www.php.net/manual/en/function.rmdir.php#98622 */
        $objects = scandir($dir); 
        
        if( is_array( $objects ) ) // scandir returns false for empty folders
        foreach($objects as $object)
        {
            if ($object !== "." && $object !== "..")
              if(filetype($dir."/".$object) === "dir") 
                $this->recursiveRemoveDir($dir."/".$object);
              else 
                unlink($dir."/".$object);              
        }         
        rmdir($dir);  
    }
    
    public function actionDelete($path)
    {
        $path = $this->sanitizePath($path);
        $fullpath = $this->getFullPath($path);
        
        
        if( !file_exists( $fullpath ) ){
            $this->flashMessage( 'The file or folder you are trying to delete was not found' );            
        }else{
            
            if( is_file( $fullpath ) ){
                unlink( $fullpath );
                $this->flashMessage( 'File deleted' );
            }elseif( is_dir( $fullpath ) ){
                
                $this->recursiveRemoveDir( $fullpath );
                $this->flashMessage( 'Folder deleted' );
                
            }else{
                $this->flashMessage( 'Something strange happened, please try again.' );
            }
            
        }
        
        $this->redirect("default", array( 'path'=>$this->getFolderAbove($path) ) );
    }    
    
    // TODO: Obsolete, remove
    public function actionDownload( $path )
    {        
        $path = $this->sanitizePath($path);
        $fullpath = $this->getFullPath($path);

        if( !is_file( $fullpath ) ){
            $this->flashMessage( 'The file you are trying to delete was not found' );
            $this->redirect("default");
        }

        $name = Strings::match( $path, "~.*/(.*)$~");
        $name = array_pop($name);

        // Send file for download
        $this->sendResponse( new \Nette\Application\Responses\FileResponse( $fullpath , $name ) );
    }    
        
    public function renderDefault( $path )
    {
        if( !isset( $this->template->path ) ){
            $this->setTemplateVariables($path);
        }
    }

    public function createComponentFileUploadForm($name)
    {
        $form = new \App\Form($this,$name);
        $form->getElementPrototype()->class( 'html5upload' );
        
        //$form->addUpload('file', 'Soubor');
        $form->addMultipleUpload("files","File(s)");
        
        $form->addSubmit( 'send', 'Upload' );
        $form['send']->getControlPrototype()->class('big');
        
        $form->onSuccess[] = array($this,'fileUploadFormSubmit');
        
        return $form;
    }    
    
    public function fileUploadFormSubmit($form)
    {
        $path = $this->sanitizePath($this->getParam('path'));
        $form = $form->getValues();
        
        foreach($form['files'] as $file){        
            // Get name and path to place
            $filename = Strings::webalize( $file->name, '.');
            $filepath = $this->getFullPath( $this->sanitizePath($path) ). '/' . $filename;
            // Move
            $file->move( $filepath, $filename );    
        }
        
        $this->redirect( 'default', array( 'path'=>$path ) );
    }
    
    public function createComponentFolderCreationForm($name)
    {
        $form = new \App\Form($this,$name);
        
        $form->addText( 'folder', 'Folder name' );
        
        $form->addSubmit( 'send', 'Create' );
        $form['send']->getControlPrototype()->class('big');
        
        $form->onSuccess[] = array($this,'folderCreationFormSubmit');
        
        return $form;
    }    
    
    public function folderCreationFormSubmit($form)
    {        
        $path = $this->sanitizePath($this->getParam('path'));
        $form = $form->getValues();
        
        $folder = $form['folder'];
        
        mkdir( $this->getFullPath( $path.'/'.$form['folder'] ) );
                
        $this->redirect( 'default', array( 'path'=>$path ) );        
    }    
    
    public function createComponentRenameForm($name)
    {
        $form = new \App\Form($this,$name);
        $form->getElementPrototype()->class('ajax');
        
        $form->addText( 'new_name', 'Name' );
            $form['new_name']->getControlPrototype()->class('low');
            
        $form->addHidden( 'old_name' );
        
        $form->addImage('send', $this->template->themePath.'/icons/accept.png', 'Save');
            $form['send']->getControlPrototype()->class('low');
            
        $form->onSuccess[] = array( $this, 'renameFormSubmit' );
            
        return $form;
    }    
    
    public function renameFormSubmit($form)
    {        
        $showpath = $this->sanitizePath($this->getFolderAbove($this->getParam('path')));

        $form = $form->getValues();
        
        $form['new_name'] = $this->sanitizePath($form['new_name']);
        $form['old_name'] = $this->sanitizePath($form['old_name']);
        
        $form['new_name'] = $this->getFolderAbove( $form['old_name'] ) .'/'. $form['new_name'];
        
        $form['new_name'] = $this->getFullPath( $form['new_name'] );
        $form['old_name'] = $this->getFullPath( $form['old_name'] );
                
        if( file_exists($form['old_name']) )
            rename( $form['old_name'], $form['new_name'] );
        
        $this->setTemplateVariables($showpath);
        $this->invalidateControl('FileList');
        
        if(!$this->isAjax())        
            $this->redirect( 'default', array( 'path'=>$showpath ) );        
    }    
    
    public function createComponentSearchForm($name)
    {
        $form = new \App\Form($this,$name);
        $form->getElementPrototype()->class('ajax onchange');
        
        $form->addText( 'q', 'Search' );
        $form['q']->getControlPrototype()->addAttributes( array( 'autocomplete' => 'off') );
                        
        $form->onSuccess[] = array( $this, 'searchFormSubmit' );
            
        return $form;
    }    
    
    public function searchFormSubmit($form)
    {        
        $form = $form->getValues();
        
        if( empty($form['q']) ){
            $this->setTemplateVariables( '/' );
        }else{
            $this->mode = self::MODE_SEARCH;        
            $this->template->mode = self::MODE_SEARCH;

            $this->setTemplateVariables( $form['q'] );            
            
            if(!$this->isAjax())        
                $this->redirect( 'search', array( 'path'=>$showpath ) );  
        }
                        
        $this->invalidateControl('FileList');     
    }    
    
}
