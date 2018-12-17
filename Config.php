<?php

class Config {
  const ACCESS_TOKEN = getenv('ACCESS_TOKEN');
  const COUNT = getenv('COUNT');
  const REFRESH_DELAY = getenv('REFRESH_DELAY');
  const PORT = getenv('PORT');
  const ALLOWED_ORIGINS = getenv('ALLOWED_ORIGINS');

  //
  // to allow any connection origin use the line below instead
  // const ALLOWED_ORIGINS = "*";
  //
  const DEBUG_MODE = getenv('DEBUG_MODE');
}
