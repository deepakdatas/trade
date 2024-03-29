<?php
/**
 * 
 * Represents a Message
 * 
 * @author NekoCari
 *
 */

class Message extends DbRecordModel {
    
    protected $id, $date, $utc, $sender_id, $recipient_id, $text, $status, $deleted;
    
    private $sender_obj, $recipient_obj;
    
    protected static
        $db_table = 'messages',
        $db_pk = 'id',
        $db_fields = array('id','date','utc','sender_id','recipient_id','text','status','deleted'),
        $sql_order_by_allowed_values = array('id','date','utc','status','sender','recipient');
        
        private static $accepted_status_values = array('new','all');
        private static $accepted_deleted_values = array('sender','recipient');
    
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * get message objects for member - msg status optional
     * @param int $id member id
     * @param string $status new|all
     * @return Message[]
     */
    public static function getReceivedByMemberId($id, $status = 'all') {
        return self::getByMemberId($id, 'recipient', $status);
    }
    
    /**
     * get message objects sent by member - status optional
     * @param int $id member id
     * @param string $status new|all
     * @return Message[]
     */
    public static function getSentByMemberId($id, $status = 'all') {
       return self::getByMemberId($id, 'sender', $status);
    }
    
    /**
     * get message objects by member id
     * @param int $id
     * @param string $type
     * @param string $tatus
     * @return Message[]
     */
    public static function getByMemberId($id, $type, $status='all', $order_settings=['status'=>'ASC','utc'=>'DESC']){
        if(!in_array($type,['sender','recipient'])){
            $type = 'recipient';
        }
        if(!in_array($status, self::$accepted_status_values)){
            $status = self::$accepted_status_values[0];
        }
        
        $where = $type.'_id = '.intval($id).' AND (deleted != \''.$type.'\' OR deleted IS NULL) ';
        
        if($status == 'new'){
            $where.= ' AND status = \'new\'';
        }
        
        return parent::getWhere($where,$order_settings);
    }
    
    /**
     * adds/sends a new message 
     * @param int $sender
     * @param int $recipient
     * @param string $text
     * @return Message
     */
    public static function add($sender, $recipient, $text){
        // remove HTML
        $text = strip_tags($text);
        
        $message = new Message();
        $message->setPropValues(['sender_id'=>$sender, 'recipient_id'=>$recipient, 'text'=>$text]);
        $message->create();
        
        return $message;
    }
    
    /**
     * change msg status to read and updates database entry;
     * @return boolean
     */
    public function read(){
        $this->status = 'read';
        return $this->update();
    }
    
    /**
     * returns if true if message is unread
     * @return boolean
     */
    public function isNew() {
        $is_new = false;
        if($this->status == 'new'){
            $is_new = true;
        }
        return $is_new;
    }
    
    /**
     * sets a delete flag or deletes if msg was already flagged by the other party
     * 
     * @return boolean
     */
    public function deleteForUser($login){
    	
    	if($login->isLoggedIn()){
            $user_id = $login->getUserId();
            $user_type = NULL;
            if($this->recipient_id == $user_id){
                $user_type = 'recipient';
            }else{
                $user_type = 'sender';
            }
            
            // user is recipient and sender is null -> system message
            if($user_type == 'recipient' AND $this->sender_id == NULL){
                return parent::delete();
            }
            
            // message has not been deleted by anyone -> NULL
            if($this->deleted == NULL){
                // set deleted to current user type and update
                $this->deleted = $user_type;
                return $this->update();
            }else{
                // if deleted is other than current user type
                if($this->deleted != $user_type){
                    // delete db entry 
                    return parent::delete();
                }else{
                    // do nothing
                }
            }
        }
        return false;
    }
    
    // GETTER
    
    public function getId() {
        return $this->id;
    }
    
    public function getDate($timezone = DEFAULT_TIMEZONE) {
    	$date = new DateTime($this->utc);
    	$date->setTimezone(new DateTimeZone($timezone));
    	return $date->format(Setting::getByName('date_format')->getValue().' - H:i');
    }
    
    public function getSender() {
        if(!$this->sender_obj instanceof Member){
            $this->sender_obj = Member::getById($this->sender_id);
            if(is_null($this->sender_obj)){
                $this->sender_obj = new Member();
                $this->sender_obj->setName('System');
            }
        }
        return $this->sender_obj;
    }
    
    
    public function getRecipient() {
        if(!$this->recipient_obj instanceof Member){
            $this->recipient_obj = Member::getById($this->recipient_id);
        }
        return $this->recipient_obj;
    }
    
    public function getText() {
    	$parsedown = new Parsedown();
    	return $parsedown->text($this->text);
    }
    
    public function getTextPlain() {
    	return $this->text;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function isSystemMessage() {
    	return is_null($this->getSender()->getId());
    }
    
    
}