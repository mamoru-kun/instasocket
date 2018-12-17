<?php

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

class Config {
  public $ACCESS_TOKEN;
  public $COUNT;
  public $REFRESH_DELAY;
  public $PORT;
  public $ALLOWED_ORIGINS;
  public $DEBUG_MODE;

  function __construct() {
    $this->ACCESS_TOKEN = getenv('ACCESS_TOKEN');
    $this->COUNT = getenv('COUNT');
    $this->REFRESH_DELAY = getenv('REFRESH_DELAY');
    $this->PORT = getenv('PORT');
    $this->ALLOWED_ORIGINS = getenv('ALLOWED_ORIGINS');
    $this->DEBUG_MODE = getenv('DEBUG_MODE');
  }
}
