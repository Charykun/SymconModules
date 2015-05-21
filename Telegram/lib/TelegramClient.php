<?php

/**
 * Telegram Client
 */
class TelegramClient {

  /**
   * Running parameters to pass to the process.
   *
   * @var array
   */
  protected $params;

  /**
   * Process wrapper
   *
   * @var TelegramProcess
   */
  protected $process;

  /**
   * @var TelegramLogger
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @var TelegramProcess $process
   *   Mixed params
   * @var TelegramLogger $logger
   *   Logging interface.
   */
  public function __construct($process, $logger) {
    $this->process = $process;
    $this->logger = $logger;
  }

  /**
   * Send message to peer.
   */
  public function sendMessage($peer, $message) {
    $output = $this->execCommand('msg', $peer, $message);
    // @todo Parse output and get success / failure.
    return TRUE;
  }

  /**
   * Get contact list.
   *
   * @return array
   *   Contact objects indexed by phone number.
   */
  public function getContactList() {
	  if ($this->execCommand('contact_list')) {
	    return $this->parseContactList();
	  }
  }

  /**
   * Parse contact list from response.
   */
  protected function parseContactList() {
    $pattern = array(
      0 => '/([A-Z][\w]+)\s?([A-Z][\w]+)?/',
    );
    $mapping = array(
	    0 => 'string',
	    1 => 'first_name',
            2 => 'last_name',
    );    
    return $this->parseResponse($pattern, $mapping);
  }

  /**
   * Get list of current dialogs.
   *
   * @todo Remove filters, just get dialog list
   * @params filter 1 for all, 2 for read, 3 for unread
   */
  public function getDialogList($filter = 1) {
    if ($this->execCommand('dialog_list')) {
      // @todo Add the right regexp format for the response.

      if ($filter == 1)
        {
          $pattern = array(
            0=> '/^User\s([\w\s]+)\:\s(\d+)\s(\w+)$/u',
          );
        }
      if ($filter == 2)
      {
      	 $pattern = array(
      	   0 => '/^User\s([\w\s]+)\:\s(0)\s(\w+)$/u',
      	 );
      }
      if ($filter == 3)
      {
      	$pattern = array(
      	  0 => '/^User\s([\w\s]+)\:\s(1)\s(\w+)$/u',
      	);
      }
      $mapping = array(
        0 => 'string',
        1 => 'user',
        2 => 'messages',
        3 => 'state'
      );
      return $this->parseResponse($pattern, $mapping);
    }
  }

  /**
   * Add contact
   *
   * Add contact can change a name contact
   *
   * @param string $phone
   *   Phone number, with country code but without '+' nor '00'
   * @param string $first_name
   *   Contact's first name, a single word.
   * @param string $first_name
   *   Contact's last name, a single word.
   *
   * @return TelegramContact|NULL
   */
  public function addContact($phone, $first_name, $last_name) {
  	if ($this->execCommand('add_contact', $phone, $first_name,  $last_name)) {
  	  $contacts = $this->parseContactList();
  	  return reset($contacts);
  	}
  }
  
  /**
   * Del contact
   *
   * @param string $user
   *   Contact name with spaces replaced by '_'
   *
   * @return TelegramContact|NULL
   */
  public function delContact($user) {
  	if ($this->execCommand('del_contact', $user)) {
  	  $contacts = $this->parseContactList();
  	  return reset($contacts);
  	}
  }  

  /**
   * Rename contact
   *
   * @param string $peer
   *   Peer name or contact name with spaces replaced by '_'
   *
   * @param string $first_name
   *   Contact's first name, a single word.
   * @param string $lat_name
   *   Contact's last name, a single word.
   *
   * @return TelegramContact|NULL
   */
  public function renameContact($peer, $first_name, $last_name){
  	if ($this->execCommand('rename_contact', $peer, $first_name,  $last_name)) {
  	  $contacts = $this->parseContactList();
  	  return reset($contacts);
  	}
  	return NULL;
  }

  /**
   * Get contact information
   *
   * @param string $peer
   *   Peer name or contact name with spaces replaced by '_'
   *
   * @return TelegramContact|NULL
   */
  public function getContactInfo($peer){
  	if ($this->execCommand('user_info', $peer)) {
  	  // Response has the form
  	  //   User Jose Reyero:
      //   real name: Jose Reyero
      //   phone: 34626472653
      //   offline (was online [2014/03/17 19:32:33])
  	  // Parse in 2 stages.
  	  $pattern = array(
  	    0 => '/^(User)\s([\w\s]+)\:$/u',
  	    1 => '/^real\s(name)\:\s([\w\s]+)$/u',
  	    2 => '/^(phone)\:\s(\d+)$/',
  	  );
  	  $mapping = array('string', 'type', 'data');
  	  $info = $this->parseResponse($pattern, $mapping);
  	  // If we get user data, parse second stage.
  	  if ($info) {
  	    $data = array();
  	    foreach ($info as $item) {
  	      $data[$item->type] = $item->data;
  	    }
  	    $contact = new TelegramContact($data);
  	    // Now get status data.
  	    $pattern = array(
    	    3 => '/^(online)$/',
    	    4 => '/^(offline)\s\(was\sonline\s\[([\d\/]+)\s([\d\:]+)\]\)/',
    	  );
    	  $mapping = array(
    	    0 => 'string',
    	    1 => 'status',
    	    2 => 'date',
    	    3 => 'time',
    	  );
    	  if ($status_data = $this->parseResponse($pattern, $mapping)) {
    	    $status = reset($status_data);
    	    unset($status->line);
    	    $contact->setData($status);
    	  }
  	    return $contact;
  	  }
  	}
  	return NULL;
  }

 /**
  * Get peer's history
  *
  * @param string $peer
  *   Peer name or contact name with spaces replaced by '_'
  * @param int $limit
  *   Maximum number of messages, defaults to 40
  *
  * @return array
  *   Array of message objects
  */
  public function getHistory($peer, $limit = 40){
  	if ($this->execCommand('history', $peer, $limit)) {
  	  $pattern = array(
  	    0 => '/(\d+)\s\[(.*.)\]\s+(.*.)\s(«««|»»»|<<<|>>>)(.*)/u',
  	  );

  	  $mapping = array(
  	    0 => 'string',
    	  1 => 'idmsg',
    	  2 => 'date',
    	  3 => 'name',
    	  4 => '_direction',
    	  5 => 'text',
  	  );

  	  $translator = function($data) {
  	    if ($data['_direction'] == '«««' || $data['_direction'] == '<<<') {
  	      $data['direction'] = 'incoming';
  	    }
  	    else {
  	      $data['direction'] = 'outgoing';
  	    }
  	    $data['peer'] = TelegramContact::nameToPeer($data['name']);
  	    return new TelegramMessage($data);
  	  };

  	  return $this->parseResponse($pattern, $mapping, 'idmsg', $translator);
  	}
  }

  /**
   * Mark as read messages of a peer
   *
   * @param string $peer
   *   Peer name or contact name with spaces replaced by '_'
   */
  public function markAsRead($peer){
  	$output = $this->execCommand('mark_read', $peer);
  	return TRUE;
  }

  /**
   * Run command through telegram CLI
   *
   * @param string $command
   *   Telegram CLI command to execute.
   * @param string $arg1, $arg2...
   *   Optional, variable number of arguments for the command.
   */
  protected function execCommand() {
    // Make sure process is started.
    if ($process = $this->getProcess()) {
      $args = func_get_args();
      $command = array_shift($args);
      return $process->execCommand($command, $args);
    }
  }

  /**
   * Parse process response.
   *
   * @param strwhatsapping|array $pattern
   *   Regexp with the response format.
   * @param array mapping
   *   Field mapping.
   * @param string index_field
   *   Field to use for indexing the resulting array.
   * @param callable $translate
   *   Callback function to translate each response array.
   *   If none, it will be just converted into an object.
   *
   * @return array
   *   Response array with objects indexed by index_field.
   */
  protected function parseResponse($pattern = NULL, $mapping = array(), $index_field = NULL, $translator = NULL) {
    $response = array();
    if (($process = $this->getProcess()) && ($list = $process->parseResponse($pattern, $mapping))) {
      foreach ($list as $key => $data) {
        $index = $index_field && isset($data[$index_field]) ? $data[$index_field] : $key;
        $response[$index] = $translator ? call_user_func($translator, $data) : (object)$data;
      }
    }
    return $response;
  }

  /**
   * Translate reponse and map into class.
   */
  protected function translateResponse($response, $translation, $class = NULL) {

  }

  /**
   * Get process, make sure it it started.
   *
   * @return TelegramProcess
   *   Started telegram process.
   */
  function getProcess() {
    if ($this->start()) {
      return $this->process;
    }
  }

  /**
   * Get logger.
   *
   * @return TelegramLogger
   */
  function getLogger() {
    return $this->logger;
  }

  /**
   * Start process.
   *
   * @return boolean
   *   TRUE if process started successfully.
   */
  public function start() {
    if (isset($this->process)) {
      return $this->process->start();
    }
  }

  /**
   * Exit process (send quit command).
   */
  public function stop() {
    if (isset($this->process)) {
      $this->log('Client stopping process');
      $this->process->stop();
      unset($this->process);
    }
  }

  /**
   * Shorthand for debug message.
   */
  protected function debug($message, $args = NULL) {
    $this->logger->logDebug($message, $args);
  }

  /**
   * Shorthand for log message.
   *
   * @param mixed $message
   */
  protected function log($message, $args = NULL) {
    $this->logger->logInfo($message, $args);
  }

  /**
   * Get logged messages.
   */
  function getLogs() {
    return $this->logger->formatLogs();
  }

  /**
   * Class destructor.
   */
  public function __destruct() {
    $this->stop();
    unset($this->logger);
  }

}
