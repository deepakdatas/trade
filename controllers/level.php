<?php
/*
 * Level Controller
 */

require_once 'models/admin.php';
require_once 'models/level.php';

class LevelController {
    
    /**
     * Level overview
     */
    public function level() {
        
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        
        // create a new instance of admin class
        $admin = new Admin($_SESSION['user']->id);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('ManageLevel',$admin->getRights())){
            
            $data['level'] = Level::getAll();
            
            Layout::render('admin/level/list.php',$data);
            
        }else{
            
            Layout::render('templates/error_rights.php');
            
        }
    }
    
    /**
     * Level add
     */
    public function addLevel() {
        
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        
        // create a new instance of admin class
        $admin = new Admin($_SESSION['user']->id);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('ManageLevel',$admin->getRights())){
            
            $data = array();
            
            if(isset($_POST['addLevel'])){
                
                $return = Level::add($_POST['level'], $_POST['name'], $_POST['cards']);
                
                if($return === true){
                    
                    header("Location: ".BASE_URI.Routes::getUri('level_index'));
                    
                }else{
                    
                    $data['_error'][] = 'Anlegen fehlgeschlagen. '.$return;
                    
                }
            }
            
            Layout::render('admin/level/add.php',$data);
            
        }else{
            
            Layout::render('templates/error_rights.php');
            
        }
    }
    
    /**
     * Level edit
     */
    public function editLevel() {
        
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        
        // create a new instance of admin class
        $admin = new Admin($_SESSION['user']->id);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('ManageLevel',$admin->getRights())){
            
            $data = array();
            $data['level'] = Level::getById($_GET['id']);
            
            if(isset($_POST['editLevel'])){
                
                $return = $data['level']->update($_POST['level'],$_POST['name'], $_POST['cards']);
                
                if($return === true){
                    
                    $data['_success'][] = 'Änderungen gespeichert.';
                    
                }else{
                    
                    $data['_error'][] = 'Ändern fehlgeschlagen. '.$return;
                    
                }
            }
            
            Layout::render('admin/level/edit.php',$data);
            
        }else{
            
            Layout::render('templates/error_rights.php');
            
        }
    }
    
}
?>