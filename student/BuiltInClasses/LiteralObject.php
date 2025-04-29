<?php

namespace IPP\Student\BuiltInClasses;

use IPP\Student\Interpreter;


class LiteralObject {
  protected mixed $value;
  public string $className;
  public string $parentClassName;
  protected Interpreter $interpreter;


  /** 
   * @var array<string, callable> 
   */
  public array $methods = [];

  public function __construct(mixed $value, Interpreter $interpreter) {
    $this->className = $this::class;
    $this->parentClassName = '';
    if ($value instanceof LiteralObject) $value = $value->getValue();
    $this->value = $value;
    $this->interpreter = $interpreter;

    $this->methods = [
      'new' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("identicalTo: method requires no argument");
        }

        return new self(0, $this->interpreter);
      },
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

        return $this === $args[0] ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
      },
      'equalTo:' => function (array $args) {
        if (count($args) !== 1) {
          throw new \InvalidArgumentException("identicalTo: method requires exactly one argument");
        }

        if (!($args[0] instanceof LiteralObject)) {
          throw new \InvalidArgumentException("Argument must be an instance of LiteralObject");
        }

        return $this->value == $args[0]->getValue() ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
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

        return ($this instanceof IntegerObject) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
      },
      'isString' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isString: method requires no arguments");
        }

        return ($this instanceof StringObject || ($this instanceof CustomObject && $this->parentClassName == 'String')) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
      },
      'isBlock' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isBlock: method requires no arguments");
        }

        return ($this instanceof BlockObject) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
      },
      'isNil' => function (array $args) {
        if (count($args) !== 0) {
          throw new \InvalidArgumentException("isNil: method requires no arguments");
        }

        return ($this instanceof NilObject) ? new TrueObject(true, $this->interpreter) : new FalseObject(false, $this->interpreter);
      },
    ];
  }

  public function getValue(): mixed {
    return $this->value;
  }

  public function __toString() {
    return $this->value;
  }
}
