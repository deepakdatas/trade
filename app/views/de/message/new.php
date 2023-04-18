<h1>Nachrichten</h1>

<div class="text-right"><a class="btn btn-dark" href="<?php echo Routes::getUri('messages_recieved')?>">zum Posteingang</a></div>

<form name="new-message" method="POST" action="">
    <div class="card my-4">
    	<div class="card-header">
    		Nachricht verfassen
    	</div>
    	<div class="card-body">
        	<div class="row">
        		<div class="col-4">Empfänger</div>
        		<div class="col-8">
        			<select class="form-control" name="recipient" required>
        				<option value=""></option>
        				<?php foreach(Member::getWhere(['status'=>'default']) as $recipient){ if($recipient->getId() != $this->login->getUser()->getId()) { ?>
        					<option value="<?php echo $recipient->getId(); ?>" <?php if($recipient)?>><?php echo $recipient->getName(); ?></option>
        				<?php } } ?>
        			</select>
        		</div>
        	</div>
        	<hr>
        	<div class="my-2">Deine Nachricht:</div>
        	<textarea class="form-control" rows="8" style="max-height: 75vh;" name="text" placeholder="schreib etwas..." required></textarea>
        	<small>Du kannst <a href="https://de.wikipedia.org/wiki/Markdown#Auszeichnungsbeispiele" target="_blank">Markdown</a> 
					verwenden. HTML ist nicht erlaubt!</small>
    	</div>
    </div>
    
    <div class="text-center">
    	<button class="btn btn-dark" type="reset" name="action" value="reset">zurücksetzen</button>
    	&bull;
    	<button class="btn btn-primary" type="submit" name="action" value="submit">absenden</button>
    </div>
    
    <input type="hidden" name="sender" value="<?php echo $this->login->getUser()->getId(); ?>">
</form>