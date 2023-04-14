<?php
/**
 * 
 * Represents a Member
 * 
 * @author NekoCari
 *
 */

class Member extends DbRecordModel {
    
    protected 
        $id, $name, $mail, $info_text, $info_text_html, $level, $money, $join_date, $login_date, $status, $ip;
    
    private 
        $password, $cards, $masterd_decks, $rights, $profil_cards, $settings;
    
        
    protected static
        $db_table = 'members',
        $db_pk = 'id',
        $db_fields = array('id','name','mail','info_text','info_text_html','level','money','join_date','login_date','status','ip'),
        $sql_order_by_allowed_values = array('id','name','level','join_date','login_date','status');
    
        private static 
            $accepted_group_options = array('level'),
            $accepted_status_options = array('pending','default','suspended');
    
    public function __construct() {
        parent::__construct();
    }
    
    
    public static function getAcceptedStati(){
        return self::$accepted_status_options;
    }
    
    /**
     * get data of all members in an array grouped by a col from database
     *
     * @param string $group fieldname for array grouping
     *
     * @return Member[]
     */
    public static function getGrouped($group, $order_by = 'name', $order = 'ASC') {
        
        $members = array();
        $db_conn = Db::getInstance();
        
        if(!in_array($order_by, self::$sql_order_by_allowed_values)){
            $order_by = self::$sql_order_by_allowed_values[0];
        }
        
        if(!in_array($order, self::$sql_direction_allowed_values)){
            $order = self::$sql_direction_allowed_values[0];
        }
        
        if(!in_array($group, self::$accepted_group_options)){
            $group = self::$accepted_group_options[0];
        }
        
        $members_array = self::getAll([$order_by=>$order]);
            
        foreach($members_array as $member){
            $members[$member->$group][]  = $member;
        }
        
        return $members;
    }
    
    /**
     * get member data from database using id number
     *
     * @param int $id Id number of member in database
     *
     * @return boolean|Member
     */
    public static function getById($id) {
        return parent::getByPk($id);
    }
    
    /**
     * get member data from database mail adress
     *
     * @param string $mail
     *
     * @return Member|NULL
     */
    public static function getByMail($mail) {
        return parent::getByUniqueKey('mail', $mail);
    } 
    
    /**
     * find members with names matching the search string
     * 
     * @param string $search_str
     * 
     * @return Member[]
     */
    public static function searchByName($search_str) {
        
        $db = DB::getInstance();
        $members = array();
        
        $req = $db->prepare('SELECT * FROM '.self::$db_table.' WHERE name LIKE :search_str ');
        $req->execute(array(':search_str'=>'%'.$search_str.'%'));
       
            foreach($req->fetchAll(PDO::FETCH_CLASS,__CLASS__) as $member){
                $members[] = $member;
            }
            
        return $members;
    }
    
    /**
     * 
     * @return string
     */
    public function getPassword(){
    	return $this->password;
    }
    
    public function getStatus() {
    	return $this->status;
    }
    
    /**
     * 
     * @return MemberSetting|NULL
     */
    public function getSettings() {
    	if(!$this->settings instanceof MemberSetting){
    		$this->settings = MemberSetting::getByMemberId($this->getId());
    	}
    	return $this->settings;
    }
    
    
    public function getLang() {
    	return $this->getSettings()->getValueByName('language');
    }
    public function getTimezone() {
    	return $this->getSettings()->getValueByName('timezone');
    }
    
    /**
     * Uses Card Method getMemberCardsByStatus to get this members cards of given status
     * 
     * @param string $status current card status
     * 
     * @return Card[] array of Card Objects or empty array 
     */
    public function getCardsByStatus($status, $only_tradeable = false) {
        if(!isset($this->cards[$status])){
            $this->cards[$status] = Card::getMemberCardsByStatus($this->id, $status, $only_tradeable);
        }
        return $this->cards[$status];
    }
    
    /**
     */
    public function getProfilCardsByStatus($status, $only_tradeable = true, $compare_user_id = NULL){
        if(!isset($this->profil_cards[$status])){
            $this->profil_cards[$status] = CardFlagged::getCardsByStatus($this->getId(), $status, true, $compare_user_id);
        }
        return $this->profil_cards[$status];
    }
    
    /**
     * @todo make work with status objects
     * @param string $status
     * @return NULL[][]|unknown[][]
     */
    public function getDuplicateCards($tradeable = true){
        return Card::getDuplicatesByMemberId($this->getId(),$tradeable);
    }
    
    /**
     * Uses Carddeck Method getMasterdByMember to get this members masterd decks
     * 
     * @return Carddeck[]
     */
    public function getMasteredDecks($grouped = false) {
        if(!isset($this->mastered_decks)){
            $this->mastered_decks = array();
            $masters = Master::getMasterdByMember($this->id,$grouped);
            $this->mastered_decks = $masters;
        }
        return $this->mastered_decks;
    }
    
    /**
     * returns url to view pofil
     *
     * @return string - html code
     */
    public function getProfilLink() {
    	if(!is_null($this->id)){
    		return RoutesDb::getUri('member_profil').'?id='.$this->id;
    	}else{
    		return null;
    	}
    }
    
    /**
     * returns linked member name
     *
     * @return string - html code
     */
    public function getMemberLinkHtml() {
    	if(!is_null($url = $this->getProfilLink())){
    		return '<a href="'.$url.'">'.$this->getName().'</a>';
    	}else{
    		return $this->getName();
    	}
    }
    
    /**
     * checks if member already took cards from the update with $update_id
     * 
     * @param int $update_id - id of update
     * 
     * @return boolean
     */
    public function gotUpdateCards($update_id){
        $req = $this->db->prepare('SELECT * FROM updates_members WHERE member_id = :member_id AND update_id = :update_id ');
        $req->execute(array(':member_id'=>$this->id, ':update_id'=>$update_id));
        if($req->rowCount() > 0){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * retuns the amount of decks the member has mastered
     * 
     * @return int
     */
    public function getMasterCount(){
        $master_counter = 0;
        if(!is_null($this->masterd_decks)){
            $master_counter = count($this->getMasteredDecks());
        }else{
            $sql = "SELECT COUNT(*) FROM ".Master::getDbTableName()." WHERE member = ".$this->getId();
            $req = $this->db->query($sql);
            $master_counter = $req->fetchColumn();
        }
        return $master_counter;
    }
    
    /**
     * returns the total amount of cards the member owns
     * 
     * @return int - number of cards in total
     */
    public function getCardCount(){
        $sql = "SELECT COUNT(*) FROM ".Card::getDbTableName()." WHERE owner = ".$this->getId();
        $req = $this->db->query($sql);
        $counter = $req->fetchColumn();
        return $counter;
    }
    
    /**
     * checks if the member has enough cards to level up and changes the level
     */
    public function checkLevelUp() {
        
        $card_count_with_master = $this->getCardCount() + ($this->getMasterCount() * Setting::getByName('cards_decksize')->getValue());
        $current_level = $this->getLevel();
        $reached_level = Level::getByCardNumber($card_count_with_master);
        
        if(!is_null($reached_level) AND $current_level != $reached_level->getId()){
            
            $next_level = $this->getLevel('object')->next();
            
            if($next_level instanceof Level){
                
                $this->setLevel($next_level->getId());
                $this->cards = null;
                
                if($this->store()){
                    
                    // Gift for level up
                    $levelup_bonus = Setting::getByName('giftcards_for_master');
                    if($levelup_bonus instanceof Setting AND $levelup_bonus->getValue() > 0){
                        Card::createRandomCard($this->getId(),$levelup_bonus->getValue(),'Level Up!');
                    }
                    Message::add(null, $this->getId(), 'LEVEL UP! Du hast, für das Erreichen von Level '.$next_level->getName().', '.$levelup_bonus->getValue().' Karten erhalten.');
                    
                }
            }
        }
        
    }
    
    /**
     * returns the fields admins can edit
     * @return String[]
     */
    public function getEditableData($mode = 'user') {
        $filed = array();
        if($mode  == 'admin'){
            $fields = array('Name' => $this->name, 'Mail' => $this->mail, 'Status' => $this->status, 'Level' => $this->level, 'Money' => $this->money, 'Text' => $this->info_text);
        }elseif($mode == 'user'){
            $fields = array('Name' => $this->name, 'Mail' => $this->mail, 'Text' => $this->info_text);
        }
        return $fields; 
    }
    
    /**
     * push current member data into database
     *
     * @return boolean
     */
    public function store() {
        
        // make sure info_text_html is up to date!
        $parsedown = new Parsedown();
        $this->info_text_html = $parsedown->text($this->info_text);
        
        return parent::update();
    }
    
    
    /**
     * get members assigned rights
     * @return Right[]
     */
    public function getRights(){
        if(is_null($this->rights)){
            $member_rights = MemberRight::getByMemberId($this->getId());
            $this->rights = array();
            foreach($member_rights as $right){
                $this->rights[] = $right->getRight()->getName();
            }
        }
        return $this->rights;
    }
    
    /**
     * add a right to member
     *
     * @param int $right_id - id of right to add
     *
     * @return boolean
     */
    public function addRight($right_id){
        $member_right = new MemberRight();
        $member_right->setPropValues(['member_id'=>$this->getId(), 'right_id'=>$right_id]);
        try {
            $member_right->create();
            return true;
        }
        catch(Exception $e){
            return false;
        }
        
    }
    
    /**
     * remove a right from member
     *
     * @param int $right_id - id of right to remove
     *
     * @return boolean
     */
    public function removeRight($right_id){
        $result = false;
        try{
            $member_right = MemberRight::getWhere('member_id = '.$this->getId().' AND right_id = '.intval($right_id));
            if(is_array($member_right) AND count($member_right) == 1){
                $member_right[0]->delete();
            }
        }
        catch(Exception $e){
            return false;
        }
    }
    
    /**
     * adds set amount to member money balance
     * @param int $amount - has to be positive!
     * @throws ErrorException
     * @return boolean
     */
    public function addMoney($amount){
        if(is_numeric($amount) AND intval($amount) > 0){
            $this->setMoney($this->getMoney() + intval($amount));
            return true;
        }else{
            throw new ErrorException('amount has to be a positiv number');
            return false;
        }
    }
    
    public function resetPassword(){
        if(Login::loggedIn() AND in_array('Admin',$this->login()->getUser()->getRights()) OR !Login::loggedIn() ){
          
            $random_code = Login::getRandomActivationCode();            
            
            if($this->setPassword($random_code)){
                // set mail vars
                $app_name = Setting::getByName('app_name')->getValue();
                $app_mail = Setting::getByName('app_mail')->getValue();
                $name = $this->getName();
                $recipient  = $this->getMail();
                $title = 'Neues Passwort für '.$app_name;
                // get mail tpl and replace placeholders
                $message = file_get_contents(PATH.'inc/mail_template_reset_pw.php');
                $message = str_replace(['{{MEMBERNAME}}','{{PASSWORD}}','{{APPNAME}}'], [$name,$random_code,$app_name], $message);
                // set mail header
                $header[] = 'MIME-Version: 1.0';
                $header[] = 'Content-type: text/html; charset=UTF-8';
                $header[] = 'From: '.$app_name.' <'.$app_mail.'>';
                // send mail
                if(!mail($recipient, $title, $message, implode("\r\n", $header))){
                    throw new ErrorException('login_new_password_not_sent');
                    return false;
                }
                return true;
            }else{
                return false;
            }
            
        }
    }
    
    /**
     * BASIC GETTER FUNCTIONS
     */
    
    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getMail() {
        return $this->mail;
    }
    
    public function getLevel($mode='id') {
        if($mode=='id'){
            return $this->level;
        }else{
            return Level::getById($this->level);
        }
    }
    
    public function getMoney() {
        return $this->money;
    }
    
    public function getJoinDate() {
        return $this->join_date;
    }
    
    public function getInfoText($mode='html') {
        $text = '';
        switch($mode){
            case 'html':
                $text = $this->info_text_html;
                break;
            default:
                $text = $this->info_text;
                break;
        }
        return $text;
    }
    
    /**
     * @deprecated
     */
    public function getInfoTextplain() {
        return $this->text;
    }
    
    
    /**
     * BASIC SETTER FUCTIONS
     */
    
    public function setName($name) {
        $this->name = $name;
        return true;
    }
    
    public function setMail($mail) {
        $this->mail = $mail;
        return true;
    }
    
    public function setLevel($level) {
        $this->level = $level;
        return true;
    }    
    
    public function setMoney($amount){
        $this->money = intval($amount);
        return true;
    }
    
    public function setStatus($status){
        if(in_array($status, self::$accepted_status_options)){
            $this->status = $status;
            return true;
        }else{
            return false;
        }
    }
    
    public function setInfoText($text) {
        $parsedown = new Parsedown();
        $this->info_text = strip_tags($text);
        $this->info_text_html = $parsedown->text($this->info_text);
        if($this->info_text != $text){
            throw new Exception('Unerlaubte Elemente wie HTML Code wurden entfernt.','8000');
        }
        return true;
    }
    
    public function setPassword($pw){
        $req = $this->db->prepare('UPDATE members SET password = :password WHERE id = :id');
        return $req->execute([':password'=>password_hash($pw,PASSWORD_BCRYPT), ':id'=>$this->getId()]);
    }
    
    
}