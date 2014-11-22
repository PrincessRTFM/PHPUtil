<?php

/**
 * The Flags class can keep track of arbitrarily named true/false flags.
 * It provides access to its flags in three ways:
 * <dl>
 * <dt>Object properties: <tt><i>$object</i>->myFlag = true</tt></dt>
 * <dd>If the value is <tt>null</tt>, the flag is unset. If the value is considered <tt>true</tt>, the flag is set to <tt>true</tt>. Otherwise, it's set to <tt>false</tt>.<br/>When accessing a flag, the returned value will be <tt>true</tt> or <tt>false</tt> only.</dd>
 * <dt>Object methods: <tt><i>$object</i>->myFlag(true)</tt></dt>
 * <dd>If no value is given, the value is returned. This allows you to use a flag as a callback.</dd>
 * <dt>Array indices: <tt><i>$object</i>['myFlag'] = true</tt></dt>
 * <dd>If the array index is null or empty, an <tt>E_USER_WARNING</tt> level error will be emitted.</dd>
 * </dl>
 * It is possible to iterate over the object as though it were an array. Any flag that is set - be it on or off - will be included. To unset a flag, set it to <tt>null</tt>.
 * @author RisingDemon
 */
class Flags implements ArrayAccess, Countable, Iterator, Serializable {
	private $flags;
	function __construct() {
		$flags = array();
	}
	// The core of the class
	function __set($name, $value) {
		return $this->offsetSet($name, $value);
	}
	function __get($name) {
		return $this->offsetGet($name);
	}
	function __isset($name) {
		return $this->offsetExists($name);
	}
	function __unset($name) {
		return $this->offsetUnset($name);
	}
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			trigger_error("Null offset used in array access of Flags object", E_USER_WARNING);
		}
		elseif (empty($offset)) {
			trigger_error("Empty offset used in array access of Flags object", E_USER_WARNING);
		}
		elseif (is_null($value)) {
			$this->__unset($name);
		}
		elseif ($value) {
			$this->flags[$offset] = true;
		}
		else {
			$this->flags[$offset] = false;
		}
	}
	public function offsetGet($offset) {
		return isset($this->flags[$offset]) ? $this->flags[$offset] : false;
	}
	public function offsetExists($offset) {
		return isset($this->flags[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->flags[$offset]);
	}
	function __call($name, $argv) {
		$argc = count($argv);
		if ($argc > 1) {
			trigger_error("Flags pseudo-method needs 0 or 1 args, given $argc", E_USER_NOTICE);
		}
		if ($argc > 0) {
			return $this->__set($name, $argv[0]);
		}
		return $this->__get($name);
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