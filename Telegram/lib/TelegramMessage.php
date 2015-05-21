<?php

/**
 * Incoming / outgoing messages.
 */
class TelegramMessage extends TelegramData {

  /**
   * Message status.
   */
  const STATUS_DONE = 0;
  const STATUS_PENDING = 1;
  const STATUS_ERROR = 2;

  /**
   * Telegram message id.
   *
   * @var string
   */
  public $idmsg;

  /**
   * Message text.
   *
   * @var string
   */
  public $text;

  /**
   * Other source / destination parameters.
   *
   * @var string
   */
  public $contactid;
  public $name;
  public $phone;
  public $source;
  public $peer;


  /**
   * Message status (incoming, outgoing).
   *
   * @var string
   */
  public $direction = 'incoming';

  /**
   * Possible values are 0 = done, 1 = queued, 2 = error
   */
  public $status = 0;

  /**
   * Drupal User id.
   */
  public $uid = 0;


  /**
   * Set destination contact.
   *
   * @param TelegramContact $contact
   */
  public function setDestination($contact) {
    $this->direction = 'outgoing';
    return $this->setContact($contact);
  }

  /**
   * Set contact data to message.
   *
   * @param TelegramContact $contact
   */
  public function setContact($contact) {
    foreach (array('idcontact', 'phone', 'peer', 'name', 'uid') as $field) {
      if (isset($contact->$field)) {
        $this->$field = $contact->$field;
      }
    }
    return $this;
  }

  /**
   * Get status list.
   *
   * @return array
   *   List of possible status value with translated names.
   */
  public static function getStatusList() {
    return array(
      static::STATUS_DONE => t('Done'),
      static::STATUS_PENDING => t('Pending'),
      static::STATUS_ERROR => t('Error'),
    );
  }

  /**
   * Format message status.
   *
   * @return string
   *   Message status name.
   */
  public function formatStatus() {
    $list = $this->getStatusList();
    return isset($this->status) && isset($list[$this->status]) ? $list[$this->status] : t('Unknown');
  }

}
