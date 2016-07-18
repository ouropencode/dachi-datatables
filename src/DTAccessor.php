<?php
namespace Dachi\DataTables;

class DTAccessor {

	protected $object;

	public static function access($object, $property) {
		$accessor = new DTAccessor($object);
		return $accessor->get($property);
	}

	public function __construct($object) {
		$this->object = $object;
	}

	public function get($property) {
		$getter = \Closure::bind(function($prop) {
			return $this->$prop;
		}, $this->object, $this->object);

		return $getter($property);
	}

}
