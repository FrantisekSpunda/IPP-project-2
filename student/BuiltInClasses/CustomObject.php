<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\Interpreter;

class CustomObject extends LiteralObject {

  public function __construct(mixed $value, Interpreter $interpreter, string $className, string $parentClassName) {
    parent::__construct($value, $interpreter);

    $this->className = $className;
    $this->parentClassName = $parentClassName;

    $this->methods['new'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("identicalTo: method requires no argument");
      }

      return new self($this->value, $this->interpreter, $this->className, $this->parentClassName);
    };

    $this->methods['from:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      return new self($args[0], $this->interpreter, $this->className, $this->parentClassName);
    };
  }

  public function __toString() {
    return (string)$this->className;
  }

  public function getClassName(): string {
    return $this->className;
  }

  public function getValue(): mixed {
    return $this->value;
  }

  public function setValue(mixed $value): void {
    $this->value = $value;
  }
}
