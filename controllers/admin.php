<?php
/*
 * Admin Controller
 */
class AdminController {
    
    /**
     * Team Dashboard
     */
    public function dashboard() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
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
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
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
     * Categories List
     */
    public function categories() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights())){
            require_once PATH.'models/subcategory.php';
            
            if(isset($_POST['action']) AND $_POST['id']){
                if($_POST['action'] == 'del_cat'){
                    $return = Category::delete($_POST['id']);
                }elseif($_POST['action'] == 'del_subcat'){
                    $return = Subcategory::delete($_POST['id']);
                }
                if($return !== true){
                    Layout::render('admin/error.php',['errors'=>array($return)]);
                    die();
                }
            }
            $categories = Category::getALL();
            $subcategories = Subcategory::getALL();
            
            Layout::render('admin/categories.php',['categories'=>$categories,'subcategories'=>$subcategories]);
        }else{
            Layout::render('templates/error_rights.php');
        }
        
    }
    
    /**
     * Memberlist
     */
    public function memberList() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
    
        if(in_array('Admin',$admin->getRights())){
            require_once 'models/member.php';
            $members = Member::getAll('id', 'ASC');
        
            Layout::render('admin/members/list.php',['members'=>$members]);
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Member edit
     */
    public function editMember(){
        // TODO: add pw reset option
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights())){
            require_once 'models/member.php';
            if(isset($_POST['updateMemberdata'])){
                $updated_member = new Member($_POST['id'],$_POST['name'],$_POST['level'],$_POST['mail']);
                $return = $updated_member->store();
                if($return === true){
                    $data['_success'][] = 'Daten wurden gespeichert.';
                }else{
                    $data['_error'][] = 'Daten nicht aktualisiert. Datenbank meldet: '.$return;
                }
            }
            $data['memberdata'] = Member::getById($_GET['id']);
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
     * Card Upload
     */
    public function cardUpload() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once PATH.'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            
            require_once 'models/setting.php';
            $setting_decksize = Setting::getByName('cards_decksize');            
            if($setting_decksize instanceof Setting){
                $data['settings']['decksize'] = $setting_decksize->getValue();
            }else{
                $data['_error'][] = $setting_decksize;
            }
            
            require_once PATH.'models/subcategory.php';
            $data['categories'] = Category::getALL();
            foreach($data['categories'] as $category){
                $cat_id = $category->getId();
                $data['subcategories'][$cat_id] = Subcategory::getByCategory($cat_id);
            }
            
            if(isset($_POST['upload']) AND isset($_POST['name'],$_POST['deckname'],$_POST['subcategory']) AND isset($_FILES)){
                require_once 'helper/cardupload.php';
                $upload = new CardUpload($_POST['name'], $_POST['deckname'], $_FILES, $_SESSION['user']->id, $_POST['subcategory']);
                if(($upload_status = $upload->store()) === true){
                    $data['_success'][] = 'Karten wurde erfolgreich hochgeladen.';
                }else{
                    $data['_error'][] = 'Upload nicht abgeschlossen: '.$upload_status;
                }
            }
            
            Layout::render('admin/cards/upload.php', $data);
            
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Card List
     */
    public function cardList() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once 'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            require_once 'models/carddeck.php';
            $data['carddecks'] = Carddeck::getAll();
            $data['badge_css']['new'] = 'badge-primary';
            $data['badge_css']['public'] = 'badge-secondary';
            
            Layout::render('admin/cards/list.php',$data);
            
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Card edit
     */
    public function cardEdit() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once 'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            require_once 'models/carddeck.php';
            require_once 'models/setting.php';
            require_once 'models/member.php';
            
            if(isset($_POST['updateDeckdata'])){
                $update = Carddeck::update($_POST['id'], $_POST['name'], $_POST['creator']);
                if($update === true){
                    $data['_success'][] = 'Daten wurden gespeichert';
                }else{
                    $date['_error'][] = $update;
                }
            }
            
            $data['deckdata'] = Carddeck::getById($_GET['id']);
            $data['card_images'] = $data['deckdata']->getImages();
            $data['memberlist'] = Member::getAll('name', 'ASC');
            
            Layout::render('admin/cards/edit.php',$data);
            
        }else{
            Layout::render('templates/error_rights.php');
        }
    }
    
    /**
     * Level overview
     */
    public function level() {
        if(!isset($_SESSION['user'])){
            header("Location: ".BASE_URI."signin.php");
        }
        require_once 'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            
            require_once 'models/level.php';
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
            header("Location: ".BASE_URI."signin.php");
        }
        require_once 'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            $data = array();
            
            if(isset($_POST['addLevel'])){
                require_once 'models/level.php';
                $return = Level::add($_POST['level'], $_POST['name'], $_POST['cards']);
                
                if($return === true){
                    header("Location: ".BASE_URI."admin/level.php");
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
            header("Location: ".BASE_URI."signin.php");
        }
        require_once 'models/admin.php';
        $admin = new Admin(Db::getInstance(),$_SESSION['user']);
        
        if(in_array('Admin',$admin->getRights()) OR in_array('CardCreator',$admin->getRights())){
            $data = array();
            require_once 'models/level.php';
            $data['level'] = Level::getById($_GET['id']);
            
            if(isset($_POST['editLevel'])){
                $return = $data['level']->update($_POST['level'],$_POST['name'], $_POST['cards']);
                
                if($return === true){
                    $data['_success'][] = 'Änderungen gespeichert.';
                }else{
                    $data['_error'][] = 'Anlegen fehlgeschlagen. '.$return;
                }
            }
            
            Layout::render('admin/level/edit.php',$data);
            
        }else{
            Layout::render('templates/error_rights.php');
        }
    }


}
?>