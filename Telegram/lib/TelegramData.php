<?php

abstract class TelegramData {

  /**
   * Primary object id.
   *
   * @var int
   */
  public $oid = NULL;

  /**
   * Timestamps
   *
   * @var int
   */
  public $created;
  public $updated;

  /**
   * The string this was parsed from.
   */
  public $string;

  /**
   * Construct from array.
   */
  public function __construct($data = NULL) {
    if ($data) {
      $this->setData($data);
    }
  }

  /**
   * Set data from array / object.
   */
  public function setData($data) {
    foreach ((array)$data as $name => $value) {
      if (isset($value)) {
        $this->$name = $value;
      }
    }
  }
}
