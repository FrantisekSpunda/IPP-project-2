<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\Interpreter;

class StringObject extends LiteralObject {
  protected Interpreter $interpreter;

  public function __construct(string $value, Interpreter $interpreter) {
    parent::__construct();

    $this->value = $value;
    $this->interpreter = $interpreter;

    $this->methods['print'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("print method requires no arguments");
      }

      echo $this->value;

      return $this;
    };

    $this->methods['read'] = function (array $args) {
      $this->value = $this->interpreter->readInput();
      return $this;
    };

    $this->methods['asInteger'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("print method requires no arguments");
      }

      return is_numeric($this->value) ? new IntegerObject((int)$this->value, $this->interpreter) : new NilObject(0);
    };
  }

  public function __toString() {
    return $this->value;
  }

  public function getValue(): int {
    return $this->value;
  }

  public function setValue(int $value): void {
    $this->value = $value;
  }
}
