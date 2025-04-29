<?php

namespace IPP\Student;

use DOMElement;
use IPP\Student\ExceptionRunner;
use IPP\Core\AbstractInterpreter;
use IPP\Student\Tables\ClassTable;
use IPP\Student\Tables\VariableTable;
use IPP\Core\Exception\InputFileException;
use IPP\Core\ReturnCode;
use IPP\Student\BuiltInClasses\BlockObject;
use IPP\Student\BuiltInClasses\IntegerObject;
use IPP\Student\BuiltInClasses\LiteralObject;
use IPP\Student\BuiltInClasses\StringObject;
use IPP\Student\BuiltInClasses\CustomObject;
use IPP\Student\BuiltInClasses\FalseObject;
use IPP\Student\BuiltInClasses\NilObject;
use IPP\Student\BuiltInClasses\TrueObject;

class Interpreter extends AbstractInterpreter {

    private ClassTable $classTable;
    public VariableTable $variableTable;

    private CustomObject $currentClass;

    public function readInput(): ?string {
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
            new ExceptionRunner("Missing class 'Main' or method 'run'", ReturnCode::PARSE_MAIN_ERROR);
        }

        $this->executeMethod('Main', 'run', [], '');

        return 0;
    }

    /** 
     * @param array<mixed> $args 
     */
    private function executeMethod(string $className, string $methodName, array $args, mixed $value): LiteralObject {

        $block = $this->classTable->getBlock($className, $methodName);

        $this->currentClass = new CustomObject($value, $this, $className, $this->classTable->classes[$className]['parent']);

        // fwrite(STDERR, "\nExecuting method: $className::$methodName\n");

        return $this->executeBlock($block, $args);
    }


    /** 
     * @param array<mixed> $args 
     */
    public function executeBlock(DOMElement $block, array $args = []): LiteralObject {
        $assigns = [];

        $parameters = $this->getChildElementsByTagName($block, 'parameter');

        $params = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getAttribute('name');
            $paramOrder = $parameter->getAttribute('order');
            if (empty($paramName)) {
                throw new \Exception("Parameter name cannot be empty");
            }
            $params[(int)$paramOrder - 1] = $paramName;
        }

        // fwrite(STDERR, "Method args: " . implode(", ", $params) . "\n");

        foreach ($args as $argIndex => $argValue) {
            if (isset($params[$argIndex]))
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

        // $valueClass = get_class($value);
        // fwrite(STDERR, "\nAssigned variable: $varName = ($valueClass) $value\n");

        $this->variableTable->setVariable($varName, $value);
    }

    private function evaluateExpr(DOMElement $expr): LiteralObject {
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
                        return $this->currentClass;
                    } else if ($varName == 'super') {
                        $classValue = $this->currentClass->getValue();
                        if ($classValue instanceof LiteralObject) $classValue = $classValue->getValue();

                        if (isset($this->classTable->classes[$this->currentClass->parentClassName]))
                            return new CustomObject($classValue, $this, $this->currentClass->parentClassName, $this->classTable->classes[$this->currentClass->parentClassName]['parent']);
                        else
                            return new ("IPP\Student\BuiltInClasses\\" . $this->currentClass->parentClassName . "Object")($classValue, $this);
                    }

                    throw new ExceptionRunner("Undefined variable {$varName}", ReturnCode::INTERPRET_TYPE_ERROR);
                case 'send':
                    return $this->evaluateSend($child);

                case 'expr':
                    return $this->evaluateExpr($child);

                case 'block':
                    return new BlockObject($child, $this);

                default:
                    throw new ExceptionRunner("Undefined type", ReturnCode::INVALID_XML_ERROR);
            }
        }

        // Add a fallback return statement to ensure all paths return a value
        throw new \Exception("Expression could not be evaluated");
    }

    private function evaluateLiteral(DOMElement $literal): LiteralObject {
        $class = $literal->getAttribute('class');
        $value = $literal->getAttribute('value');

        if ($class == "class" && $value == "Object") $value = "Literal";

        return match ($class) {
            'String' => new StringObject($value, $this),
            'Integer' => new IntegerObject((int)$value, $this),
            'True' => new TrueObject(true, $this),
            'False' => new FalseObject(false, $this),
            'Nil' => new NilObject(0, $this),
            'class' => isset($this->classTable->classes[$value]['name']) ?
                new CustomObject('', $this, $this->classTable->classes[$value]['name'], $this->classTable->classes[$value]['parent'])
                : new ("IPP\Student\BuiltInClasses\\" . $value . "Object")(0, $this),
            default => throw new \Exception("Literal of class $class not implemented"),
        };
    }

    public function executeSend(string $selector, LiteralObject $caller, mixed $argValues): LiteralObject {
        if ($caller instanceof BlockObject) {
            if (str_contains($selector, "value"))
                return $caller->methods['value:']($argValues);
            else
                return $caller->methods[$selector]($argValues);
        } else {
            // $className = $caller->className;
            $callerValue = $caller->getValue();
            // print_r("\nCall LiteralObject $caller->className ($caller)($callerValue)::$selector (" . implode(", ", $argValues) . ")\n\n");

            $parentClassNamePath = "IPP\\Student\\BuiltInClasses\\" . $caller->parentClassName . "Object";

            if (isset($this->classTable->classes[$caller->className]['methods'][$selector]) && !isset($this->classTable->classes[$caller->className]['methods'][$selector]['value'])) {
                return $this->executeMethod($caller->className, $selector, $argValues, $callerValue);
            } else if (isset($caller->methods[$selector])) {
                return $caller->methods[$selector]($argValues);
            } else if (isset($this->classTable->classes[$caller->className]['parent']) && isset($this->classTable->classes[$this->classTable->classes[$caller->className]['parent']][$selector])) {
                return $this->executeMethod($this->classTable->classes[$caller->className]['parent'], $selector, $argValues, $callerValue);
            } else if (class_exists($parentClassNamePath)) {
                // print_r("\nCreate new sub class $caller->parentClassName = " . (int)$caller->getValue() . " from $caller->className type " . get_class($caller->getValue()) . " \n");
                $parentClass = new $parentClassNamePath($caller->getValue(), $this);
                if (isset($parentClass->methods[$selector]))
                    return $parentClass->methods[$selector]($argValues);
            }

            if ((!isset($this->classTable->classes[$caller->className]['methods'][$selector]) || isset($this->classTable->classes[$caller->className]['methods'][$selector]['value']))) {
                // print_r("\nCALL ($caller)::$selector \n");
                // print_r("\nSet class var $caller->className::$selector =  --- " . isset($this->classTable->classes[$caller->className]['methods'][$selector]) ? "true" : "false" . "\n\n");

                if (isset($argValues[0])) {
                    // print_r("\n Set attr $caller->className::$selector\n");
                    $this->classTable->classes[$caller->className]['methods'][$selector]['value'] = $argValues[0];
                    return $argValues[0];
                } else if (isset($this->classTable->classes[$caller->className]['methods'][$selector . ":"])) {
                    // print_r("\n Get attr $caller->className::$selector\n");
                    return $this->classTable->classes[$caller->className]['methods'][$selector . ":"]['value'];
                }
            }
        }

        throw new ExceptionRunner("Send message $caller($caller->className)::$selector() not implemented", ReturnCode::INTERPRET_DNU_ERROR);
    }


    private function evaluateSend(DOMElement $send): LiteralObject {

        $selector = $send->getAttribute('selector');
        $caller = $this->evaluateExpr($this->getChildElementByTagName($send, 'expr'));


        $args = $this->getChildElementsByTagName($send, 'arg');
        $argValues = [];

        foreach ($args as $arg) {
            $argValue = $this->evaluateExpr($arg);
            $argValues[((int)$arg->getAttribute('order')) - 1] = $argValue;
            // fwrite(STDERR, "\nEvaluate arg: " . $argValue . "\n");
        }
        // fwrite(STDERR, "\nEvaluate send: " . $caller . "::" . $selector . "(" . implode(", ", $argValues) . ")\n");

        return $this->executeSend($selector, $caller, $argValues);
    }

    private function getChildElementByTagName(DOMElement $element, string $tagName): ?DOMElement {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                return $child;
            }
        }
        return null;
    }

    /**
     * @return DOMElement[] 
     */
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
