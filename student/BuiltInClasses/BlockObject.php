<?php

namespace IPP\Student\BuiltInClasses;

use DOMElement;
use IPP\Student\Interpreter;

class BlockObject extends LiteralObject {
  protected Interpreter $interpreter;

  public function __construct(DOMElement $value, Interpreter $interpreter) {
    parent::__construct($value, $interpreter);

    $this->methods['whileTrue:'] = function (array $args) {
      if (count($args) !== 1) {
        throw new \InvalidArgumentException("whileTrue: method requires exactly one argument");
      }

      if (!($args[0] instanceof BlockObject)) {
        throw new \InvalidArgumentException("Argument must be an instance of BlockObject");
      }

      while ($this->interpreter->executeSend('value', $this, []) instanceof TrueObject) {
        // $this->interpreter->executeBlock(($args[0]->getValue()));
        $this->interpreter->executeSend('value', $args[0], []);
      }

      return $this->interpreter->variableTable->lastAssign;
    };

    $this->methods['value:'] = function (array $args) {
      $this->interpreter->executeBlock($this->value, $args);
      return $this->interpreter->variableTable->lastAssign;
    };
  }

  public function __toString() {
    return "block";
  }

  public function getValue(): DOMElement {
    return $this->value;
  }

  public function setValue(DOMElement $value): void {
    $this->value = $value;
  }
}
