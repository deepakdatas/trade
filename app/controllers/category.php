<?php
/*
 * Categories and Subcategories Controller
 * 
 * @author NekoCari
 */


class CategoryController extends AppController {
  
    
    /**
     * Categories List
     */
    public function categories() {
        
    	$this->redirectNotLoggedIn();
        
        // check if user has required rights
        $user_rights = $this->login()->getUser()->getRights();
        if(!in_array('Admin',$user_rights) AND !in_array('ManageCategories',$user_rights) ){
            die($this->layout()->render('templates/error_rights.php'));
        }
        
        if(isset($_POST['action']) AND $_POST['id']){
            
            try {
                if($_POST['action'] == 'del_cat'){
                   Category::getById($_POST['id'])->delete();
                    
                }elseif($_POST['action'] == 'del_subcat'){
                   Subcategory::getById($_POST['id'])->delete();
                    
                }
            }
            
            catch(Exception $e){
                
                if($e instanceof PDOException AND $e->getCode() == 23000){
                    $this->layout()->addSystemMessage('error', 'database_relations_exist_error');
                }else{
                	$this->layout()->addSystemMessage('error', '0',[],$e->getCode());
                }
                
            }
        }
        
        
        $data['categories'] = Category::getALL();
        
        foreach($data['categories'] as $category){
            $cat_id = $category->getId();
            $data['subcategories'][$cat_id] = Subcategory::getByCategory($cat_id);
        }
        
        $this->layout()->render('admin/category/index.php',$data);            
        
    }
    
    /**
     * new Category
     */
    public function addCategory() {
    	
    	$this->redirectNotLoggedIn();
        
        // check if user has required rights
        $user_rights = $this->login()->getUser()->getRights();
        if(!in_array('Admin',$user_rights) AND !in_array('ManageCategories',$user_rights) ){
            die($this->layout()->render('templates/error_rights.php'));
        }
        
        if(isset($_POST['addCategory']) AND isset($_POST['name'])){
            
            if(($return = Category::add($_POST['name'])) === true){
                
                header("Location: ".BASE_URI."admin/category.php");
                
            }else{
                
                $this->layout()->render('admin/error.php',['errors'=>array($return)]);
                
            }
        }else{
            
            $this->layout()->render('admin/category/add_category.php');
            
        }
        
    }
    
    /**
     * new Subcategory
     */
    public function addSubcategory() {
    	
    	$this->redirectNotLoggedIn();
        
        // check if user has required rights
        $user_rights = $this->login()->getUser()->getRights();
        if(!in_array('Admin',$user_rights) AND !in_array('ManageCategories',$user_rights) ){
            die($this->layout()->render('templates/error_rights.php'));
        }
            
        if(isset($_POST['addCategory']) AND isset($_POST['name']) AND isset($_POST['category'])){
            
            if(($return = Subcategory::add($_POST['name'],$_POST['category'])) === true){
                
                header("Location: ".BASE_URI.Routes::getUri('category_index'));
                
            }else{
            
                $this->layout()->render('admin/error.php',['errors'=>array($return)]);
                
            }
            
        }else{
            
            $category = Category::getById($_POST['category']);
        
            $this->layout()->render('admin/category/add_subcategory.php',['category'=>$category]);
            
        }
        
    }
    
    
    /**
     * edit Subcategory
     */
    public function editSubcategory() {
    	
    	$this->redirectNotLoggedIn();
        
        // check if user has required rights
        $user_rights = $this->login()->getUser()->getRights();
        if(!in_array('Admin',$user_rights) AND !in_array('ManageCategories',$user_rights) ){
            die($this->layout()->render('templates/error_rights.php'));
        }
            
        $subcategory = Subcategory::getById($_GET['id']);
        
        if(isset($_POST['rename']) AND isset($_POST['name']) AND isset($_POST['category'])){
            
            $subcategory->setCategory($_POST['category']);
            $subcategory->setName($_POST['name']);
            $subcategory->store();
            
            header("Location: ".BASE_URI.Routes::getUri('category_index'));
            
        }
        
        $category = Category::getById($subcategory->getCategory()->getId());
        $categories = Category::getALL();
        
        $this->layout()->render('admin/category/edit_subcategory.php',['category'=>$category,'subcategory'=>$subcategory,'categories'=>$categories]);
        
    }
    
    
    /**
     * edit Category
     */
    public function editCategory() {
        
    	$this->redirectNotLoggedIn();
        
        // check if user has required rights
        $user_rights = $this->login()->getUser()->getRights();
        if(!in_array('Admin',$user_rights) AND !in_array('ManageCategories',$user_rights) ){
            die($this->layout()->render('templates/error_rights.php'));
        }
            
        $category = Category::getById($_GET['id']);
        
        if(isset($_POST['rename']) AND isset($_POST['name'])){
            
            $category->setName($_POST['name']);
            $category->store();
            
            header("Location: ".BASE_URI.Routes::getUri('category_index'));
            
        }
        
        $this->layout()->render('admin/category/edit_category.php',['category'=>$category]);
        
    }

}
?>