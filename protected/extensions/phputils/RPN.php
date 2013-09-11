<?php

Class RPN
{
    public static $operators = array(
        '+','-','*','/',
    	'<','>','=','<=','=<','>=','=>','!=','<>',
        'LT', 'LE', 'GT', 'GE', 'EQ', 'NE',
    	'LOOKUP', 'IF', 'SYS_DATE',
    );

    public static $functions = array(
        'LOOKUP', 'IF', 'SYS_DATE',
    );

    private static $operands_by_operator = array(
    	'+' => 2,
    	'-' => 2,
    	'*' => 2,
    	'/' => 2,
        '<' => 2,
        '>' => 2,
        '=' => 2,
    	'<=' => 2,
        '=<' => 2,
    	'>=' => 2,
        '=>' => 2,
    	'!=' => 2,
    	'<>' => 2,
        'LT' => 2,
        'LE' => 2,
        'GT' => 2,
        'GE' => 2,
        'EQ' => 2,
        'NE' => 2,
    	'LOOKUP' => 2,
    	'IF' => 3,
    	'SYS_DATE' => 1,
    );

    private static $preferred_operators = array(
        'LT' => '<',
        'LE' => '<=',
        'GT' => '>',
        'GE' => '>=',
        'EQ' => '=',
        'NE' => '!=',
        '<>' => '!=',
    );

    private static $text_operators = array(
        '<' => 'LT',
        '>' => 'GT',
        '=' => 'EQ',
        '<=' => 'LE',
        '=<' => 'LE',
        '>=' => 'GE',
        '=>' => 'GE',
        '<>' => 'NE',
        '!=' => 'NE'
    );

    public static $commutative_operators = array(
        '+','*',
        '=','!=','<>',
        'EQ', 'NE',
    );

    public static $precedence = array(
        '+' => 2,
        '-' => 2,
        '*' => 3,
        '/' => 3,
        '<' => 1,
        '>' => 1,
        '=' => 1,
        '<=' => 1,
        '>=' => 1,
        '!=' => 1,
        '<>' => 1,
        'LT' => 1,
        'LE' => 1,
        'GT' => 1,
        'GE' => 1,
        'EQ' => 1,
        'NE' => 1,
        'LOOKUP' => 4,
        'IF' => 4,
        'SYS_DATE' => 4,
    );

    public static function getSystemConstants() {
        return array(
        	"aggr_time"        =>array("display_name"=>"SYS_A_T", "description"=>Yii::t('msg',"aggregáció hossza másodpercben")),
        	"aggr_multiplier"  =>array("display_name"=>"SYS_A_M", "description"=>Yii::t('msg',"fogyasztás órára vetítésének szorzószáma")),
        	"aggr_time_n"      =>array("display_name"=>"SYS_A_n", "description"=>Yii::t('msg',"aktuális integrálási időbe hányszor fér bele a legkisebb időegység"))
        );
    }

    /**
     * Evaluate RPN expression with boolean operators and IF statement
     * Examples:
     * 	1 2 -         => 1-2+@konst1@ = -1
     *  6 3 /         => 6/3 = 2
     *  1 2 < 3 4 IF  => if (1<2) 3 else 4  = 3
     *  1 2 > 3 NULL IF => if (1>2) 3 else NULL = NULL
     *
     * @param string $rpn_expr space separated evaluable RPN expression (=no variables, only values)
     */
    public static function evalRPN($rpn_expr, $time, $system_second)
    {
        $time = (!is_int($time))?intval($time):$time;
		$rpn_arr = explode(' ', $rpn_expr);

		$stack = array();
		foreach($rpn_arr as $operand) {
			if (in_array($operand,self::$operators)) {
				switch ($operand) {
				case '+':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a + $b);
					break;
				case '-':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $b - $a);
					break;
				case '*':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a * $b);
					break;
				case '/':
					$a = array_pop($stack);
					$b = array_pop($stack);
					if (is_null($a) || is_null($b)) {
					    array_push($stack, null);
					} else {
    					$division = null;
    					if ($a!=0) $division = $b / $a;
    				    array_push($stack, $division);
					}
					break;
				case '<':
				case 'LT':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a > $b);
					break;
				case '>':
				case 'GT':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a < $b);
					break;
				case '=':
				case 'EQ':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a == $b);
					break;
				case '<=':
				case 'LE':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a >= $b);
					break;
				case '>=':
				case 'GE':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a <= $b);
					break;
				case '!=':
				case '<>':
				case 'NE':
				    $a = array_pop($stack);
				    $b = array_pop($stack);
				    if (is_null($a) || is_null($b)) array_push($stack, null);
				                               else array_push($stack, $a != $b);
					break;
				case 'IF':
					$else = array_pop($stack);
					$then = array_pop($stack);
					$expr = array_pop($stack);
					if (is_null($expr)) {
					    array_push($stack, null);
					} else {
                        array_push($stack, $expr ? $then : $else);
					}
					break;
				case 'LOOKUP':
				    $function_name = array_pop($stack);
					$x = array_pop($stack);

					if (is_null($function_name)) {
					    throw new EException('Function name not set! RPN Expression: '.$rpn_expr);
					} elseif (is_null($x)) {
					    array_push($stack, null);
					} else {
					    array_push($stack, Lookup::model()->interpolate($function_name, $x));
					}
					break;
				case 'SYS_DATE':
				    $a = array_pop($stack);
				    array_push($stack, date($a, $time));
					break;
				}

			} else {
			    $first_char = substr($operand, 0, 1);
			    $last_char = substr($operand, -1);

			    if ($first_char=='@' && $last_char == '@') {
			        $constant_name = substr($operand, 1, strlen($operand)-2);

			        $system_constants = self::getSystemConstants();

			        switch ($constant_name) {
			            case $system_constants["aggr_time"]["display_name"]:
			                $operand = $system_second;
			                break;
			            case $system_constants["aggr_multiplier"]["display_name"]:
			                $operand = 3600 / $system_second;
			                break;
			            case $system_constants["aggr_time_n"]["display_name"]:
			                $operand = $system_second / 30;
			                break;
			            default:
			                $operand = Constant::model()->getValueAt($constant_name, $time);
			        }
			    }

			    if ($operand == 'null') $operand = null;

			    array_push($stack, $operand);
			}
		}

		return array_pop($stack);
    }

    /**
     * Validates a formal RPN expression, if it leaves the stack with (more than == not strict) 1 value(s)
     *
     * @param string $rpn_expr formal RPN expression, it can contains variables, constants, operators, etc.
     * @param boolean $strict if the validation strict, the stack must contain only 1 value at the end of the evaluation, else it could be more than 1
     */
    public static function validateRPN($rpn_expr, $strict = true)
    {
        //vizsgáljuk, hogy egyáltalán vannak-e az adatbázisban a képletbe megadott mérők
        if (!Meter::model()->exists_all(self::extractMetersFromRPN($rpn_expr))) return false;
        // felesleges dupla szóközök eltüntetése
        $rpn_expr = trim(preg_replace('/\s+/', ' ', $rpn_expr));

		$rpn_arr = explode(' ', $rpn_expr);
		$stack_num = 0;
		foreach($rpn_arr as $operand) {
			if (in_array($operand,self::$operators)) {
			    $stack_num += 1 - self::$operands_by_operator[$operand];
			} else {
			    $stack_num++;
			}
		}

		return $strict ? $stack_num==1 : $stack_num>=1;
    }

    public static function extractMetersFromRPN($rpn_expr)
    {
        $rpn_arr = explode(' ', $rpn_expr);

        $variables = array();
        foreach ($rpn_arr as $key => $operand) {
            $first_word = substr($operand, 0, 1);

            if (!in_array($operand,self::$operators) && !is_numeric($operand) && $first_word!="@") {
                $variables += array($operand => $operand);
            } elseif ($operand == 'LOOKUP' || $operand == 'SYS_DATE') {
                array_pop($variables);
            }

        }

        return array_keys($variables);
    }

    public static function extractConstantsFromRPN($rpn_expr)
    {
        $rpn_arr = explode(' ', $rpn_expr);

        $variables = array();
        foreach ($rpn_arr as $operand) {
            $first_word = substr($operand, 0, 1);
            $last_word = substr($operand, -1);

            if ($first_word=="@" && $last_word=="@") {
                $variables += array(trim($operand, "@") => trim($operand, "@"));
            }
        }

        return array_keys($variables);
    }

    public static function InfixToRPN($infix){
        $rpn = '';

        // felesleges dupla szóközök eltüntetése
        $infix = trim(preg_replace('/\s+/', ' ', $infix));

        // az operátorokból valid regexp készítése
        $ops = implode('|', self::$operators);
        $ops = str_replace(array('+', '-', '*', '/'), array('\+', '\-', '\*', '\/'), $ops);
        $pattern = '/(\s|\(|\)|,|'.$ops.')+/';

        // az operátorok mentén szétvagdosásához megszerezzük az offseteket
        $arr_offsets = preg_split($pattern, $infix, -1, PREG_SPLIT_OFFSET_CAPTURE);

        // fordított sorrendben kell rajtuk végigmenni, mert szóközöket fogunk beszúrni
        $arr_offsets = array_reverse($arr_offsets, true);

        // beszúrunk 1-1 szóközt a megtalált pozíciókra, és a megtalált nem-operátorok mögé is
        foreach ($arr_offsets as $a) {
            $infix = substr($infix, 0, $a[1]) . ' ' . substr($infix, $a[1], strlen($a[0])) . ' ' . substr($infix, $a[1]+strlen($a[0]));
        }

        // a legalább 2 karakteres relációs jeleket cseréljük szövegesre
        $ops2 = array();
        foreach (self::$text_operators as $k => $o) {
            if (strlen($k)>1) $ops2[$k] = $o;
        }
        $infix = str_replace(array_keys($ops2), array_values($ops2), $infix);
        // aztán a maradékot is
        $infix = str_replace(array_keys(self::$text_operators), array_values(self::$text_operators), $infix);

        // már csak a halmozott operátorok vannak egyben -> őket is szétválasztjuk
        $ops = self::$operators;
        $ops[] = '(';
        $ops[] = ')';
        $ops[] = ',';

        $replacement = array();
        foreach ($ops as $o) {
            $replacement[] = ' '.$o;
        }
        $infix = str_replace($ops, $replacement, $infix);

        // dupla szóközöket eltűntetjük
        $infix = trim(preg_replace('/\s+/', ' ', $infix));

        // most lehet szétvagdosni szóközök mentén
        $infix_arr = explode(' ', $infix);

        // FELDOLGOZÁS
        $op_stack = array();
        foreach ($infix_arr as $T) {
            if (array_key_exists($T, self::$preferred_operators)) $T = self::$preferred_operators[$T];
            if ($T == '(') {
                $op_stack[] = $T;
            } elseif ($T == ',') {
                while (!empty($op_stack) && $op_stack[count($op_stack)-1] != '(') {
                    $o = array_pop($op_stack);
                    $rpn .= ' ' . $o;
                }
            } elseif (!in_array($T, $ops)) {
                $rpn .= ' ' . $T;
            } elseif ($T != ')') {
                if (empty($op_stack) || $op_stack[count($op_stack)-1]=='(' || (self::$precedence[$op_stack[count($op_stack)-1]] < self::$precedence[$T])) {
                    $op_stack[] = $T;
                } else {
                    do {
                        $o = array_pop($op_stack);
                        $rpn .= ' ' . $o;
                    } while (!empty($op_stack) && $op_stack[count($op_stack)-1]!='(' && (self::$precedence[$op_stack[count($op_stack)-1]] > self::$precedence[$T]));

                    $op_stack[] = $T;
                }
            } else {    /* ) */
                while (!empty($op_stack) && $op_stack[count($op_stack)-1] != '(') {
                    $o = array_pop($op_stack);
                    $rpn .= ' ' . $o;
                }

                if ($op_stack[count($op_stack)-1] == '(') $o = array_pop($op_stack);

                if (count($op_stack)>0 && in_array($op_stack[count($op_stack)-1], self::$functions)) {
                    $o = array_pop($op_stack);
                    $rpn .= ' ' . $o;
                }
            }
        }

        // no more tokens left in expression - clear stack into output
        while (!empty($op_stack)) {
            $o = array_pop($op_stack);
            if ($o != '(') $rpn .= ' ' . $o;
        }

        return trim($rpn);
    }

    public static function RPNToInfix($rpn){
        $tree = self::buildRPNTree($rpn);
        return $tree->toString();
    }

    private static function buildRPNTree($rpn)
    {
        $stack = array();

        // felesleges dupla szóközök eltüntetése
        $rpn = trim(preg_replace('/\s+/', ' ', $rpn));
        $rpn_array = explode(' ', $rpn);

        foreach ($rpn_array as $rpn_item) {
            if (in_array($rpn_item, self::$operators)) {
                $args = array();
                for($i=0; $i<self::$operands_by_operator[$rpn_item]; $i++) {
                    if (empty($stack)) throw new EException('Invalid RPN expression!');
                    $args[] = array_pop($stack);
                }

                // kicseréljük a megfelelő (preferált) operátorra, ha lehet
                if (array_key_exists($rpn_item, self::$preferred_operators)) $rpn_item = self::$preferred_operators[$rpn_item];

                $stack[] = new RPNTreeNode(true, $rpn_item, array_reverse($args));
            } else {
                $stack[] = new RPNTreeNode(false, $rpn_item);
            }
        }

        return array_pop($stack);
    }
    
    public static function RPNToDisplayName($rpn_expr, $infix_expr) {
        $meters = self::extractMetersFromRPN($rpn_expr);
        
        $command = Yii::app()->db->createCommand();
        $command->select('identifier, name')
                ->from(Meter::model()->tableName())
                ->where("identifier IN ('".  implode("','", $meters). "')");
        
        $meter_rows = $command->queryAll();

        foreach ($meter_rows AS $meter_row){
            $infix_expr = str_replace($meter_row['identifier'], $meter_row['name'], $infix_expr);
        }
        
        return $infix_expr;
    }

}

class RPNTreeNode
{
    public $is_operator;
    public $operator;
    public $value;
    public $children = array();

    public function __construct($is_operator, $op_val, $children = array()){
        $this->is_operator = $is_operator;
        if ($is_operator) $this->operator = $op_val;
                    else  $this->value = $op_val;
        $this->children = $children;
    }

    public function toString(){
        if (!$this->is_operator) return $this->value;

        $children_text = array();
        foreach($this->children as $child) {
            $children_text[] = $child->toString();
        }

        if (in_array($this->operator, RPN::$functions)) {
            return $this->operator . '(' . implode(', ',$children_text) . ')';
        } else {
            $left = $this->children[0]->is_operator ? (RPN::$precedence[$this->operator] > RPN::$precedence[$this->children[0]->operator] ? '('.$children_text[0].')' : $children_text[0]) : $children_text[0];

            if ($this->children[1]->is_operator) {
                if (in_array($this->operator, RPN::$commutative_operators)) {
                    $right = RPN::$precedence[$this->operator] > RPN::$precedence[$this->children[1]->operator] ? '('.$children_text[1].')' : $children_text[1];
                } else {
                    $right = RPN::$precedence[$this->operator] >= RPN::$precedence[$this->children[1]->operator] ? '('.$children_text[1].')' : $children_text[1];
                }
            } else {
                $right = $children_text[1];
            }
            return  $left . ' ' . $this->operator . ' ' . $right;
        }
    }
}

