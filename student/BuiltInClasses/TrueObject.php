<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\BuiltInClasses\LiteralObject;


class TrueObject extends LiteralObject {
  public function __construct(bool $value) {
    parent::__construct();

    if ($value !== true) {
      throw new \InvalidArgumentException("Value must be a true");
    }

    $this->value = (bool)$value;

    $this->methods['not'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("not method requires no arguments");
      }

      return new FalseObject(false);
    };


    $this->methods['and:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("and: method requires exactly one argument");
      }

      return $args[0] ? new TrueObject(true) : new FalseObject(false);
    };

    $this->methods['or:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("or: method requires exactly one argument");
      }

      return new TrueObject(true);
    };



    $this->methods['ifTrue:ifFalse:'] = function (array $args) {
      if (count($args) !== 2) {
        throw new \InvalidArgumentException("ifTrue:ifFalse: method requires exactly two arguments");
      }

      return $args[0];
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
