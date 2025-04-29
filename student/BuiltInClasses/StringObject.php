<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\Interpreter;

class StringObject extends LiteralObject {
  protected Interpreter $interpreter;

  public function __construct(string $value, Interpreter $interpreter) {
    parent::__construct($value, $interpreter);

    $this->value = json_decode('"' . $value . '"');

    $this->value = preg_replace_callback('/\\\\([nt\\"\'\\\\])/', function ($matches) {
      $escapes = [
        'n' => "\n",
        't' => "\t",
        '"' => '"',
        "'" => "'",
        '\\' => '\\',
      ];
      return $escapes[$matches[1]];
    }, $value);

    $this->methods['from:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
      }

      return new self((string)$args[0]->getValue(), $this->interpreter);
    };

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

      return is_numeric($this->value) ? new IntegerObject((int)$this->value, $this->interpreter) : new NilObject(0, $this->interpreter);
    };

    $this->methods['concatenateWith:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("print method requires 1 argument");
      }

      if (!($args[0] instanceof StringObject)) {
        return new NilObject(0, $this->interpreter);
      }

      return new self($this->getValue() . $args[0]->getValue(), $this->interpreter);
    };

    $this->methods['startsWith:endsBefore:'] = function (array $args) {
      if (count($args) !== 2) {
        throw new \InvalidArgumentException("method requires 2 arguments");
      }

      if ($args[0]->getValue() <= 0 || $args[1]->getValue() <= 0) {
        return new NilObject(0, $this->interpreter);
      }

      if ($args[1]->getValue() - $args[0]->getValue() <= 0) {
        return new StringObject('', $this->interpreter);
      }

      return new StringObject(substr($this->value, $args[0]->getValue() - 1, $args[1]->getValue() - $args[0]->getValue()), $this->interpreter);
    };


    $this->methods['equalTo:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("equalTo: method requires exactly one argument");
      }

      if ($args[0]->getValue() instanceof StringObject) $args[0]->setValue($args[0]->getValue()->getValue());

      return ((string)$this->value === (string)$args[0]->getValue()) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
    };
  }

  public function __toString() {
    return $this->value ?? '';
  }

  public function getValue(): string {
    return $this->value ?? '';
  }

  public function setValue(int $value): void {
    $this->value = $value;
  }
}
