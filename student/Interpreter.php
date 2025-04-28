<?php

namespace IPP\Student;

use DOMElement;
use IPP\Core\AbstractInterpreter;
use IPP\Student\Tables\ClassTable;
use IPP\Student\Tables\VariableTable;
use IPP\Core\Exception\InputFileException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Core\Exception\NotImplementedException;
use IPP\Student\BuiltInClasses\BlockObject;
use IPP\Student\BuiltInClasses\IntegerObject;
use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\BuiltInClasses\StringObject;
use IPP\Student\BuiltInClasses\CustomObject;

class Interpreter extends AbstractInterpreter {

    private ClassTable $classTable;
    public VariableTable $variableTable;

    private string $currentClass = '';
    private string $currentMethod = '';

    public function readInput() {
        return $this->input->readString();
    }

    public function execute(): int {
        $this->classTable = new ClassTable();
        $this->variableTable = new VariableTable();

        $dom = $this->source->getDOMDocument();

        $program = $dom->documentElement;
        if (!$program || $program->tagName !== 'program') {
            throw new InputFileException("Invalid XML structure: missing <program> element");
        }

        // fwrite(STDERR, "Program: " . $program->tagName . "\n");

        foreach ($program->getElementsByTagName('class') as $classElement) {
            $this->classTable->addClass($classElement);
        }


        // Check if the 'Main' class exists with method 'run
        if (!isset($this->classTable->classes['Main']['methods']['run'])) {
            throw new InputFileException("Main class does not have a 'run' method");
        }

        $this->executeMethod('Main', 'run', []);

        return 0;
    }

    private function executeMethod(string $className, string $methodName, array $args) {

        $block = $this->classTable->getBlock($className, $methodName);

        if ($block instanceof LiteralObject) return $block;

        $this->currentClass = $className;
        $this->currentMethod = $methodName;

        // fwrite(STDERR, "\nExecuting method: $className::$methodName\n");

        return $this->executeBlock($block, $args);
    }

    public function executeBlock(DOMElement $block, array $args = []) {
        $assigns = [];

        $parameters = $this->getChildElementsByTagName($block, 'parameter');

        $params = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getAttribute('name');
            $paramOrder = $parameter->getAttribute('order');
            if (empty($paramName)) {
                throw new \Exception("Parameter name cannot be empty");
            }
            $params[$paramOrder - 1] = $paramName;
        }

        // fwrite(STDERR, "Method args: " . implode(", ", $params) . "\n");

        foreach ($args as $argIndex => $argValue) {
            $this->variableTable->setVariable($params[$argIndex], $argValue);
        }

        foreach ($block->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'assign') {
                $order = (int)$child->getAttribute('order');
                $assigns[$order] = $child;
            }
        }

        ksort($assigns);

        foreach ($assigns as $assign) {
            $this->executeAssign($assign);
        }

        return $this->variableTable->lastAssign;
    }

    private function executeAssign(DOMElement $assign): void {
        $varName = $assign->getElementsByTagName('var')->item(0)->getAttribute('name');
        // fwrite(STDERR, "\nAssigning value to variable: $varName\n");
        $expr = $this->getChildElementByTagName($assign, 'expr');
        $value = $this->evaluateExpr($expr);
        // fwrite(STDERR, "\nAssigned variable: $varName = $value\n");

        $this->variableTable->setVariable($varName, $value);
    }

    private function evaluateExpr(DOMElement $expr) {
        foreach ($expr->childNodes as $child) {
            if (!$child instanceof DOMElement) continue;

            switch ($child->tagName) {
                case 'literal':
                    return $this->evaluateLiteral($child);
                case 'var':
                    $varName = $child->getAttribute('name');

                    if (isset($this->variableTable->variables[$varName])) {
                        return $this->variableTable->variables[$varName]['value'];
                    } else if ($varName == 'self') {
                        return new CustomObject(0, $this, $this->currentClass);
                    }

                    throw new \Exception("Undefined variable {$varName}");
                case 'send':
                    return $this->evaluateSend($child);

                case 'expr':
                    return $this->evaluateExpr($child);

                case 'block':
                    return new BlockObject($child, $this);

                default:
                    throw new \Exception("Unknown expression type: " . $child->tagName);
            }
        }
    }

    private function evaluateLiteral(DOMElement $literal) {
        $class = $literal->getAttribute('class');
        $value = $literal->getAttribute('value');

        return match ($class) {
            'String' => new StringObject($value, $this),
            'Integer' => new IntegerObject($value, $this),
            'class' => isset($this->classTable->classes[$value]['name']) ?
                new CustomObject(0, $this, $this->classTable->classes[$value]['name'])
                : new ("IPP\Student\BuiltInClasses\\" . $value . "Object")('', $this),
            default => throw new \Exception("Literal of class $class not implemented"),
        };
    }


    private function evaluateSend(DOMElement $send) {

        $selector = $send->getAttribute('selector');
        $caller = $this->evaluateExpr($this->getChildElementByTagName($send, 'expr'));


        $args = $this->getChildElementsByTagName($send, 'arg');
        $argValues = [];

        foreach ($args as $arg) {
            $argValue = $this->evaluateExpr($arg);
            $argValues[] = $argValue;
            // fwrite(STDERR, "\nEvaluate arg: " . $argValue . "\n");
        }
        // fwrite(STDERR, "\nEvaluate send: " . $caller . "::" . $selector . "(" . implode(", ", $argValues) . ")\n");

        print_r("\n Send class type -- " . get_class($caller) . "\n");

        if ($caller instanceof CustomObject) {
            $className = $caller->getClassName();
            $this->currentMethod = $selector;

            print_r("\nSend method -> " . $className . "::" . $selector . " -- class type " . get_class($caller) . "\n");

            if (!isset($this->classTable->classes[$className]['methods'][$selector])) {
                if (isset($argValues[0])) {
                    $this->classTable->classes[$className]['methods'][$selector]['value'] = $argValues[0];
                    return $argValues[0];
                } else {
                    return $this->classTable->classes[$className]['methods'][$selector . ":"]['value'];
                }
            }

            if ($selector == 'from:') {
                return new CustomObject($argValues, $this, $className);
            }

            if (isset($this->classTable->classes[$this->currentClass]['methods'][$selector]))
                return $this->executeMethod($this->currentClass, $selector, $argValues);
            else
                return $this->executeMethod($this->classTable->classes[$this->currentClass]['parent'], $selector, $argValues);
        } else if ($caller instanceof BlockObject) {
            return $caller->methods['value:']($argValues);
        } else if ($caller instanceof LiteralObject) {
            // print_r($this->classTable->classes);
            return $caller->methods[$selector]($argValues);
        } else {
        }

        throw new \Exception("Send message $caller::$selector() not implemented");
    }

    private function getChildElementByTagName(DOMElement $element, string $tagName): ?DOMElement {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                return $child;
            }
        }
        return null;
    }

    private function getChildElementsByTagName(DOMElement $element, string $tagName): array {
        $elements = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                $elements[] = $child;
            }
        }
        return $elements;
    }
}
