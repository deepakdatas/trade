<?php
/**
 * 
 * Represents a Level
 * 
 * @author NekoCari
 *
 */

class Level extends DbRecordModel {
    
    protected $id, $level, $name, $cards;
    
    protected static
        $db_table = 'level',
        $db_pk = 'id',
        $db_fields = array('id','name','level','cards'),
        $sql_order_by_allowed_values = array('id','name','level','cards');
    
    private static 
    	$naming_pattern = '/[A-Za-z0-9äÄöÖüÜß _\-]+/',
    	$level_badge_folder;
    
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * get data of all levels from database
     * 
     * @return Level[]
     */
    public static function getAll($order_settings = ['level'=>'ASC'], $limit = null) {
        return parent::getAll($order_settings, $limit);
        
    }
    
    /**
     * get level data from database using id number
     * 
     * @param int $id Id number of level in database
     * 
     * @return Level|NULL 
     */
    public static function getById($id) {
        return parent::getByPk($id);
    }
    
    /**
     * creates a new Level in db and returns the object
     * @param int $level
     * @param string $name
     * @param int $cards
     * @throws Exception
     * @return Level
     */
    public static function add($level, $name, $cards) {
        
            if(preg_match(self::$naming_pattern, $name) AND intval($cards) >= 0){
                $newlevel = new Level();
                $newlevel->setPropValues(['level'=>$level,'name'=>$name,'cards'=>$cards]);
                $level_id = $newlevel->create();
                $newlevel->setPropValues(['id'=>$level_id]);
                return $newlevel;
            }else{
                throw new Exception('Name enthält mindestens ein ungültiges Zeichen.',1001);
            }
        
    }
    
    public static function getLevelBadgeFolder() {
    	if(is_null(self::$level_badge_folder)){
    		self::$level_badge_folder = Setting::getByName('level_badge_folder')->getValue();
    	}
    	return self::$level_badge_folder;
    }
    
    
    /*
     * Getter
     */
    public function getCards() {
        return $this->cards;
    }
    public function getLevel() {
        return $this->level;
    }
    public function getName() {
        return $this->name;
    }
    public function getId() {
        return $this->id;
    }
    
    public function getLevelBadgeUrl() {
    	$path = self::getLevelBadgeFolder().'/'.$this->getLevel().'.'.Setting::getByName('cards_file_type')->getValue();
    	if(file_exists(PATH.$path)){
    		return $path;
    	}else{
    		return null;
    	}
    }
    
    public function getLevelBadgeHTML() {
    	$url = $this->getLevelBadgeUrl();
    	if($url){
    		return '<img src="'.$url.'" alt="'.$this->getName().'">';
    	}else{
    		return '-img not found-';
    	}
    }
    
    /**
     * get level matching a number of cards
     * 
     * @param int $number
     * @return Level|NULL
     */
    public static function getByCardNumber($number) {
        $level_array = parent::getWhere('cards < '.intval($number),['level'=>'DESC']);
        if(count($level_array) > 0){
            return $level_array[0];
        }else{
            return null;
        }
    }
    
    /**
     * Get next level after current
     * 
     * @return Level|NULL
     */
    public function next() {
        $level_array = parent::getWhere('level > '.$this->getLevel(),['level'=>'ASC']);
        if(count($level_array) > 0){
            return $level_array[0];
        }else{
            return null;
        }
    }
    
}