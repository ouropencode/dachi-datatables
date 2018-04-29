<?php
namespace Dachi\DataTables;

use Dachi\Core\Request;
use Dachi\Core\Database;

class DTFilter {

	protected $column;
	protected $comparison;
	protected $value;

	protected $uniq_id;

	public function __construct($column, $comparison, $value = null, $is_boolean = false) {
		$this->column     = $column;
		$this->comparison = $comparison;
		$this->value      = $value;
		$this->is_boolean = $is_boolean;

		$this->uniq_id    = preg_replace("/[^a-zA-Z0-9]/", "", $column) . "_" . substr(md5($column . $comparison . uniqid('', true) . ($is_boolean ? "1" : "0")), 0, 4);
	}

	public function getColumn() {
		return $this->column;
	}

	public function getComparison() {
		return $this->comparison;
	}

	public function getWhere() {
		$against = $this->getComparison() == "IN" ? ("(:" . $this->uniq_id . ")") : (" :" . $this->uniq_id);
		return $this->getColumn() . " " . $this->getComparison() . " " . ($this->value !== null ? $against : "");
	}

	public function getIsBoolean() {
		return $this->is_boolean;
	}

	public function setWhere($query) {
		$value = "";
		switch($this->getComparison()) {
			case "IS NULL":
			case "IS NOT NULL":
				break;
			case "LIKE":
			case "like":
				$query->setParameter($this->uniq_id, "%" . $this->getValue() . "%");
				break;
			default:
				if($this->getIsBoolean()) {
					$query->setParameter($this->uniq_id, $this->getValue() ? true : false);
				} else {
					$query->setParameter($this->uniq_id, $this->getValue());
				}
			break;
		}
		return $query;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function applyTo($query) {
		$query->andWhere($this->getWhere());
		$this->setWhere($query);
		return $query;
	}

}
