<?php

/*
================================================================================

Expression - PHP Class to safely evaluate math expressions
Copyright (C) 2005 Miles Kaufmann <http://www.twmagic.com/>
Copyright (C) 2016 Jakub Jankiewicz <http://jcubic.pl/>

================================================================================

NAME
    Expression - safely evaluate math and boolean expressions

SYNOPSIS
    <?
      include('expression.php');
      $e = new Expression();
      // basic evaluation:
      $result = $e->evaluate('2+2');
      // supports: order of operation; parentheses; negation; built-in functions
      $result = $e->evaluate('-8(5/2)^2*(1-sqrt(4))-8');
      // create your own variables
      $e->evaluate('a = e^(ln(pi))');
      // or functions
      $e->evaluate('f(x,y) = x^2 + y^2 - 2x*y + 1');
      // and then use them
      $result = $e->evaluate('3*f(42,a)');
    ?>

DESCRIPTION
    Use the Expressoion class when you want to evaluate mathematical or boolean
    expressions  from untrusted sources.  You can define your own variables and
    functions, which are stored in the object.  Try it, it's fun!

    Based on http://www.phpclasses.org/browse/file/11680.html, cred to Miles Kaufmann

METHODS
    $e->evalute($expr)
        Evaluates the expression and returns the result.  If an error occurs,
        prints a warning and returns false.  If $expr is a function assignment,
        returns true on success.

    $e->e($expr)
        A synonym for $e->evaluate().

    $e->vars()
        Returns an associative array of all user-defined variables and values.

    $e->funcs()
        Returns an array of all user-defined functions.

PARAMETERS
    $e->suppress_errors
        Set to true to turn off warnings when evaluating expressions

    $e->last_error
        If the last evaluation failed, contains a string describing the error.
        (Useful when suppress_errors is on).

AUTHORS INFORMATION
    Copyright 2005, Miles Kaufmann.
    Copyright 2016, Jakub Jankiewicz

LICENSE
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are
    met:

    1   Redistributions of source code must retain the above copyright
        notice, this list of conditions and the following disclaimer.
    2.  Redistributions in binary form must reproduce the above copyright
        notice, this list of conditions and the following disclaimer in the
        documentation and/or other materials provided with the distribution.
    3.  The name of the author may not be used to endorse or promote
        products derived from this software without specific prior written
        permission.

    THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
    IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
    INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
    HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
    STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
    ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

*/

class Expression
{

    var $suppress_errors = false;
    var $last_error = null;

    var $v = array('e' => 2.71, 'pi' => 3.14); // variables (and constants)
    var $f = array(); // user-defined functions
    var $vb = array('e', 'pi'); // constants
    var $fb = array(  // built-in functions
        'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh',
        'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh',
        'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh',
        'sqrt', 'abs', 'ln', 'log');

    var $functions = array(); // function defined outside of Expression as closures

    function __construct()
    {
        // make the variables a little more accurate
        $this->v['pi'] = pi();
        $this->v['e'] = exp(1);
    }

    function e($expr)
    {
        return $this->evaluate($expr);
    }

    function evaluate($expr)
    {
        $this->last_error = null;
        $expr = trim($expr);
        if (substr($expr, -1, 1) == ';') $expr = substr($expr, 0, strlen($expr) - 1); // strip semicolons at the end
        //===============
        // is it a variable assignment?
        if (preg_match('/^\s*([a-z]\w*)\s*=(?!~|=)\s*(.+)$/', $expr, $matches)) {
            if (in_array($matches[1], $this->vb)) { // make sure we're not assigning to a constant
                return $this->trigger("cannot assign to constant '$matches[1]'");
            }
            $tmp = $this->pfx($this->nfx($matches[2]));
            $this->v[$matches[1]] = $tmp; // if so, stick it in the variable array
            return $this->v[$matches[1]]; // and return the resulting value
            //===============
            // is it a function assignment?
        } elseif (preg_match('/^\s*([a-z]\w*)\s*\((?:\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*)?\)\s*=(?!~|=)\s*(.+)$/', $expr, $matches)) {
            $fnn = $matches[1]; // get the function name
            if (in_array($matches[1], $this->fb)) { // make sure it isn't built in
                return $this->trigger("cannot redefine built-in function '$matches[1]()'");
            }

            if ($matches[2] != "") {
                $args = explode(",", preg_replace("/\s+/", "", $matches[2])); // get the arguments
            } else {
                $args = array();
            }
            if (($stack = $this->nfx($matches[3])) === false) return false; // see if it can be converted to postfix
            for ($i = 0; $i < count($stack); $i++) { // freeze the state of the non-argument variables
                $token = $stack[$i];
                if (preg_match('/^[a-z]\w*$/', $token) and !in_array($token, $args)) {
                    if (array_key_exists($token, $this->v)) {
                        $stack[$i] = $this->v[$token];
                    } else {
                        return $this->trigger("undefined variable '$token' in function definition");
                    }
                }
            }
            $this->f[$fnn] = array('args' => $args, 'func' => $stack);
            return true;
            //===============
        } else {
            return $this->pfx($this->nfx($expr)); // straight up evaluation, woo
        }
    }

    function vars()
    {
        $output = $this->v;
        unset($output['pi']);
        unset($output['e']);
        return $output;
    }

    function funcs()
    {
        $output = array();
        foreach ($this->f as $fnn => $dat)
            $output[] = $fnn . '(' . implode(',', $dat['args']) . ')';
        return $output;
    }

    //===================== HERE BE INTERNAL METHODS ====================\\

    // Convert infix to postfix notation
    function nfx($expr)
    {
        $index = 0;
        $stack = new ExpressionStack;
        $output = array(); // postfix form of expression, to be passed to pfx()
        $expr = trim(strtolower($expr));

        $ops = array('+', '-', '*', '/', '^', '_', '%', '>', '<', '>=', '<=', '==', '!=', '=~', '&&', '||', '!');
        $ops_r = array('+' => 0, '-' => 0, '*' => 0, '/' => 0, '%' => 0, '^' => 1, '>' => 0,
            '<' => 0, '>=' => 0, '<=' => 0, '==' => 0, '!=' => 0, '=~' => 0,
            '&&' => 0, '||' => 0, '!' => 0); // right-associative operator?
        $ops_p = array(
            '&&' => 1, '||' => 1,
            '>' => 2, '<' => 2, '>=' => 2, '<=' => 2, '==' => 2, '!=' => 2, '=~' => 2,
            '+' => 3, '-' => 3,
            '*' => 4, '/' => 4, '_' => 4, '%' => 4,
            '^' => 5, '!' => 5,
        ); // operator precedence

        $expecting_op = false; // we use this in syntax-checking the expression
        // and determining when a - is a negation

        /* we allow all characters because of strings
        if (preg_match("%[^\w\s+*^\/()\.,-<>=&~|!\"\\\\/]%", $expr, $matches)) { // make sure the characters are all good
            return $this->trigger("illegal character '{$matches[0]}'");
        }
        */
        $begin_argument = false;
        while (1) { // 1 Infinite Loop ;)
            $op = substr($expr, $index, 2); // get the first two characters at the current index
            if (preg_match("/^[+\-*\/^_\"<>=%(){\[!~,](?!=|~)/", $op) || preg_match("/\w/", $op)) {
                // fix $op if it should have one character
                $op = substr($expr, $index, 1);
            }
            $single_str = '(?<!\\\\)"(?:(?:(?<!\\\\)(?:\\\\{2})*\\\\)"|[^"])*(?<![^\\\\]\\\\)"';
            $double_str = "(?<!\\\\)'(?:(?:(?<!\\\\)(?:\\\\{2})*\\\\)'|[^'])*(?<![^\\\\]\\\\)'";
            $json = '[\[{](?>"(?:[^"]|\\\\")*"|[^[{\]}]|(?1))*[\]}]';
            $number = '[\d.]+e\d+|\d+(?:\.\d*)?|\.\d+';
            $name = '[a-z]\w*\(?|\\$\w+';
            $parenthesis = '\\(';
            // find out if we're currently at the beginning of a number/string/object/array/variable/function/parenthesis/operand
            $ex = preg_match("%^($single_str|$double_str|$json|$name|$number|$parenthesis)%", substr($expr, $index), $match);
            //===============
            if ($op == '[' && $expecting_op && $ex) {
                if (!preg_match("/^\[(.*)\]$/", $match[1], $matches)) {
                    return $this->trigger("invalid array access");
                }
                $stack->push('[');
                $stack->push($matches[1]);
                $index += strlen($match[1]);
                //} elseif ($op == '!' && !$expecting_op) {
                //    $stack->push('!'); // put a negation on the stack
                //    $index++;
            } elseif ($op == '-' and !$expecting_op) { // is it a negation instead of a minus?
                $stack->push('_'); // put a negation on the stack
                $index++;
            } elseif ($op == '_') { // we have to explicitly deny this, because it's legal on the stack 
                return $this->trigger("illegal character '_'"); // but not in the input expression
                //===============
            } elseif (((in_array($op, $ops) or $ex) and $expecting_op) or in_array($op, $ops) and !$expecting_op) {
                // are we putting an operator on the stack?
                if ($ex) { // are we expecting an operator but have a number/variable/function/opening parethesis?
                    $op = '*';
                    $index--; // it's an implicit multiplication
                }
                // heart of the algorithm:
                while ($stack->count > 0 and ($o2 = $stack->last()) and in_array($o2, $ops) and ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) {
                    $output[] = $stack->pop(); // pop stuff off the stack into the output

                }
                // many thanks: http://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
                $stack->push($op); // finally put OUR operator onto the stack
                $index++;
                if (strlen($op) == 2) {
                    $index++;
                }
                $expecting_op = false;
                //===============
            } elseif ($op == ')' and $expecting_op || !$ex) { // ready to close a parenthesis?
                while (($o2 = $stack->pop()) != '(') { // pop off the stack back to the last (
                    if (is_null($o2)) return $this->trigger("unexpected ')'");
                    else $output[] = $o2;
                }
                if (preg_match("/^([a-z]\w*)\($/", $stack->last(2), $matches)) { // did we just close a function?
                    $fnn = $matches[1]; // get the function name
                    $arg_count = $stack->pop(); // see how many arguments there were (cleverly stored on the stack, thank you)
                    $output[] = $stack->pop(); // pop the function and push onto the output
                    if (in_array($fnn, $this->fb)) { // check the argument count
                        if ($arg_count > 1)
                            return $this->trigger("too many arguments ($arg_count given, 1 expected)");
                    } elseif (array_key_exists($fnn, $this->f)) {
                        if ($arg_count != count($this->f[$fnn]['args']))
                            return $this->trigger("wrong number of arguments ($arg_count given, " . count($this->f[$fnn]['args']) . " expected) " . json_encode($this->f[$fnn]['args']));
                    } elseif (array_key_exists($fnn, $this->functions)) {
                        $func_reflection = new ReflectionFunction($this->functions[$fnn]);
                        $count = $func_reflection->getNumberOfParameters();
                        if ($arg_count != $count)
                            return $this->trigger("wrong number of arguments ($arg_count given, " . $count . " expected)");
                    } else { // did we somehow push a non-function on the stack? this should never happen
                        return $this->trigger("internal error");
                    }
                }
                $index++;
                //===============
            } elseif ($op == ',' and $expecting_op) { // did we just finish a function argument?

                while (($o2 = $stack->pop()) != '(') {
                    if (is_null($o2)) return $this->trigger("unexpected ','"); // oops, never had a (
                    else $output[] = $o2; // pop the argument expression stuff and push onto the output
                }
                // make sure there was a function
                if (!preg_match("/^([a-z]\w*)\($/", $stack->last(2), $matches))
                    return $this->trigger("unexpected ','");
                $stack->push('('); // put the ( back on, we'll need to pop back to it again
                $index++;
                $expecting_op = false;
                $begin_argument = true;
                //===============
            } elseif ($op == '(' and !$expecting_op) {
                if ($begin_argument) {
                    $begin_argument = false;
                    if (!$stack->incrementArgument()) {
                        $this->trigger("unexpected '('");
                    }
                }
                $stack->push('('); // that was easy
                $index++;
                //===============
            } elseif ($ex and !$expecting_op) { // do we now have a function/variable/number?
                $expecting_op = true;
                $val = $match[1];
                if ($op == '[' || $op == "{" || preg_match("/null|true|false/", $match[1])) {
                    $output[] = $val;
                } elseif (preg_match("/^([a-z]\w*)\($/", $val, $matches)) { // may be func, or variable w/ implicit multiplication against parentheses...
                    if (in_array($matches[1], $this->fb) or
                        array_key_exists($matches[1], $this->f) or
                        array_key_exists($matches[1], $this->functions)
                    ) { // it's a func
                        if ($begin_argument) {
                            if (!$stack->incrementArgument()) {
                                $this->trigger("unexpected '('");
                            }
                        }
                        $stack->push($val);
                        $stack->push(0);
                        $stack->push('(');
                        $begin_argument = true;
                        $expecting_op = false;
                    } else { // it's a var w/ implicit multiplication
                        $val = $matches[1];
                        $output[] = $val;
                    }
                } else { // it's a plain old var or num
                    $output[] = $val;
                    if (preg_match("/^([a-z]\w*)\($/", $stack->last(3))) {
                        if ($begin_argument) {
                            $begin_argument = false;

                            if (!$stack->incrementArgument()) {
                                $this->trigger("unexpected error");
                            }
                        }
                    }
                }
                $index += strlen($val);
                //===============
            } elseif ($op == ')') { // miscellaneous error checking
                return $this->trigger("unexpected ')'");
            } elseif (in_array($op, $ops) and !$expecting_op) {
                return $this->trigger("unexpected operator '$op'");
            } else { // I don't even want to know what you did to get here
                return $this->trigger("an unexpected error occured " . json_encode($op) . " " . json_encode($match) . " " . ($ex ? 'true' : 'false') . " " . $expr);
            }
            if ($index == strlen($expr)) {
                if (in_array($op, $ops)) { // did we end with an operator? bad.
                    return $this->trigger("operator '$op' lacks operand");
                } else {
                    break;
                }
            }
            while (substr($expr, $index, 1) == ' ') { // step the index past whitespace (pretty much turns whitespace 
                $index++;                             // into implicit multiplication if no operator is there)
            }

        }
        while (!is_null($op = $stack->pop())) { // pop everything off the stack and push onto output
            if ($op == '(') return $this->trigger("expecting ')'"); // if there are (s on the stack, ()s were unbalanced
            $output[] = $op;
        }
        return $output;
    }

    // evaluate postfix notation
    function pfx($tokens, $vars = array())
    {

        if ($tokens == false) return false;
        $stack = new ExpressionStack();
        foreach ($tokens as $token) { // nice and easy
            // if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
            if (in_array($token, array('+', '-', '*', '/', '^', '<', '>', '<=', '>=', '==', '&&', '||', '!=', '=~', '%'))) {
                $op2 = $stack->pop();
                $op1 = $stack->pop();
                switch ($token) {
                    case '+':
                        if (is_string($op1) || is_string($op2)) {
                            $stack->push((string)$op1 . (string)$op2);
                        } else {
                            $stack->push($op1 + $op2);
                        }
                        break;
                    case '-':
                        $stack->push($op1 - $op2);
                        break;
                    case '*':
                        $stack->push($op1 * $op2);
                        break;
                    case '/':
                        if ($op2 == 0) return $this->trigger("division by zero");
                        $stack->push($op1 / $op2);
                        break;
                    case '%':
                        $stack->push($op1 % $op2);
                        break;
                    case '^':
                        $stack->push(pow($op1, $op2));
                        break;
                    case '>':
                        $stack->push($op1 > $op2);
                        break;
                    case '<':
                        $stack->push($op1 < $op2);
                        break;
                    case '>=':
                        $stack->push($op1 >= $op2);
                        break;
                    case '<=':
                        $stack->push($op1 <= $op2);
                        break;
                    case '==':
                        if (is_array($op1) && is_array($op2)) {
                            $stack->push(json_encode($op1) == json_encode($op2));
                        } else {
                            $stack->push($op1 == $op2);
                        }
                        break;
                    case '!=':
                        if (is_array($op1) && is_array($op2)) {
                            $stack->push(json_encode($op1) != json_encode($op2));
                        } else {
                            $stack->push($op1 != $op2);
                        }
                        break;
                    case '=~':
                        $value = @preg_match($op2, $op1, $match);

                        if (!is_int($value)) {
                            return $this->trigger("Invalid regex " . json_encode($op2));
                        }
                        $stack->push($value);
                        for ($i = 0; $i < count($match); $i++) {
                            $this->v['$' . $i] = $match[$i];
                        }
                        break;
                    case '&&':
                        $stack->push($op1 ? $op2 : $op1);
                        break;
                    case '||':
                        $stack->push($op1 ? $op1 : $op2);
                        break;
                }
                // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } elseif ($token == '!') {
                $stack->push(!$stack->pop());
            } elseif ($token == '[') {
                $selector = $stack->pop();
                $object = $stack->pop();
                if (is_object($object)) {
                    $stack->push($object->$selector);
                } elseif (is_array($object)) {
                    $stack->push($object[$selector]);
                } else {
                    return $this->trigger("invalid object for selector");
                }
            } elseif ($token == "_") {
                $stack->push(-1 * $stack->pop());
                // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            } elseif (preg_match("/^([a-z]\w*)\($/", $token, $matches)) { // it's a function!
                $fnn = $matches[1];
                if (in_array($fnn, $this->fb)) { // built-in function:
                    if (is_null($op1 = $stack->pop())) return $this->trigger("internal error");
                    $fnn = preg_replace("/^arc/", "a", $fnn); // for the 'arc' trig synonyms
                    if ($fnn == 'ln') $fnn = 'log';
                    $stack->push($fnn($op1)); // perfectly safe variable function call
                } elseif (array_key_exists($fnn, $this->f)) { // user function
                    // get args
                    $args = [];
                    for ($i = count($this->f[$fnn]['args']) - 1; $i >= 0; $i--) {
                        if ($stack->isEmpty()) {
                            return $this->trigger("internal error " . $fnn . " " . json_encode($this->f[$fnn]['args']));
                        }
                        $args[$this->f[$fnn]['args'][$i]] = $stack->pop();
                    }
                    $stack->push($this->pfx($this->f[$fnn]['func'], $args)); // yay... recursion!!!!
                } else if (array_key_exists($fnn, $this->functions)) {
                    $reflection = new ReflectionFunction($this->functions[$fnn]);
                    $count = $reflection->getNumberOfParameters();
                    $args = [];
                    for ($i = $count - 1; $i >= 0; $i--) {
                        if ($stack->isEmpty()) {
                            return $this->trigger("internal error");
                        }
                        $args[] = $stack->pop();
                    }
                    $stack->push($reflection->invokeArgs(array_reverse($args)));
                }
                // if the token is a number or variable, push it on the stack
            } else {
                if (preg_match('/^([\[{](?>"(?:[^"]|\\")*"|[^[{\]}]|(?1))*[\]}])$/', $token) ||
                    preg_match("/^(null|true|false)$/", $token)
                ) { // json
                    //return $this->trigger("invalid json " . $token);
                    if ($token == 'null') {
                        $value = null;
                    } elseif ($token == 'true') {
                        $value = true;
                    } elseif ($token == 'false') {
                        $value = false;
                    } else {
                        $value = json_decode($token);
                        if ($value == null) {
                            return $this->trigger("invalid json " . $token);
                        }
                    }
                    $stack->push($value);
                } elseif (is_numeric($token)) {
                    $stack->push(0 + $token);
                } else if (preg_match("/^['\\\"](.*)['\\\"]$/", $token)) {
                    $stack->push(json_decode(preg_replace_callback("/^['\\\"](.*)['\\\"]$/", function ($matches) {
                        $m = array("/\\\\'/", '/(?<!\\\\)"/');
                        $r = array("'", '\\"');
                        return '"' . preg_replace($m, $r, $matches[1]) . '"';
                    }, $token)));
                } elseif (array_key_exists($token, $this->v)) {
                    $stack->push($this->v[$token]);
                } elseif (array_key_exists($token, $vars)) {
                    $stack->push($vars[$token]);
                } else {
                    return $this->trigger("undefined variable '$token'");
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count != 1) return $this->trigger("internal error");
        return $stack->pop();
    }

    /**
     * trigger an error, but nicely, if need be
     * @param string $msg
     * @return bool
     */
    function trigger($msg)
    {
        $this->last_error = $msg;
        if (!$this->suppress_errors) trigger_error($msg, E_USER_WARNING);
        return false;
    }
}

/**
 * for internal use
 */
class ExpressionStack
{
    var $stack = array();
    var $count = 0;

    function push($val)
    {
        $this->stack[$this->count] = $val;
        $this->count++;
    }

    function pop()
    {
        if ($this->count > 0) {
            $this->count--;
            return $this->stack[$this->count];
        }
        return null;
    }

    function incrementArgument()
    {
        while (($o2 = $this->pop()) != '(') {
            if (is_null($o2)) {
                return false; // oops, never had a (
            } else {
                $output[] = $o2; // pop the argument expression stuff and push onto the output
            }
        }
        // make sure there was a function
        if (!preg_match("/^([a-z]\w*)\($/", $this->last(2), $matches)) {
            return false;
        }

        $this->push($this->pop() + 1); // increment the argument count
        $this->push('('); // put the ( back on, we'll need to pop back to it again

        return true;
    }

    function isEmpty()
    {
        return empty($this->stack);
    }

    function last($n = 1)
    {
        if (isset($this->stack[$this->count - $n])) {
            return $this->stack[$this->count - $n];
        }
        return null;
    }
}