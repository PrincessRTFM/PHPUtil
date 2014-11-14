<?php

/**
 * The Flags class can keep track of arbitrarily named true/false flags.
 * It provides access to its flags in three ways:
 * <dl>
 * <dt>Object properties: <tt><i>$object</i>->myFlag = true</tt></dt>
 * <dd>If the value is considered <tt>true</tt> by PHP, the flag is set. Otherwise, it's unset.<br/>When accessing a flag, the returned value will be <tt>true</tt> if the flag is set, <tt>false</tt> if it isn't.</dd>
 * <dt>Object methods: <tt><i>$object</i>->myFlag(true)</tt></dt>
 * <dd>If no value is given, the value is inverted. It is not possible to retrieve the value of a flag with this method.</dd>
 * <dt>Array indices: <tt><i>$object</i>['myFlag'] = true</tt></dt>
 * <dd>If the array index is null or empty, an <tt>E_USER_WARNING</tt> level error will be emitted.</dd>
 * </dl>
 * @author RisingDemon
 */
class Flags implements ArrayAccess, Countable, Iterator, Serializable {
	private $flags;
	function __construct() {
		$flags = array();
	}
	// The core of the class
	function __set(string $name, mixed $value) {
		return $this->offsetSet($name, $value);
	}
	function __get(string $name) {
		return $this->offsetGet($name);
	}
	function __isset(string $name) {
		return $this->offsetExists($name);
	}
	function __unset(string $name) {
		return $this->offsetUnset($name);
	}
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			trigger_error("Null offset used in array access of Flags object", E_USER_WARNING);
		}
		elseif (empty($offset)) {
			trigger_error("Empty offset used in array access of Flags object", E_USER_WARNING);
		}
		elseif ($value) {
			$this->flags[$offset] = true;
		}
		else {
			$this->__unset($offset);
		}
	}
	public function offsetGet($offset) {
		return isset($this->flags[$offset]);
	}
	public function offsetExists($offset) {
		return isset($this->flags[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->flags[$name]);
	}
	function __call(string $name, array $args) {
		$v = -1;
		if (count($args) > 0) {
			$v = $args[0] ? 1 : 0;
		}
		switch ($v) {
			case -1:
				if ($this->__get($name)) {
					$this->__unset($name);
				}
				else {
					$this->__set($name, true);
				}
				break;
			case 0:
				$this->__unset($name);
				break;
			case 1:
				$this->__set($name, true);
			default:
				break;
		}
	}
	// Magic
	function __toString() {
		$ret = "Flags{";
		foreach ($this->flags as $k => $v) {
			$ret .= " \"$k\"=\"$v\"";
		}
		$ret .= "}";
		return $ret;
	}
	// Magic in PHP 5.6
	function __debugInfo() {
		return $this->flags;
	}
	// Implemented from Countable
	public function count() {
		return count($this->flags);
	}
	// Implemented from Iterator
	public function rewind() {
		reset($this->flags);
	}
	public function current() {
		return current($this->flags);
	}
	public function key() {
		return key($this->flags);
	}
	public function next() {
		return next($this->flags);
	}
	public function valid() {
		$k = $this->key();
		return ($k !== NULL && $k !== false);
	}
	// Implemented from Serializable
	public function serialize() {
		return serialize($this->flags);
	}
	public function unserialize($serialized) {
		$this->flags = unserialize($serialized);
	}
}

?>