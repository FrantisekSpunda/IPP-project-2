<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\Interpreter;


abstract class LiteralObject {
  protected mixed $value;
  protected Interpreter $interpreter;

  abstract public function getValue(): mixed;

  public array $methods;

  public function __construct() {
    $this->methods = [
      'from:' => function (array $args) {
        if (count($args) !== 1) {
          throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
        }

        return new self($args[0], $this->interpreter);
      },
      'identicalTo:' => function (array $args) {
        if (count($args) !== 1) {
          throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
        }

        if (!($args[0] instanceof LiteralObject)) {
          throw new \InvalidArgumentException("Argument must be an instance of LiteralObject");
        }

        return $this->value === $args[0]->getValue() ? new TrueObject(true) : new FalseObject(false);
      },
      'asString' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("asString: method requires no arguments");
        }

        return (new StringObject((string)$this->value, $this->interpreter));
      },
      'isNumber' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isNumber: method requires no arguments");
        }

        return ($args[0] instanceof IntegerObject) ? new TrueObject(true) : new FalseObject(false);
      },
      'isString' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isString: method requires no arguments");
        }

        return ($args[0] instanceof StringObject) ? new TrueObject(true) : new FalseObject(false);
      },
      'isBlock' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isBlock: method requires no arguments");
        }

        return ($args[0] instanceof BlockObject) ? new TrueObject(true) : new FalseObject(false);
      },
      'isNil' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isNil: method requires no arguments");
        }

        return ($args[0] instanceof NilObject) ? new TrueObject(true) : new FalseObject(false);
      },
    ];
  }
}
