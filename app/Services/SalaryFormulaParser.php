<?php

namespace App\Services;

class SalaryFormulaParser
{
    /**
     * Parse and compute a mathematical expression with +, -, *, /, and parentheses.
     * Completely safe, zero eval(), deterministic operator precedence.
     */
    public function calculate(string $expression): float
    {
        $tokens = $this->tokenize($expression);
        $postfix = $this->toPostfix($tokens);
        return $this->evaluatePostfix($postfix);
    }

    protected function tokenize(string $expr): array
    {
        $tokens = [];
        $len = strlen($expr);
        $i = 0;

        while ($i < $len) {
            $char = $expr[$i];

            if (ctype_digit($char) || $char === '.') {
                $num = '';
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                $tokens[] = (float) $num;
                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '(', ')'])) {
                $tokens[] = $char;
                $i++;
                continue;
            }

            // Ignore unexpected whitespace
            $i++;
        }

        return $tokens;
    }

    protected function toPostfix(array $tokens): array
    {
        $output = [];
        $ops = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;
            } elseif ($token === '(') {
                $ops[] = $token;
            } elseif ($token === ')') {
                while (!empty($ops) && end($ops) !== '(') {
                    $output[] = array_pop($ops);
                }
                if (!empty($ops)) {
                    array_pop($ops); // pop '('
                }
            } elseif (isset($precedence[$token])) {
                while (!empty($ops) && end($ops) !== '(' && $precedence[end($ops)] >= $precedence[$token]) {
                    $output[] = array_pop($ops);
                }
                $ops[] = $token;
            }
        }

        while (!empty($ops)) {
            $output[] = array_pop($ops);
        }

        return $output;
    }

    protected function evaluatePostfix(array $postfix): float
    {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = (float) $token;
            } else {
                if (count($stack) < 2) {
                    continue;
                }
                $b = array_pop($stack);
                $a = array_pop($stack);

                switch ($token) {
                    case '+':
                        $stack[] = $a + $b;
                        break;
                    case '-':
                        $stack[] = $a - $b;
                        break;
                    case '*':
                        $stack[] = $a * $b;
                        break;
                    case '/':
                        $stack[] = $b != 0 ? $a / $b : 0.0;
                        break;
                }
            }
        }

        return !empty($stack) ? (float) end($stack) : 0.0;
    }
}

