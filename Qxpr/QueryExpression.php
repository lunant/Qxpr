<?php

class QueryExpression implements Iterator, Countable, ArrayAccess {
	private $tableName;
	private $select;
	private $where;
	private $order;
	private $group;
	private $limit;
	private $dbh;
	private $proto;

    /* cache */
    private $queryList;

    /* iteration */
    private $currentRef;
    private $currentOffset;
    private $currentRow;

	private $alias;
	private $join;
	private $result;
	private $init;
	private $PK;
	private $describe;

	function __construct($dbh, $tableName) {
		$this->dbh = $dbh;
		$this->init = true;
		$this->_init($tableName);
	}

	function tableName() {
		return $this->alias
             ? $this->tableName." as ".$this->alias
             : $this->tableName;	
	}

	function alias() {
		return $this->alias ? $this->alias : $this->tableName;
	}

	function select() {
		$result = array();

		foreach($this->select as $item) {
			
			if(preg_match("/\(.*\)/", $item)) {
				if(preg_match("/\(\*\)/", $item))
					$result[] = $item;
				else if(preg_match("/\(.*\s.*\)/", $item))
					$result[] = preg_replace("/\((.*)\s(.*)\)/",
                                             "(\\1 ".$this->alias().".\\2)",
                                             $item);
				else
					$result[] = preg_replace("/\((.*)\)/",
                                             "(".$this->alias().".\\1)",
                                             $item);
			} else {
                $result[] = $this->alias().".$item";
            }   
		}			

		return $result;
	}

	function describe($ref = null) {
		$statement	= isset($ref) ? $ref
					: $this->dbh->query("SELECT * FROM {$this->tableName()}");

		$schema = array();

		for($i = 0, $length = $statement->columnCount(); $i < $length; ++$i) {
            $meta = $statement->getColumnMeta($i);
            if (!isset($meta["native_type"])) {
                $meta["native_type"] = "tiny_int";
            }
			$schema[] = $meta;
        }

		return $schema;
	}

	function PK() {
		if(isset($this->PK))
			return $this->PK;

		foreach($this->describe() as $record) {
			if(in_array('primary_key', $record['flags']))
				return $this->PK = $record['name'];
		}

		return false;
	}

    function exec($query = null, $debug = false) {
		if(is_null($query))
            return $this->exec($this->_createSelectQuery(), $debug);

		if($debug)
			echo $query;

        return $this->dbh->query($query);
    }

	function query($query = null, $debug = false) {
		$ref = $this->exec($query, $debug);
		return $ref ? $this->_fetch($ref) : null;
	}

	function q($query = null, $debug = false) {
		return $this->query($query, $debug);
	}

	function queryList($query = null, $debug = false) {
        if (is_null($query)) {
            if ($debug)
                echo $this->_createSelectQuery();

            if (!$this->queryList)
                $this->queryList = iterator_to_array($this);

            return $this->queryList;
        }

        $list = array();

        $ref = $this->exec($query, $debug);

        while ($drow = $this->_fetch($ref))
            $list[] = $drow;

        return $list;
	}

	function ql($query = null, $debug = false) {
		return $this->queryList($query, $debug);
	}

	function getOne($query = null, $debug = false) {
		$return = $this->query($query, $debug);
		return count($return) ? $return[0] : null;
	}

	function O($query = null, $debug = false) {
		return $this->getOne($query, $debug);
	}

	function insert($data, $debug = false) {
		$fieldInfo = array();
		$fieldValue = array();

		foreach($this->describe() as $tableFieldInfo) {
			$fieldName = $tableFieldInfo['name'];
			$fieldType = $tableFieldInfo['native_type'];

			if(isset($data[$fieldName]) and count($data[$fieldName]))
				$fieldInfo[] = "`{$fieldName}`";
			else
				continue;

			$fieldValue[] = $this->_toInsertValueByType($tableFieldInfo, $data);
		}

		$table = $this->__toString();
		$query = "INSERT INTO $table (".implode(',', $fieldInfo).') VALUES ('.implode(',', $fieldValue).')';

		if($debug) echo $query;

		$result = $this->dbh->query($query);

		return $result ? $this->dbh->lastInsertId() : false;
	}

	function i($data, $debug = false) {
		return $this->insert($data, $debug);
	}

	function update($data, $modifyFields = null, $debug = false) {
		$fieldValue = array();

		foreach($this->describe() as $tableFieldInfo) if($this->_isModifyField($tableFieldInfo, $modifyFields, $data)) {
			$fieldValue[]	= "{$tableFieldInfo['name']} = "
							. $this->_toInsertValueByType($tableFieldInfo, $data);
		}

		$this->_createWhereQuery($where);
		$table = $this->__toString();
		$query = "UPDATE $table SET ".join(',', $fieldValue).$where;

		if($debug) echo $query;

		return $this->dbh->query($query);
	}

	function u($data, $modifyFields = null, $debug = false) {
		return $this->update($data, $modifyFields, $debug);
	}

	private function _toInsertValueByType($tableFieldInfo, &$data) {
		if(preg_match('/int/', $tableFieldInfo['native_type']) and !count($data[$tableFieldInfo['name']]))
			return $data[$tableFieldInfo['name']];
		else
			return $this->dbh->quote($data[$tableFieldInfo['name']]);
	}

	private function _isModifyField($tableFieldInfo, $modifyFields, &$data) {
		$fieldName = $tableFieldInfo['name'];
		$fieldType = $tableFieldInfo['native_type'];

		if(is_array($modifyFields) and in_array($fieldName, $modifyFields))
			return true;
		else
			return !is_array($modifyFields) and array_key_exists($fieldName, $data);
	}

    function quote($str) {
        return $this->dbh->quote($str);
    }

	function get() {
		$obj = $this->copy();
		$args = func_get_args();

		if(count($args) and is_array($args[0])) $args = $args[0];

		foreach($args as $arg)
			array_push($obj->select, $arg);
		return $obj;
	} 

	private function _by($field, $var, $conn = "AND", $midConn = "") {
		if(!$midConn) $midConn = "=";
		$obj = $this->copy();

		if(strtoupper(trim($midConn)) == 'IN') {
			if(is_array($var))
				$var = "(".join(", ",
                    array_map(array($obj, "quote"), $var)
                ).")";
		} else if (is_bool($var)) {
            $midConn = $var ? "IS NOT" : "IS";
            $var = "NULL";
        } else {
            $var = $obj->quote($var);
        }
		
		if(!is_string($obj->where))
			array_push($obj->where, array('conn'=>$conn, 'exp'=>$field." ".$midConn." ".$var));
		return $obj;
	}

	function limit($first = 0, $second = null) {
		$obj = $this->copy();

		$obj->limit	= $second
					? "LIMIT $second OFFSET $first"
					: "LIMIT $first";

		return $obj;
	}

	function order($field, $opt = 'ASC') {
		$obj = $this->copy();
		$obj->order[] = array('field'=>$field, 'opt'=>$opt);
		return $obj;
	}
	
	function asc($field) {
		return $this->order($field, 'ASC');
	}
	
	function desc($field) {
		return $this->order($field, 'DESC');
	}
	
	function reverse() {
		foreach($this->order as $index => $item)
			$this->order[$index]['opt'] = ($item['opt'] == 'ASC') ? 'DESC' : 'ASC';

		foreach($this->join as $item)
			$item['obj']->reverse();
		
		return $this;
	}

	function group($field) {
		$obj = $this->copy();
		$obj->group = 'GROUP BY '.$obj->alias().".$field";
		return $obj;
	}

	function where($where) {
		$obj = $this->copy();
		$obj->where = $where;
		return $obj;	
	}

	function delete() {
		$obj = $this->copy();
		$this->dbh->query($obj->_createDeleteQuery());
		return $obj;
	}

	function nameAs($name) {
		$obj = $this->copy();
		$obj->alias = $name;
		return $obj;
	}

	function apply() {
		$vars = get_class_vars(get_class($this));
		foreach ($vars as $name => $value)
			$this->proto->$name = $this->$name;
		return $this->proto;
	}

	function createSelectQuery() {
		return $this->_createSelectQuery();	
	}

	private function _createSelectQuery() {		
		$this->_createFieldsAndTableQuery($table, $fields);
		$this->_createWhereQuery($where);
		$this->_createOrderQuery($order);
		$fields = ($fields) ? $fields : '*';
		$query = "SELECT ".$fields." FROM ".$table.$where;
		if($this->group) $query.=" ".$this->group;
		$query.=$order;
		if($this->limit) $query.=" ".$this->limit;
		
		return $query;
	}

	private function _createDeleteQuery() {
		$this->_createWhereQuery($where);
		$query = "DELETE FROM ".$this->tableName.$where;
		if($this->limit) $query.=" ".$this->limit;

		return $query;
	}

	private function _createFieldsAndTableQuery(&$table, &$fields) {
		if(!count($table)) {
			$table = $this->tableName();
			$fields = join(',', $this->select());
		}

		foreach($this->join as $item) {
			$table .= " ".$item['type']." ".$item['obj']->tableName()." ON ";
			if(is_array($item['keys']))
			{
				$item['keys']['pk'] = isset($item['keys']['pk']) ? $item['keys']['pk'] : $item['keys'][0];
				$item['keys']['fk'] = isset($item['keys']['fk']) ? $item['keys']['fk'] : $item['keys'][1];
				$table.=$this->alias().".".$item['keys']['pk']." = ".$item['obj']->alias().".".$item['keys']['fk'];
			}
			else
				$table.=$item['keys'];

			if(count($arr = $item['obj']->select())) {
				$fields = ($fields) ? $fields.', ' : $fields;
				$fields .=join(',', $arr);
			}

			$item['obj']->_createFieldsAndTableQuery($table, $fields);
		}
	}

	private function _createWhereQuery(&$where) {
		$whereStart = " WHERE ";
		if(!count($where) and count($this->where))$where .= $whereStart;

		if(is_array($this->where) and count($this->where)) {
			foreach($this->where as $item) {
				$item['exp'] = $this->alias().".".$item['exp'];
				if($where == $whereStart) 
					$where.=$item['exp'];
				else	
					$where.=" ".$item['conn']." ".$item['exp'];
			}
		}
		else if(is_string($this->where))
			$where.=$this->where;

		foreach($this->join as $item)
			$item['obj']->_createWhereQuery($where);		
	}
	
	private function _createOrderQuery(&$order) {
		$orderStart = " ORDER BY ";
		if(!count($order) and count($this->order)) $order .= $orderStart;
		
		foreach($this->order as $item) {
			if($order != $orderStart) $order.=', ';
			$order.= $this->alias().'.'.$item['field'].' '.$item['opt']; 
		}
		
		foreach($this->join as $item)
			$item['obj']->_createOrderQuery($order);
	}

	private function _init($tableName){
		if(!$this->init) return $this;

		if(count($this->join)) foreach($this->join as $item)
			$item['obj']->_init($item['obj']->tableName);

		$this->select = $this->where = $this->order = array();
		$this->limit = $this->group = $this->alias = '';
		$this->tableName = $tableName;
		$this->join = array();

		return $this;
	}

	private function _join($type, $other, $keys){	
		$obj = $this->copy();
		array_push($obj->join, array('type'=>$type, 'obj'=>$other, 'keys'=>$keys));
		return $obj;
	}

	private function _fetch($ref) {
		$map = array();
		$drow = array();

        $row = $ref->fetch();

		if($row) {
			foreach ($this->describe($ref) as $index => $field) {
				$table = isset($field['table']) ? $field['table'] : '';
				$column = $field['name'];

				if($table == '' or $table == $this->alias())
					$drow[$column] = $row[$index];
				else
					$drow[$table][$column] = $row[$index];

				$drow[$index] = $row[$index];
			}
			return $drow;
		} else {
            $this->ref = null;
			return array();
        }
	}

	function __toString() {
		return $this->tableName;
	}

	function __call($method, $args) {
		if(preg_match("/.*([bB]y)/",$method)) {
			$arr = preg_split("/[bB]y/", $method);
			$conn = ($arr[0]) ? $arr[0] : 'AND';
			$midConn = null;
			
			if($arr[1]) {
				$field = $arr[1];
                $field = preg_replace_callback(
                    "|([a-z]*)([A-Z])|",
                    create_function('$matches',
                        'return ($matches[1])
                              ? strtolower($matches[1]."_".$matches[2])
                              : strtolower($matches[2]);'),
                    $field
                );
				$var = isset($args[0]) ? $args[0] : null; 

				if(isset($args[1])) {
					$midConn = $var;
					$var = $args[1];
				}
			} else {
				$field = isset($args[0]) ? $args[0] : null;
				$var = isset($args[1]) ? $args[1] : null; 
				
				if(isset($args[2])) {
					$midConn = $var;
					$var = $args[2];
				}
			}

			return $this->_by($field, $var, $conn, $midConn);
		}
		else if(preg_match("/Join/", $method) or $method == 'join') {
			$type = preg_replace_callback("|([a-z]*)([A-Z][a-z]*)*(Join)|", create_function(
				'$matches',
				'return strtoupper($matches[1]." ".$matches[2]." ".$matches[3]);'
			), $method);

			$table	= is_string($args[0])
					? new self($this->dbh, $args[0])
					: $args[0];

			return $this->_join($type, $table, $args[1]);
		}
	} 

	function rewind() {
        if (!$this->queryList) {
            $this->currentOffset = 0;
            $this->currentRef = $this->exec();
        }
	}

	function current() {
        return $this->queryList
             ? current($this->queryList)
             : $this->currentRow;
	}

	function key() {
        return $this->queryList
             ? key($this->queryList)
             : $this->currentOffset;
	}

	function next() {
        return $this->queryList
             ? next($this->queryList)
             : ++ $this->currentOffset;
	}

	function valid() {
        if ($this->queryList) {
            return current($this->queryList) !== false;
        } else  {
            $this->currentRow = $this->_fetch($this->currentRef);
            return count($this->currentRow);
        }
	}

	function count() {
		$this->_createFieldsAndTableQuery($table, $fields);
		$this->_createWhereQuery($where);
		$this->_createOrderQuery($order);
		$fields = 'COUNT(*)';
		$query = "SELECT ".$fields." FROM ".$table.$where.$order;

		if($this->limit) $query.=" ".$this->limit;

		return $this->getOne($query);
	}

	function offsetExists($key) {
		$this->init = false;
		$this->result = $this->queryList();
		$this->init = true;

		if(is_string($key))
			return array_key_exists($key, $this->result[0]);
		else 
			return array_key_exists($key, $this->result);
	}

	function offsetGet($key) {
		$this->init = false;
		$this->result = $this->queryList();
		$this->init = true;

		if(is_string($key))
			return $this->result[0][$key];
		else 
			return $this->result[$key];
	}

	function offsetSet($key, $value) {
		$this->init = false;
		$this->result = $this->queryList();

		if(is_string($key)) {
			$this->result[0][$key] = $value;

			if($this->PK()) {
				$q = new QueryExpression($this->dbh, $this->tableName);
				$q->_by($this->PK(), $this->result[0][$this->PK()])
				->update(array($key=>$value));
			}
		}

		$this->init = true;
	}

	function offsetUnset($key) {
		return false;
	}

	function __get($key) {
		$this->result = $this->queryList();
		$this->init = true;

		return $this->result[0][$key];
	}

	function __set($key, $value) {
		$this->init = false;
		$this->result = $this->queryList();

		if(isset($this->result[0]) and $this->PK()) {
			$q = new QueryExpression($this->dbh, $this->tableName);

			$q->_by($this->PK(), $this->result[0][$this->PK()])->update(array(
				$key => $value
			));

			$this->init = true;
		}

		return $this->init;
	}

	function copy() {
        $proto = $this->proto;
        $queryList = $this->queryList;

        $this->proto = $proto ? $proto : $this;
        $this->queryList = null;
		$obj = clone $this;

        $this->proto = $proto;
        $this->queryList = $queryList;

		return $obj;
	}
}

