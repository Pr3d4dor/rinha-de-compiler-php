<?php

declare(strict_types=1);

namespace Bine\RinhaDeCompilerPhp;

class RinhaInterpreter
{
    private array $ast = [];

    private array $stack = [];

    public function __construct(?string $file = null, array $stack = [])
    {
        if (empty($file)) {
            return;
        }

        $file = fopen($file, 'r');
        if ($file === false) {
            throw new \InvalidArgumentException('Error opening AST JSON file!');
        }

        $fileContents = stream_get_contents($file);
        if ($fileContents === false) {
            throw new \InvalidArgumentException('Error reading AST JSON file contents!');
        }

        $ast = json_decode($fileContents, true);
        if ($ast === false) {
            throw new \InvalidArgumentException('Invalid AST JSON!');
        }

        $this->ast = $ast;
        $this->stack = $stack;
    }

    public function execute()
    {
        $rootNote = $this->ast['expression'];

        return $this->interpret($rootNote, $this->stack);
    }

    public function interpret(
        array $node,
        array &$stack,
        bool $inTailPosition = false
    ) {
        switch ($node['kind']) {
            case 'Str':
                return strval($node['value']);
                break;
            case 'Int':
                return intval($node['value']);
                break;
            case 'Bool':
                return boolval($node['value']);
                break;
            case 'Tuple':
                $first = $this->interpret($node['first'], $stack);
                $second = $this->interpret($node['second'], $stack);

                return [
                    'kind' => 'Tuple',
                    'first' => $first,
                    'second' => $second,
                ];
                break;
            case 'Print':
                $term = $this->interpret($node['value'], $stack);

                if (is_bool($term)) {
                    $term = $term ? "true" : "false";
                }

                if (isset($term['kind']) && $term['kind'] === 'Tuple') {
                    $term = sprintf("(%s, %s)", $term['first'], $term['second']);
                }

                if (isset($term['kind']) && $term['kind'] === 'Closure') {
                    $term = "<#closure>";
                }

                echo $term . "\n";
                break;
            case 'First':
                $result = $this->interpret($node['value'], $stack);

                if (isset($result['kind']) && $result['kind'] === 'Tuple') {
                    return $result['first'];
                } else {
                    throw new \InvalidArgumentException('Must be a tuple!');
                }

                break;
            case 'Second':
                $result = $this->interpret($node['value'], $stack);

                if (isset($result['kind']) && $result['kind'] === 'Tuple') {
                    return $result['second'];
                } else {
                    throw new \InvalidArgumentException('Must be a tuple!');
                }

                break;
            case 'If':
                $result = $this->interpret($node['condition'], $stack);
                if (! is_bool($result)) {
                    throw new \InvalidArgumentException("Invalid if!");
                }

                return $result
                    ? $this->interpret($node['then'], $stack)
                    : $this->interpret($node['otherwise'], $stack);
                break;
            case 'Binary':
                $lhs = $this->interpret($node['lhs'], $stack);
                $rhs = $this->interpret($node['rhs'], $stack);

                switch ($node['op']) {
                    // Aritméticos
                    case 'Add':
                        return is_numeric($lhs) && is_numeric($rhs)
                            ? $lhs + $rhs
                            : $lhs . $rhs;
                        break;
                    case 'Sub':
                        if (! is_numeric($lhs) || ! is_numeric($rhs)) {
                            throw new \InvalidArgumentException('Invalid operator!');
                        }

                        return $lhs - $rhs;
                        break;
                    case 'Mul':
                        if (! is_numeric($lhs) || ! is_numeric($rhs)) {
                            throw new \InvalidArgumentException('Invalid operator!');
                        }

                        return $lhs * $rhs;
                        break;
                    case 'Div':
                        if (! is_numeric($lhs) || ! is_numeric($rhs)) {
                            throw new \InvalidArgumentException('Invalid operator!');
                        }

                        return intval($lhs / $rhs);
                        break;
                    case 'Rem':
                        if (! is_numeric($lhs) || ! is_numeric($rhs)) {
                            throw new \InvalidArgumentException('Invalid operator!');
                        }

                        return intval($lhs % $rhs);
                        break;
                    // Comparação
                    case 'Eq':
                        return $lhs === $rhs;
                        break;
                    case 'Neq':
                        return $lhs !== $rhs;
                        break;
                    // Booleanos
                    case 'Lt':
                        return $lhs < $rhs;
                        break;
                    case 'Gt':
                        return $lhs > $rhs;
                        break;
                    case 'Lte':
                        return $lhs <= $rhs;
                        break;
                    case 'Gte':
                        return $lhs >= $rhs;
                        break;
                    case 'And':
                        return $lhs && $rhs;
                        break;
                    case 'Or':
                        return $lhs || $rhs;
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid operator!');
                        break;
                }
                break;
            case 'Let':
                $name = $node['name']['text'];
                $result = $this->interpret($node['value'], $stack);

                $stack[$name] = $result;

                return $this->interpret($node['next'], $stack);
                break;
            case 'Var':
                if (! isset($stack[$node['text']])) {
                    throw new \InvalidArgumentException('Invalid variable!');
                }

                return $stack[$node['text']];
                break;
            case 'Function':
                return [
                    'kind' => 'Closure',
                    'body' => $node['value'],
                    'params' => $node['parameters'],
                    'stack' => $stack,
                    'tail' => $inTailPosition,
                ];
                break;
            case 'Call':
                $result = $this->interpret($node['callee'], $stack);

                if ($result['kind'] === 'Closure') {
                    if (count($result['params']) !== count($node['arguments'])) {
                        throw new \InvalidArgumentException('Invalid number of closure params or node arguments!');
                    }

                    $newStack = $stack;
                    for ($i = 0; $i < count($result['params']); $i++) {
                        $newStack[$result['params'][$i]['text']] = $this->interpret($node['arguments'][$i], $stack);
                    }

                    if ($result['tail'] && $this->isTailPosition($result['body'])) {
                        $stack = $newStack;

                        return $this->interpret($result['body'], $stack, true);
                    } else {
                        return $this->interpret($result['body'], $newStack, $inTailPosition);
                    }
                }

                throw new \InvalidArgumentException('Must be a closure!');
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Invalid node %s!', $node['kind'])
                );
                break;
        }
    }

    private function isTailPosition(array $node)
    {
        switch ($node['kind']) {
            case 'Print':
            case 'Return':
            case 'If':
                return true;
            default:
                return false;
        }
    }
}
