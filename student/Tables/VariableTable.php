<?php

namespace IPP\Student\Tables;

use IPP\Student\BuiltInClasses\LiteralObject;

class VariableTable {
  /**
   * @var array<string, object>
   */
  public array $variables = [];
  public ?LiteralObject $lastAssign = null;

  public function setVariable(string $name, LiteralObject $value): void {
    // Initialize the variable entry
    $this->variables[$name] = [
      'name' => $name,
      'value' => $value,
    ];

    $this->lastAssign = $value;
  }
}
