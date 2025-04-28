<?php

namespace IPP\Student\Tables;

class VariableTable {
  public array $variables = [];
  public $lastAssign = null;

  public function setVariable($name, $value): void {
    // Initialize the variable entry
    $this->variables[$name] = [
      'name' => $name,
      'value' => $value,
    ];

    $this->lastAssign = $value;
  }
}
