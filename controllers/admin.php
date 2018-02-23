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
}
?>