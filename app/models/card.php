<?php
/**
 * 
 * Represents a Card
 * 
 * @author NekoCari
 *
 */

class Card extends DbRecordModel {
    
    protected $id, $name, $deck, $number, $owner, $status, $status_id, $date, $owner_obj, $deck_obj;
    
    private $is_tradeable;
    
    protected static 
        $db_table = 'cards',
        $db_pk = 'id',
        $db_fields = array('id','deck','number','name','owner','status','status_id','date'),
        $sql_order_by_allowed_values = array('id','name');
    
    private static  $tpl_width, $tpl_height, $tpl_html, $accepted_stati, $accepted_stati_obj;
    
    public function __construct() {
        parent::__construct();
        if(is_null(self::$tpl_html)){
            self::$tpl_width = Setting::getByName('cards_template_width')->getValue();
            self::$tpl_height = Setting::getByName('cards_template_height')->getValue();
            self::$tpl_html = file_get_contents(PATH.'app/views/multilang/templates/card_image_temp.php');
        }
    }
    
    public static function getAcceptedStati(){
    	if(is_null(self::$accepted_stati)){
    		foreach(self::getAcceptedStatiObj() as $status){
    			self::$accepted_stati[$status->getId()] = $status->getName();
    		}
    	}
    	return self::$accepted_stati;
    }
    
    public static function getAcceptedStatiObj(){
    	if(is_null(self::$accepted_stati_obj)){
    		$stati = CardStatus::getAll();
    		foreach($stati as $status){
    			self::$accepted_stati_obj[$status->getId()] = $status;
    		}
    	}
    	return self::$accepted_stati_obj;
    }
    
    public static function getById($id) {
        return parent::getByPk($id);
    }
    
    public static function getDuplicatesByMemberId($member_id, $tradeable = true, $status_id = null, $order_settings = ['name'=>'ASC']){
        $db = Db::getInstance();
        $duplicates = array();
        if($tradeable){
        	$tradable_stati = CardStatus::getWhere(['tradeable'=>1]);
        	$sql_where['query_part']  = "WHERE owner = $member_id AND status_id IN(";
        	foreach($tradable_stati as $status){
        		$sql_where['query_part'] .= $status->getId().',';
        	}
        	$sql_where['query_part'] = substr($sql_where['query_part'] ,0,-1).')';
        	$sql_where['param_array'] = null;
        }elseif(is_int($status_id)){
        	$sql_where = self::buildSqlPart('where',['owner'=>$member_id,'status_id'=>$status_id]);
        }else{
        	throw new ErrorException('has to be set to all tradeable stati or you need to define a single status id');
        }
        $sql_order = self::buildSqlPart('order_by',$order_settings);
        $sql = "SELECT MIN(id), COUNT(id) as counter, c.* FROM cards c ".$sql_where['query_part']." GROUP BY name HAVING counter > 1 ".$sql_order['query_part'];
        
        $req = $db->prepare($sql);
        $req->execute($sql_where['param_array']);
        foreach ($req->fetchAll(PDO::FETCH_CLASS,__CLASS__) as $card){
            $duplicates[] = ['card'=>$card,'possessionCounter'=>$card->counter];
        }
        return $duplicates;
    }
    
    /**
     * @deprecated use object oriented approach
     * @param int $id
     * @param string $status
     * @param int $user member id
     * @return boolean
     */
    public static function changeStatusById($id,$status,$user) {
        $db = DB::getInstance();
        if($status == 'collect'){
            $card_name = self::getById($id)->getName();
            $req = $db->prepare('SELECT * FROM cards WHERE owner = :user AND status = :status AND name = :name');
            $req->execute(array(':status'=>$status,':name'=>$card_name,':user'=>$user));
            if($req->rowCount() > 0){
                return false;
            }
        }
        $req = $db->prepare('UPDATE cards SET status = :status WHERE id = :id and owner = :user');
        return $req->execute(array(':status'=>$status,':id'=>$id,':user'=>$user));
    }
    
    public static function dissolveCollection($deck_id,$user) {
        $db = DB::getInstance();
        $new_status_id = CardStatus::getWhere(['new'=>1])[0];
        $req = $db->prepare('UPDATE cards SET status_id = :status_id WHERE deck = :deck_id and owner = :user and status = \'collect\'');
        return $req->execute(array(':status_id'=>$new_status_id,':deck_id'=>$deck_id,':user'=>$user));
    }
    
    // TODO: rework
    public static function getMemberCardsByStatus($user_id, $status, $only_tradeable = false) {
        $cards = array();
        $db = DB::getInstance();
        
        $req = $db->prepare('SELECT * FROM cards WHERE owner = :user_id AND status = :status ORDER BY name ASC');
        $req->execute(array(':user_id'=>$user_id, ':status'=>$status));
        foreach($req->fetchAll(PDO::FETCH_CLASS,__CLASS__) as $card){
            if(!$only_tradeable OR ($only_tradeable AND $card->isTradeable())){
                $cards[] = $card;
            }
        }
        return $cards;
    }
    
    /*
     * @deprecated user update() instead
     */
    public function store() {
        return parent::update();
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getDate() {
        return $this->date;
    }
    
    public function getDeckId() {
        return $this->deck;
    }
    
    public function getDeck() {
        if(!$this->deck_obj instanceof Carddeck){
            $this->deck_obj = Carddeck::getById($this->deck);
        }
        return $this->deck_obj;
    }
    
    public function getNumber() {
        return $this->number;
    }
    
    public function getStatus() {
    	if(!$this->status instanceof CardStatus){
    		$this->status = self::getAcceptedStatiObj()[$this->getStatusId()];
    	}
    	return $this->status;
    }
    public function getStatusName() {
    	return self::getAcceptedStati()[$this->getStatusId()];
    }
    public function getStatusId() {
    	return $this->status_id;
    }
    
    public function getOwner() {
        if(!$this->owner_obj instanceof Member){
            $this->owner_obj = Member::getById($this->owner);
        }
        return $this->owner_obj;
    }
    
    public function getOwnerId(){
        return $this->owner;
    }
        
    public function setStatus($status) {
        if(in_array($status, self::getAcceptedStati())){
            $this->status = $status;
            return true;
        }else{
            return false;
        }
    }
    
    public function setOwner($member) {
    	if($member instanceof Member){
    		$this->owner = $member->getId();
    		$this->owner_obj = $member;
    	}else{
	        $this->owner = $member;
	        $this->owner_obj = Member::getById($member);
    	}
        return true;
    }
    
    public function getDeckname() {
        return $this->getDeck()->getDeckname();
    }
    
    public function getImageUrl() {
        $setting_file_type = Setting::getByName('cards_file_type');
        $deckname = $this->getDeckname();
        $url = Carddeck::getDecksFolder().$deckname.'/'.$deckname.$this->getNumber().'.'.$setting_file_type->getValue();
        return $url;
    }
    
    public function getImageHtml() {
        $url = $this->getImageUrl();
        
        $tpl_placeholder = array('[WIDTH]','[HEIGHT]','[URL]');
        $replace = array(self::$tpl_width, self::$tpl_height, $url);
        
        return str_replace($tpl_placeholder, $replace, self::$tpl_html);
    }
    
    public static function getSearchcardURL($mode='default', $number=1){
        if(!in_array($mode,['default','puzzle'])){
            $mode = 'default';
        }
        switch($mode){
            case 'default':
                $url = Setting::getByName('card_filler_general_image')->getValue();
                break;
            case 'puzzle':
                $folder_url = Setting::getByName('card_filler_puzzle_folder')->getValue();
                if(substr($folder_url,strlen($folder_url)-1,1) != '/'){
                    $folder_url.= '/';
                }
                $url = $folder_url.$number.'.'.Setting::getByName('cards_file_type')->getValue();
                break;
        }
        return $url;
    }
    
    public static function getSearchcardHtml($mode='default', $number=1) {
        $image_url = self::getSearchcardUrl($mode, $number); 
        $tpl_placeholder = array('[WIDTH]','[HEIGHT]','[URL]');
        $replace = array(self::$tpl_width, self::$tpl_height, $image_url);
        return str_replace($tpl_placeholder, $replace, self::$tpl_html);
    }
    
    public function isTradeable() {
        if(is_null($this->is_tradeable)){
            $this->is_tradeable = false;
            if($this->getStatus()){
                // TODO: make use of: Trade::getWhere($condition)
                $query = 'SELECT count(*) FROM trades WHERE (offered_card = '.$this->id.' OR requested_card = '.$this->id.') AND status = \'new\' ';
                $trades = $this->db->query($query)->fetchColumn();
                if($trades == 0){
                    $this->is_tradeable = true;
                }
            }
        }
        return $this->is_tradeable;
    }
    
    //TODO: rework random card function completly
    public static function createRandomCard($user_id,$number=1,$sys_text_code=null) {
        if(intval($number) < 0){ 
            throw new Exception('Keine gültige Anzahl übergeben!');
        }
        $cards = array();
        $decksize = Setting::getByName('cards_decksize')->getValue();
        $db = DB::getInstance();
        
        for($i = 0; $i < $number; $i++){
            // grab deck data randomly
            $req = $db->query('SELECT * FROM decks WHERE status = \'public\' ORDER BY RAND() LIMIT 1');
            if($req->rowCount() > 0){
                $deckdata = $req->fetchObject('Carddeck');
                // insert created data to insert into DB for a new Card Object
                
                $random_card['number'] = mt_rand(1,$decksize);
                $random_card['name'] = $deckdata->getDeckname().$random_card['number'];
                $card_values = array('owner'=>$user_id,'deck'=>$deckdata->getId(),'number'=>$random_card['number'],'name'=>$random_card['name'],'date'=>date('Y-m-d G:i:s'));
                
                if(!is_null($user_id)){
                    $card = new Card();
                    $card->setPropValues($card_values);
                    $card_id = $card->create();
                    $card->setPropValues(['id'=>$card_id]);
                    $cards[] = $card;
                    
                    $log_text = ' -> '.$card->getName().' (#'.$card->getId().') erhalten.';
                    Tradelog::addEntry($user_id, $sys_text_code, $log_text);
                }else{
                    $cards[] = $card_values; 
                }
            }
        }
        if(!is_null($user_id)){
            Member::getById($user_id)->checkLevelUp();
        }
        
        return $cards;
    }
    
    
    // TODO: Refactor
    public static function takeCardsFromUpdate($user_id,$update_id) {
        $cards = array();
        $db = Db::getInstance();
         
        try{
            $update_decks = Carddeck::getInUpdate($update_id);
            $decksize = Setting::getByName('cards_decksize')->getValue();
            $db->beginTransaction();
            foreach($update_decks as $deck){
                // insert created data to insert into DB for a new Card Object
                $card_props['number'] = mt_rand(1,$decksize);
                $card_props['name'] = $deck->getDeckname().$card_props['number'];
                $card_props['date'] = date('Y-m-d G:i:s');
                $card_props['deck'] = $deck->getId();
                $card_props['owner'] = $user_id;
                
                $card = new Card();
                $card->setPropValues($card_props);
                $card_id = $card->create();
                $card->setPropValues(['id'=>$card_id]);
                
                $cards[] = $card;
            }
            if(count($cards) > 0){
                $req = $db->prepare('INSERT INTO updates_members (update_id, member_id) VALUES (:update_id,:user_id) ');
                $req->execute(array(':update_id'=>$update_id,':user_id'=>$user_id));
            }
            
            // add entry for each card in member tradelog
            foreach($cards as $card){
                Tradelog::addEntry($user_id, 'Cardupdate -> '.$card->getName().' (#'.$card->getId().') erhalten.');
            }
            
            $db->commit();
            Member::getById($user_id)->checkLevelUp();
            return $cards;
        }
        catch(Exception $e) {
            $db->rollBack();
            return $e->getMessage();
        }
        
    }
    
    /**
     * Get Member who owns a specific card with status 'trade' 
     * @param int $deck_id
     * @param int $number
     * @return Member[]
     */
    public static function findTrader($deck_id, $number){
        
        $db = Db::getInstance();
        $trader = array();
        
        $query = 'SELECT DISTINCT members.*, max(cards.id) as card_id FROM cards 
                JOIN members ON members.id = owner
                LEFT JOIN trades ON trades.status = \'new\' AND (requested_card = cards.id OR offered_card = cards.id)
				LEFT JOIN cards_stati ON cards_stati.id = status_id
                WHERE cards_stati.tradeable = \'1\' AND trades.status IS NULL AND deck= ? AND number = ?
                GROUP BY members.id 
                ORDER BY members.name ASC
                ';
        $req = $db->prepare($query);
        if($req->execute([$deck_id,$number])){
            foreach($req->fetchALL(PDO::FETCH_CLASS,'Member') as $member){
                
                $trader[$member->card_id] = $member; 
                
            }
        }
        
        return $trader;
    }
    
}