<?php

namespace IPP\Student\BuiltInClasses;


class NilObject extends LiteralObject {
  public function __construct(int $value) {
    parent::__construct();

    $this->value = $value;

    $this->methods['asString'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("print method requires no arguments");
      }

      return new StringObject('nil', $this->interpreter);
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
