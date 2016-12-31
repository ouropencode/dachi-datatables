<?php
namespace Dachi\DataTables;

use Dachi\Core\Request;
use Dachi\Core\Database;

class DTFilter {

	protected $column;
	protected $comparison;
	protected $value;

	protected $uniq_id;

	public function __construct($column, $comparison, $value) {
		$this->column     = $column;
		$this->comparison = $comparison;
		$this->value      = $value;

		$this->uniq_id    = preg_replace("/[^a-zA-Z0-9]/", "", $column) . "_" . substr(md5($column . $comparison . uniqid('', true)), 0, 4);
	}

	public function getColumn() {
		return $this->column;
	}

	public function getComparison() {
		return $this->comparison;
	}
	public function getWhere() {
		return $this->getColumn()." ".$this->getComparison()." :".$this->uniq_id;
	}
	
	public function setWhere($query) {
		$value = "";
		switch($this->getComparison()) {
		case "LIKE":
		case "like":
			$value = "%" . $this->getValue() . "%";
			break;
		default:
			$value = $this->getValue();
		break;
		}
		$query->setParameter($this->uniq_id, $value);
		return $query;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function applyTo($query) {
		$value = "";
		switch($this->getComparison()) {
			case "LIKE":
			case "like":
				$value = "%" . $this->getValue() . "%";
				break;

			default:
				$value = $this->getValue();
				break;
		}

		$query = $query->andWhere($this->getColumn() . " " . $this->getComparison() . " :" . $this->uniq_id)
			->setParameter($this->uniq_id, $value);

		return $query;
	}

}
