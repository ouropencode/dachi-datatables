<?php
namespace Dachi\DataTables;

use Dachi\Core\Request;
use Dachi\Core\Database;

class DTRequest {

	protected $source;

	protected $start;
	protected $length;

	protected $search;
	protected $order;
	protected $columns;

	public static function process($source, $options = array()) {
		$dt = new DTRequest($source, $options);
		$dt->handle();
		return $dt;
	}

	public static function process_relation($relations) {
		$relation = Request::getUri("id", "[a-zA-Z0-9_-]+");
		if(!isset($relations[$relation]))
			return Request::setResponseCode("error", "Permission Denied");

		$relation_name = Request::getArgument("relation", "id");

		$final_search = array();
		$final_relation = $relations[$relation];
		if(is_array($final_relation)) {
			$final_search = $final_relation[1];
			$final_relation = $final_relation[0];
		}

		$data = array();
		foreach(Database::getRepository($final_relation)->findBy($final_search) as $row) {
			$data[] = array(
				"id" => $row->getId(),
				"value" => DTAccessor::access($row, $relation_name)
			);
		}

		Request::setData("records", $data);
	}

	public function __construct($source, $options = array()) {
		$this->source = $source;

		$this->start  = (int)Request::getArgument("start", 0, "[0-9]+");
		$this->length = (int)Request::getArgument("length", 10, "[0-9]+");

		$this->search  = Request::getArgument("search", array());
		$this->order   = Request::getArgument("order", array());
		$this->columns = Request::getArgument("columns", array());

		$this->options = array(
			"dataKey"          => isset($options["dataKey"])          ? $options["dataKey"]          : "records",
			"totalKey"         => isset($options["totalKey"])         ? $options["totalKey"]         : "total",
			"filteredTotalKey" => isset($options["filteredTotalKey"]) ? $options["filteredTotalKey"] : "total_filtered",
			"extraKey"         => isset($options["extraKey"])         ? $options["extraKey"]         : "extra_data",
			"forcedFilters"    => isset($options["forcedFilters"])    ? $options["forcedFilters"]    : array(),
			"arguments"        => isset($options["arguments"])        ? $options["arguments"]        : array()
		);

		return true;
	}

	public function handle() {
		$options = Request::getArgument("options", array());

		$model = Database::getRepository($this->source);

		$table = $model->getDataTable($this, $options);

		if(!isset($table["extra_data"]))
			$table["extra_data"] = array();

		Request::setData("draw",                             (int)Request::getArgument("draw", "[0-9]+"));
		Request::setData($this->options["totalKey"],         $table["total"]);
		Request::setData($this->options["filteredTotalKey"], $table["total_filtered"]);
		Request::setData($this->options["dataKey"],          $table["records"]);
		Request::setData($this->options["extraKey"],         $table["extra_data"]);

	}

	public function getStartResult() {
		return $this->start;
	}

	public function getMaxResults() {
		return $this->length;
	}

	public function getOrderBy($mapping) {
		$orderBy = "";

		foreach($this->order as $by) {
			$column = $this->columns[$by["column"]]["data"];
			if(!isset($mapping[$column]))
				continue;

			$orderBy .= $mapping[$column] . " " . ($by["dir"] == "asc" ? "ASC" : "DESC") . ",";
		}

		if($orderBy == "")
			return "";

		return substr($orderBy, 0, -1);
	}

	public function getWhere($mapping) {
		$where = array();
		foreach($this->columns as $column) {
			if(!isset($column["search"]) || !isset($column["search"]["value"]) || !trim($column["search"]["value"]))
				continue;

			$search = json_decode($column["search"]["value"]);
			switch($search->type) {
				case "date":
					if($search->start)
						$where[] = new DTFilter($mapping[$column["data"]], ">=", $search->start);

					if($search->end)
						$where[] = new DTFilter($mapping[$column["data"]], "<=", $search->end);
					break;

				case "decimal":
					$modifiers = array(
						"eq" => "=",
						"gt" => ">",
						"lt" => "<"
					);

					if($search->value && isset($modifiers[$search->modifier]))
						$where[] = new DTFilter($mapping[$column["data"]], $modifiers[$search->modifier], $search->value);
					break;

				case "boolean":
					if(isset($search->value) && $search->value !== "")
						$where[] = new DTFilter($mapping[$column["data"]], "=", ($search->value == "true") ? true : false);
					break;

				case "relation":
					if(isset($search->value) && $search->value > 0)
						$where[] = new DTFilter($mapping[$column["data"]], "=", $search->value);
					break;

				case "enum":
				case "text":
				default:
					if(isset($search->value) && $search->value)
						$where[] = new DTFilter($mapping[$column["data"]], "LIKE", $search->value);
					break;
			}
		}

		foreach($this->options["forcedFilters"] as $filter)
			$where[] = new DTFilter($mapping[$filter->getColumn()], $filter->getComparison(), $filter->getValue());

		return $where;
	}

	public function hasWhere($column) {
		foreach($this->columns as $c) {
			if(!isset($c["data"]) || !isset($c["search"]) || !isset($c["search"]["value"]) || !trim($c["search"]["value"]))
				continue;

			if($c["data"] != $column)
				continue;

			$search = json_decode($c["search"]["value"]);
			if($search && trim($search->value))
				return true;
		}

		return false;
	}

}
