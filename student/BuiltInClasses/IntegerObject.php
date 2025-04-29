<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\Interpreter;


class IntegerObject extends LiteralObject {
  protected Interpreter $interpreter;

  public function __construct(int $value, Interpreter $interpreter) {
    parent::__construct((int)$value, $interpreter);

    $this->methods['from:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      return new self((int)$args[0]->getValue(), $this->interpreter);
    };

    $this->methods['equalTo:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("equalTo: method requires exactly one argument");
      }

      return ((int)$this->value == (int)$args[0]->getValue()) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
    };

    $this->methods['asInteger'] = function (array $args) {
      if (count($args) !== 0) {
        throw new \InvalidArgumentException("asInteger: method requires no arguments");
      }

      return $this;
    };

    $this->methods['divBy:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("divBy: method requires exactly one argument");
      }

      if (!($args[0] instanceof IntegerObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of IntegerObject");
      }

      if ($args[0]->getValue() === 0) {
        throw new \DivisionByZeroError("Cannot divide by zero");
      }

      return new IntegerObject($this->value / $args[0]->getValue(), $this->interpreter);
    };

    $this->methods['plus:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("plus: method requires exactly one argument");
      }

      if (!($args[0] instanceof IntegerObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of IntegerObject");
      }

      return new IntegerObject($this->value + $args[0]->getValue(), $this->interpreter);
    };

    $this->methods['minus:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("minus: method requires exactly one argument");
      }

      if (!($args[0] instanceof IntegerObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of IntegerObject");
      }

      return new IntegerObject($this->value - $args[0]->getValue(), $this->interpreter);
    };

    $this->methods['multiplyBy:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("multiplyBy: method requires exactly one argument");
      }

      return new IntegerObject($this->value * $args[0]->getValue(), $this->interpreter);
    };

    $this->methods['greaterThan:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("greaterThan: method requires exactly one argument");
      }

      if (!($args[0] instanceof IntegerObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of IntegerObject");
      }

      return $this->value > $args[0]->getValue() ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
    };

    $this->methods['timesRepeat:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("greaterThan: method requires exactly one argument");
      }

      for ($n = 1; $n <= $this->getValue(); $n++) {
        $this->interpreter->executeSend('value', $args[0], [new IntegerObject($n, $this->interpreter)]);
      }

      return $this->interpreter->variableTable->lastAssign;
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
