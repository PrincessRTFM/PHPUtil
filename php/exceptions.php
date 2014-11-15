<?php

class InfoException extends Exception {
	protected $additionalData;
	function __construct($message, $previous=null, $extra=null) {
		parent::__construct($message, 0, $previous);
		$additionalData = is_null($extra) ? $this->getDefaultExtraData() : $extra;
	}
	final function setExtraData($extra) {
		if (!is_null($this->additionalData)) {
			$this->additionalData = $extra;
			return true;
		}
		return false;
	}
	final function hasExtraData() {
		return (!is_null($this->additionalData) && !empty($this->additionalData));
	}
	final function getExtraData() {
		if ($this->hasExtraData()) {
			return $this->additionalData;
		}
		else {
			return null;
		}
	}
	function getDefaultExtraData() {
		return null;
	}
	function __toString() {
		$ret = __CLASS__ . ": ";
		if ($this->hasExtraData()) {
			$ret .= "[" + $this->additionalData + "] ";
		}
		$ret .= $this->message;
		$ret .= "\n;";
		return $ret;
	}
}

class IllegalArgumentException extends InfoException {
	function getDefaultExtraData() {
		return "illegal argument";
	}
}

class MalformedSyntaxException extends InfoException {
	function getDefaultExtraData() {
		return "invalid syntax";
	}
}

?>