<?php

namespace IPP\Student;

use DOMElement;
use IPP\Core\AbstractInterpreter;
use IPP\Student\Tables\ClassTable;
use IPP\Student\Tables\VariableTable;
use IPP\Core\Exception\InputFileException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Core\Exception\NotImplementedException;

class Interpreter extends AbstractInterpreter {

    private ClassTable $classTable;
    private VariableTable $variableTable;

    private string $currentClass = '';
    private string $currentMethod = '';

    public function execute(): int {
        $this->classTable = new ClassTable();
        $this->variableTable = new VariableTable();

        $dom = $this->source->getDOMDocument();

        $program = $dom->documentElement;
        if (!$program || $program->tagName !== 'program') {
            throw new InputFileException("Invalid XML structure: missing <program> element");
        }
        echo "Program: " . $program->tagName . "\n";

        foreach ($program->getElementsByTagName('class') as $classElement) {
            $this->classTable->addClass($classElement);
        }

        // print_r($this->classTable->classes);

        // Check if the 'Main' class exists with method 'run
        if (!isset($this->classTable->classes['Main']['methods']['run'])) {
            throw new InputFileException("Main class does not have a 'run' method");
        }

        // Execute the 'run' method of the 'Main' class
        $runBlock = $this->classTable->getBlock('Main', 'run');
        $this->currentClass = 'Main';
        $this->currentMethod = 'run';

        $this->executeBlock($runBlock);

        throw new NotImplementedException();
    }

    private function executeMethod(string $className, string $methodName) {
        $block = $this->classTable->getBlock($className, $methodName);

        $this->variableTable->addVariable;
    }

    private function executeBlock(DOMElement $block) {
        // 1. Najdi všechny <assign> podle pořadí
        $assigns = [];

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

        return $this->variableTable->lastAssing;
    }

    private function executeAssign(DOMElement $assign): void {
        $varName = $assign->getElementsByTagName('var')->item(0)->getAttribute('name');
        $expr = $this->getFirstExpr($assign);
        $value = $this->evaluateExpr($expr);


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

                    print_r("\nVariable: " . $varName . "\n");

                    if (isset($this->variableTable->variables[$varName])) {
                        return $this->variableTable->variables[$varName];
                    } else if ($varName == 'self') {
                        return 'self';
                    }

                    throw new \Exception("Undefined variable {$varName}");
                case 'send':
                    return $this->evaluateSend($child);
            }
        }

        throw new \Exception("Unknown expression type");
    }

    private function evaluateLiteral(DOMElement $literal) {
        $class = $literal->getAttribute('class');
        $value = $literal->getAttribute('value');

        return match ($class) {
            'String' => $value,
            'Integer' => (int)$value,
            default => throw new \Exception("Literal of class $class not implemented"),
        };
    }


    private function evaluateSend(DOMElement $send) {
        $selector = $send->getAttribute('selector');
        $caller = $this->evaluateExpr($this->getFirstExpr($send));

        $targetExpr = $send->getElementsByTagName('expr')->item(0);
        $target = $this->evaluateExpr($targetExpr);

        if ($selector === 'print') {
            $this->stdout->writeString($target);
            return $target;
        }

        if ($caller === 'self') {
            $this->currentMethod = $selector;
            return $this->executeBlock($this->classTable->getBlock($this->currentClass, $selector));
        }



        throw new \Exception("Send message $selector not implemented");
    }

    private function getFirstExpr(DOMElement $element): ?DOMElement {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'expr') {
                return $child;
            }
        }
        return null;
    }
}
