<?php

namespace IPP\Student;

class ExceptionRunner extends \Exception {
  public function __construct(string $message, int $code) {
    parent::__construct($message, $code);

    fwrite(STDERR, $message);

    exit($code);
  }
}
