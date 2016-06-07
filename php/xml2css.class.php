<?php

// Abandon all hope, ye who enter here.
class XML2CSS implements Serializable {
	const HEADER = <<<'EOH'
+---------------------------------------------------------------------+
|                                                                     |
|   __  ____  __ _     ____   ____ ____ ____          ____    ___     |
|   \ \/ /  \/  | |   |___ \ / ___/ ___/ ___|  __   _|___ \  / _ \    |
|    \  /| |\/| | |     __) | |   \___ \___ \  \ \ / / __) || | | |   |
|    /  \| |  | | |___ / __/| |___ ___) |__) |  \ V / / __/ | |_| |   |
|   /_/\_\_|  |_|_____|_____|\____|____/____/    \_/ |_____(_)___/    |
|                                                                     |
|                                                                     |
|                             designed by                             |
|                            Princess RTFM                            |
|                      lilith.rises.24@gmail.com                      |
+---------------------------------------------------------------------+
EOH;
	protected $parser;
	protected $flags;
	protected $css = "";
	protected $state = array(
		"inCSS" => false,
		"inRule" => false,
		"inVars" => false,
		"inComment" => false,
		"doneVars" => false,
		"doneCSS" => false,
		"started" => false,
		"done" => false,
	);
	protected $vars = array();
	protected $consts = array();
	function __construct($strict=false) {
		$this->flags = new Flags();
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'handlerElementStart', 'handlerElementEnd');
		xml_set_character_data_handler($this->parser, 'handlerCharacterData');
		$this->flags->strict($strict);
	}
	function __destruct() {
		xml_parser_free($this->parser);
	}
	private function handlerElementStart($parser, $elemName, $attribs) {
		if ($this->state['done']) {
			throw new MalformedSyntaxException("All tags must be within <css>");
		}
		$elemName = strtolower($elemName);
		if ($elemName == "css") {
			if ($this->state['started']) {
				if (!$this->state['inRule']) {
					throw new MalformedSyntaxException("Only one <css> section can exist");
				}
			}
			else {
				$this->state['started'] = true;
			}
		}
		elseif (!$this->state['started']) {
			throw new MalformedSyntaxException("All tags must be within <css>");
		}
		if ($this->state['inRule']) {
			if ($elemName == "__var") {
				if (empty($attribs['NAME'])) {
					$this->error("Variable access without name");
				}
				else {
					$type = empty($attribs['TYPE']) ? "" : strtolower($attribs['TYPE']);
					$name = $attribs['NAME'];
					switch ($type) {
						case "var":
							if (!empty($this->vars[$name])) {
								$this->css .= $this->vars[$name];
							}
							else {
								$this->error("Variable access with invalid name");
							}
							break;
						case "const":
							if (!empty($this->consts[$name])) {
								$this->css .= $this->consts[$name];
							}
							else {
								$this->error("Constant access with invalid name");
							}
							break;
						default:
							if (!empty($this->consts[$name])) {
								$this->css .= $this->consts[$name];
							}
							else {
								if (!empty($this->vars[$name])) {
									$this->css .= $this->vars[$name];
								}
								else {
									$this->error("Const/var access with invalid name");
								}
							}
							break;
					}
				}
			}
			else {
				$this->css .= "\t$elemName: ";
			}
		}
		else {
			switch ($elemName) {
				case "comment":
					$this->css .= "/*\n";
					$this->state['inComment'] = true;
					break;
				case "css":
					break;
				case "vars":
					if ($this->state['doneVars']) {
						throw new MalformedSyntaxException("Only one <vars> section can exist, must come before <rules>");
					}
					$this->state['inVars'] = true;
					break;
				case "var":
					if (!$this->state['inVars']) {
						$this->error("Variable declaration outside <vars> tag");
						return;
					}
					if (empty($attribs['NAME'])) {
						$this->error("Variable declaration without name");
					}
					if (empty($attribs['VALUE'])) {
						$this->error("Variable declaration without value");
					}
					$this->vars[$attribs['NAME']] = $attribs['VALUE'];
					break;
				case "const":
					if (!$this->state['inVars']) {
						$this->error("Constant declaration outside <vars> tag");
						return;
					}
					if (empty($attribs['NAME'])) {
						$this->error("Variable declaration without name");
					}
					if (empty($attribs['VALUE'])) {
						$this->error("Variable declaration without value");
					}
					if (array_key_exists($attribs['NAME'], $this->consts)) {
						$this->error("Constant cannot be redefined");
						return;
					}
					else {
						$this->consts[$attribs['NAME']] = $attribs['VALUE'];
					}
					break;
				case "rules":
					if ($this->state['inVars']) {
						throw new MalformedSyntaxException("Cannot nest <rules> inside <vars>");
					}
					if ($this->state['inCSS']) {
						throw new MalformedSyntaxException("Cannot nest <rules> inside <rules>");
					}
					if ($this->state['doneCSS']) {
						throw new MalformedSyntaxException("Only one <rules> section can exist");
					}
					$this->state['inCSS'] = true;
					$this->state['doneVars'] = true;
					break;
				case "rule":
					if (!$this->state['inCSS']) {
						throw new MalformedSyntaxException("Cannot define rule outside <rules>");
					}
					$for = "*";
					if (empty($attribs['FOR'])) {
						$this->error("Rule definition without selector");
					}
					else {
						$for = $attribs['FOR'];
					}
					$this->state['inRule'] = true;
					$this->css .= "$for {\n";
					break;
				default:
					$this->error("Unexpected " . $this->stringifyElem($elemName, $attribs) . " in XML", E_USER_ERROR);
					break;
			}
		}
	}
	private function handlerElementEnd($parser, $elemName) {
		$elemName = strtolower($elemName);
		switch ($elemName) {
			case "css":
				$this->state['done'] = true;
				break;
			case "vars":
				$this->state['inVars'] = false;
				$this->state['doneVars'] = true;
				break;
			case "var":
			case "const":
			case "__var":
				break;
			case "comment":
				$this->css .= "\n*/\n";
				$this->state['inComment'] = false;
				break;
			case "rules":
				$this->state['inCSS'] = false;
				$this->state['doneCSS'] = true;
				break;
			case "rule":
				$this->css .= "}\n";
				$this->state['inRule'] = false;
				break;
			default:
				if ($this->state['inRule']) {
					$this->css .= ";\n";
				}
		}
	}
	private function handlerCharacterData($parser, $data) {
		if (empty(trim($data))) {
			return;
		}
		if ($this->state['inRule']) {
			$this->css .= preg_replace("/[\n\r" . ($this->flags->strict ? "\t" : "") . "]/", "", $data);
		}
		elseif ($this->state['inComment']) {
			$this->css .= preg_replace("/[\t]/", "", $data);
		}
		else {
			$this->error("Plain cdata outside <rule>");
		}
	}
	protected function error($msg) {
		if ($this->flags->strict) {
			throw new StrictException($msg, null, array(
				"state" => $this->state,
				"flags" => $this->flags,
				"vars" => $this->vars
			));
		}
		else {
			trigger_error($msg, E_USER_WARNING);
		}
	}
	public static function stringifyElem($name, $attrs) {
		$ret = "<$name";
		foreach ($attrs as $k => $v) {
			$ret .= " $k=\"$v\"";
		}
		$ret .= ">";
		return $ret;
	}
	public function load($path) {
		$xml = file_get_contents($path);
		if ($xml === false) {
			trigger_error("File not found: $path", E_USER_WARNING);
			return;
		}
		xml_parse($this->parser, $xml);
	}
	public function from($xml) {
		xml_parse($this->parser, $xml);
	}
	function __invoke($data) {
		$tmp = new XML2CSS($this->flags->strict);
		if (file_exists($data)) {
			$xml = file_get_contents($data);
			if ($xml !== false) {
				$tmp->from($xml);
			}
			else {
				$tmp->from("<?xml version=\"1.0\"?><css><comment>Unable to read file $data</comment></css>");
			}
		}
		else {
			$tmp->from($data);
		}
		return $tmp->__toString();
	}
	function __toString() {
		return self::HEADER . "\n\n\n" . $this->css;
	}
	public function serialize() {
		$me = array(
			"css" => $this->css,
			"flags" => $this->flags,
			"state" => $this->state,
			"vars" => $this->vars,
			"consts" => $this->consts
		);
		return serialize($me);
	}
	public function unserialize($serialized) {
		$me = unserialize($serialized);
		$e = new IllegalArgumentException("Cannot unserialize with missing data");
		if (!array_key_exists("css", $me)) {
			throw $e;
		}
		if (!array_key_exists("flags", $me)) {
			throw $e;
		}
		if (!array_key_exists("state", $me)) {
			throw $e;
		}
		if (!array_key_exists("vars", $me)) {
			throw $e;
		}
		if (!array_key_exists("consts", $me)) {
			throw $e;
		}
		$this->__construct($me->flags->strict);
		$this->css = $me->css;
		$this->flags = $me->flags;
		$this->state = $me->state;
		$this->vars = $me->vars;
		$this->consts = $me->consts;
	}
	public function getErrorCode() {
		return xml_get_error_code($parser);
	}
	public function getErrorString() {
		return xml_error_string(xml_get_error_code($parser));
	}
	public function getFullError() {
		$code = $this->getErrorCode();
		$name = xml_error_string($code);
		return "$code $name";
	}
}

?>
