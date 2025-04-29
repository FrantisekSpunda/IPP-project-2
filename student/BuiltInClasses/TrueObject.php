<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\Interpreter;

class TrueObject extends LiteralObject {
  public function __construct(bool $value, Interpreter $interpreter) {
    parent::__construct($value, $interpreter);
    $this->value = true;

    $this->methods['new'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("identicalTo: method requires no argument");
      }

      return new self(true, $this->interpreter);
    };

    $this->methods['identicalTo:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      if (!($args[0] instanceof LiteralObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of LiteralObject");
      }

      return $this->value == $args[0]->getValue() ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
    };

    $this->methods['not'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("not method requires no arguments");
      }

      return new FalseObject(false, $this->interpreter);
    };


    $this->methods['and:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("and: method requires exactly one argument");
      }

      return $this->interpreter->executeSend('value', $args[0], []);
    };

    $this->methods['or:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("or: method requires exactly one argument");
      }

      return new TrueObject(true, $this->interpreter);
    };



    $this->methods['ifTrue:ifFalse:'] = function (array $args) {
      if (count($args) !== 2) {
        throw new \InvalidArgumentException("ifTrue:ifFalse: method requires exactly two arguments");
      }

      return $this->interpreter->executeSend('value', $args[0], []);
    };
  }

  public function __toString() {
    return (string)$this->value;
  }

  public function getValue(): int {
    return $this->value;
  }

  public function setValue(int $value): void {
    $this->value = $value;
  }
}
