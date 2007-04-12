<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/*
    MySQL Query Builder
    Copyright © 2005-2007  Alexey Zakhlestin <indeyets@gmail.com>
    Copyright © 2005-2006  Konstantin Sedov <kostya.online@gmail.com>

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

class BasicQuery
{
    private $limit = null;
    private $conditions = null;
    private $parameters;
    private $sql = null;
    private $orderby;
    private $orderdirection;
    private $havings;

    protected $from = array();

    protected function __construct($tables)
    {
        $this->setTables($tables);
    }

    public function setTables($tables)
    {
        if (is_string($tables) or $tables instanceof QBTable)
            $tables = array($tables);

        if (!is_array($tables))
            throw new InvalidArgumentException('table(s) should be specified as a string, or array of strings');

        if (count($tables) == 0)
            throw new InvalidArgumentException('Не указано ни одной таблицы');

        $this->from = array();
        foreach ($tables as $table) {
            if (is_string($table)) {
                $this->from[] = new QBTable($table);
            } elseif ($table instanceof QBTable) {
                $this->from[] = $table;
            } else {
                throw new LogicException("В качестве таблицы передан неправильный тип поля");
            }
        }

        $this->reset();
    }

    public function setWhere($conditions = null)
    {
        if (null === $conditions) {
            $this->conditions = null;
        } elseif ($conditions instanceof MQB_Condition) {
            $this->conditions = clone $conditions;
        } else {
            throw new InvalidArgumentException('Условия where не являются допустимым объектом');
        }

        $this->reset();
    }

    public function setHaving($conditions)
    {
        $this->havings = $conditions;
        $this->reset();
    }

    public function setOrderby(array $orderlist, array $orderdirectionlist = null)
    {
        foreach ($orderlist as $field)
            if (!($field instanceof Field))
                throw new InvalidArgumentException('Допускается только массив объектов типа Field');

        $this->orderby = $orderlist;

        if (null === $orderdirectionlist)
            $this->orderdirection = array();
        else 
            $this->orderdirection = $orderdirectionlist;

        $this->reset();
    }

    public function setLimit($limit, $offset=0)
    {
        if (!is_numeric($limit) or !is_numeric($offset))
            throw new InvalidArgumentException('параметрами должны быть числа');

        $this->limit = array($limit, $offset);
    }

    //
    public function showTables()
    {
        $res = array();
        foreach ($this->from as $table) {
            $res[] = $table->getTable();
        }

        return $res;
    }

    public function showConditions()
    {
        return $this->conditions;
    }


    // internal stuff
    protected function getFrom(&$parameters)
    {
        $froms = array();
        for ($i = 0; $i < count($this->from); $i++) {
            $froms[] = $this->from[$i]->__toString().' AS `t'.$i.'`';
        }

        $sql = ' FROM '.implode(", ", $froms);

        return $sql;
    }

    protected function getWhere(&$parameters)
    {
        if (null === $this->conditions)
            return "";

        return " WHERE ".$this->conditions->getSql($parameters);
    }

    protected function getHaving(&$parameters)
    {
        if (!$this->havings)
            return "";

        return " HAVING ".$this->havings->getSql($parameters);
    }

    protected function getOrderby(&$parameters)
    {
        if (!$this->orderby || !is_array($this->orderby))
            return "";

        foreach ($this->orderby as $i=>$orderby) {
            if (array_key_exists($i, $this->orderdirection) && $this->orderdirection[$i])
                $direction = ' DESC';
            else
                $direction = ' ASC';

            $sqls[] = $orderby->getSql($parameters).$direction;
        }

        return " ORDER BY ".implode(", ", $sqls);
    }

    protected function getLimit(&$parameters)
    {
        if (null === $this->limit)
            return "";

        return " LIMIT ".$this->limit[0].' OFFSET '.$this->limit[1];
    }

    protected function reset()
    {
        $this->parameters = array();
        $this->sql = null;
    }

    // get your PDO string here
    public function sql()
    {
        if (null === $this->sql) {
            $this->parameters = array();
            $this->sql = $this->getSql($this->parameters);
        }

        return $this->sql;
    }

    // get your PDO parameters here
    public function parameters()
    {
        return $this->parameters;
    }
}
