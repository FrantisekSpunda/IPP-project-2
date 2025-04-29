<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\Interpreter;

class NilObject extends LiteralObject {
  public function __construct(int $value, Interpreter $interpreter) {
    parent::__construct($value, $interpreter);

    $this->methods['new'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("identicalTo: method requires no argument");
      }

      return new self(0, $this->interpreter);
    };

    $this->methods['asString'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("print method requires no arguments");
      }

      return new StringObject('nil', $this->interpreter);
    };


    $this->methods['identicalTo:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      if (!($args[0] instanceof LiteralObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of LiteralObject");
      }

      return $this->value === $args[0]->getValue() ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
    };


    $this->methods['equalTo:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      if (!($args[0] instanceof LiteralObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of LiteralObject");
      }

      return $this->value === $args[0]->getValue() ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
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
