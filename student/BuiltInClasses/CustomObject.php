<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\Interpreter;

class CustomObject extends LiteralObject {
  public string $className;

  public function __construct(mixed $value, Interpreter $interpreter, string $className) {
    parent::__construct();

    $this->className = $className;
    $this->value = $value;
    $this->interpreter = $interpreter;

    $this->methods['from:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      return new self($args[0], $this->interpreter, $this->className);
    };
  }

  public function __toString() {
    return (string)$this->className;
  }

  public function getClassName(): string {
    return $this->className;
  }

  public function getValue(): int {
    return $this->value;
  }

  public function setValue(int $value): void {
    $this->value = $value;
  }
}
