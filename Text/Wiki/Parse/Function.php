<?php

/*
<function name="" return="" access="">
type, descr
type, descr, default (if there is a default value, then it is optional!)
</function>
*/

class Text_Wiki_Parse_Function extends Text_Wiki_Parse {

	var $regex = '/^(\<function\>)\n(.+)\n(\<\/function\>)(\s|$)/Umsi';
	
	function process(&$matches)
	{
		// default options
		$opts = array(
			'name' => null,
			'access' => 'public',
			'return' => 'void',
			'params' => array(),
			'throws' => array()
		);
		
		// split apart the markup lines and loop through them
		$lines = explode("\n", $matches[2]);
		foreach ($lines as $line) {
			
			// skip blank lines
			if (trim($line) == '') {
				continue;
			}
			
			// find the first ':' on the line; the left part is the 
			// type, the right part is the value. skip lines without
			// a ':' on them.
			$pos = strpos($line, ':');
			if ($pos === false) {
				continue;
			}
			
			// $type is the line type: name, access, return, param, throws
			// 012345678901234
			// name: something
			$type = trim(substr($line, 0, $pos));
			$val = trim(substr($line, $pos+1));
			
			switch($type) {
			
			case 'param':
				$tmp = explode(',', $val);
				$k = count($tmp);
				if ($k == 1) {
					$opts['params'][] = array(
						'type' => $tmp[0],
						'descr' => null,
						'default' => null
					);
				} elseif ($k == 2) {
					$opts['params'][] = array(
						'type' => $tmp[0],
						'descr' => $tmp[1],
						'default' => null
					);
				} else {
					$opts['params'][] = array(
						'type' => $tmp[0],
						'descr' => $tmp[1],
						'default' => $tmp[2]
					);
				}
				break;
		
			case 'throws':
				$opts[$type][] = $val;
				
			case 'name':
			case 'return':
			case 'access':
			default:
				if ($type == 'returns') {
					$type = 'return';
				}
				
				if ($type == 'throw') {
					$type = 'throws';
				}
				
				$opts[$type] = $val;
				break;
			
			}
		}
		
		// add the token back in place
        return $this->wiki->addToken($this->rule, $opts) . $matches[4];
	}
}

?>