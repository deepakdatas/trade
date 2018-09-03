<?php
/*
 * Admin Controller
 */


require_once PATH.'models/login.php';
require_once PATH.'models/admin.php';

class AdminController {
    
    /**
     * Team Dashboard
     */
    public function dashboard() {
        if(!Login::loggedIn()){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        if($admin->getRights() != NULL){
            Layout::render('admin/dashboard.php');
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Settings
     */
    public function settings() {
        if(!Login::loggedIn()){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        if($admin->getRights() != NULL){
            
            require_once PATH.'models/setting.php';
            $data = array();
            if(isset($_POST['updateSettings']) AND isset($_POST['settings'])){
                foreach($_POST['settings'] as $name => $value){
                    $setting = new Setting($name, $value);
                    $return = $setting->store();
                    if($return !== true){
                        $data['_errors'][] = $return;
                    }
                }
            }
            if(isset($_POST['updateSettings']) AND !isset($data['_error'])){ 
                $data['_success'] = array('Settings wurden aktualisiert.'); 
            }
            $data['settings'] = Setting::getAll();
            Layout::render('admin/settings.php',$data);
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    
    /**
     * Memberlist
     */
    public function memberlist() {
        if(!Login::loggedIn()){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights())){
            require_once 'models/member.php';
            require_once 'helper/pagination.php';
            $members = Member::getAll('id', 'ASC');
            if(isset($_GET['pg'])){
                $currPage = $_GET['pg'];
            }else{
                $currPage = 1;
            }
            $pagination = new Pagination($members, 10, $currPage, Routes::getUri('admin_member_index'));
            $data['members'] = $pagination->getElements();
            $data['pagination'] = $pagination->getPaginationHtml();
            
            Layout::render('admin/members/list.php',$data);
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Member edit form
     */
    public function editMember() {
        // TODO: add pw reset option
        if(!Login::loggedIn()){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights())){
            
            require_once 'models/member.php';
            $memberdata = Member::getById($_GET['id']);
            
            if(isset($_POST['updateMemberdata'])){
                $memberdata->setName($_POST['Name']);
                $memberdata->setLevel($_POST['Level']);
                $memberdata->setMail($_POST['Mail']);
                $memberdata->setInfoText($_POST['Text']);
                $return = $memberdata->store();
                if($return === true){
                    $data['_success'][] = 'Daten wurden gespeichert.';
                }else{
                    $data['_error'][] = 'Daten nicht aktualisiert. Datenbank meldet: '.$return;
                }
            }
            
            $data['memberdata'] = $memberdata->getEditableData();
            if($data['memberdata']){
                Layout::render('admin/members/edit.php',$data);
            }else{
                Layout::render('admin/error.php',['errors'=>array('ID ist ungültig!')]);
            }
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Member search form
     */
    public function searchMember() {
        if(Login::loggedIn()){
            
            $admin = new Admin(Db::getInstance(),$_SESSION['user']);
            
            if(in_array('Admin',$admin->getRights())){
                
                
                if(!isset($_POST['search'])){
                    Layout::render('admin/members/search.php');
                }else{
                    require_once PATH.'models/member.php';
                    $data = array();
                    $data['members'] = Member::searchByName($_POST['search']);
                    $data['pagination'] = '';
                    
                    Layout::render('admin/members/list.php',$data);
                    
                }
            }else{
                Layout::render('templates/error_rights.php');
            }
        }else{
            
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
    }
   
    
    /**
     * Member Gift Cards
     */
    public function giftCards(){
        if(!Login::loggedIn()){
            header("Location: ".BASE_URI.Routes::getUri('signin'));
        }
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights())){
            
            require_once 'models/member.php';
            require_once 'models/card.php';
            $data['member'] = $member = Member::getById($_GET['id']);
            
            if(isset($_POST['addCards']) and intval($_POST['addCards']) and isset($_POST['text'])){
                
                $data['cards'] = Card::createRandomCard($member->getId(),$_POST['addCards'],'manuelle GUTSCHRIFT ('.strip_tags($_POST['text'].')'));
                if(count($data['cards']) > 0){
                    $cardnames = '';
                    foreach($data['cards'] as $card){
                        $cardnames.= $card->getName().", ";
                    }
                    $cardnames = substr($cardnames, 0, -2);
                    $data['_success'][] = 'Gutschrift erfolgt: '.$cardnames;
                }else{
                    $data['_error'][] = 'Gutschrift fehlgeschlagen.';
                }
            }
            
            
            Layout::render('admin/members/gift_cards.php',$data);
        }else{
            Layout::render('templates/error_rights.php');
        }
    }

}
?>