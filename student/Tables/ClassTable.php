<?php

namespace IPP\Student\Tables;

use DOMElement;

class ClassTable {
  public array $classes = [];

  public function addClass(DOMElement $classElement): void {
    $name = $classElement->getAttribute('name');

    if (isset($this->classes[$name])) {
      throw new \Exception("Class $name already exists");
    }

    // Initialize the class entry
    $this->classes[$name] = [
      // 'element' => $classElement,
      'name' => $name,
      'parent' => $classElement->getAttribute('parent'),
      'methods' => [],
    ];



    // Insert methods into the class
    $methods = $classElement->getElementsByTagName('method');

    foreach ($methods as $method) {
      $methodName = $method->getAttribute('selector');

      $parameters = $method->getElementsByTagName('parameter');

      $params = [];
      foreach ($parameters as $parameter) {
        $paramName = $parameter->getAttribute('name');
        if (empty($paramName)) {
          throw new \Exception("Parameter name cannot be empty");
        }
        $params[] = $paramName;
      }

      $this->classes[$name]['methods'][$methodName] = [
        'element' => $method,
        'name' => $methodName,
        'parameters' => $params,
      ];
    }
  }

  public function getBlock(string $className, string $methodName): ?DOMElement {
    if (!isset($this->classes[$className])) {
      throw new \Exception("Class $className does not exist");
    }

    if (!isset($this->classes[$className]['methods'][$methodName])) {
      throw new \Exception("Method $methodName does not exist in class $className");
    }

    return $this->classes[$className]['methods'][$methodName]['element']->getElementsByTagName('block')->item(0);
  }
}
