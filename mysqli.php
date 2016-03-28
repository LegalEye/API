<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 3/25/2016
 * Time: 11:24 PM
 */
class MySQLiDriver
{
    private $mysqli;

    function __construct($credentials)
    {
        $this->mysqli = new mysqli();

        $this->mysqli->connect(
            $credentials['Data Source'],
            $credentials['User Id'],
            $credentials['Password'],
            $credentials['Database']
        );

        if ($this->mysqli->errno) {
            throw new MySQLiNotConnectedException([$this->mysqli]);
        }
    }

    function select($sqlQuery, $parameters)
    {
        global $SQL_PREFIX;

        $sqlQuery = str_replace("tbl__", $SQL_PREFIX, $sqlQuery);

        if (!$query = $this->mysqli->prepare($sqlQuery)) {
            throw new MySQLiStatementNotPreparedException([$sqlQuery, $query]);
        }

        $bind = [];
        $parameterTypes = '';

        foreach ($parameters as $parameter) {
            foreach ($parameter as $type => $data) {
                $parameterTypes = $parameterTypes . $type;
                switch ($type) {
                    case 's':
                        $bind[] = (string)$data;
                        break;
                    case 'i':
                        $bind[] = (int)$data;
                        break;
                    case 'd':
                        $bind[] = (double)$data;
                        break;
                    case 'b':
                        $bind[] = $data;
                        break;
                    default:
                        throw new WhatTheHeckIsThisException([$type]);
                        break;
                }
            }
        }

        foreach ($bind as $parameter) {
            $boundParameters[] =  &$parameter;
        }
        array_unshift($boundParameters, $parameterTypes);

        call_user_func_array(array($query, 'bind_param'), $boundParameters);

        if (!$query->execute()) {
            throw new MySQLiSelectQueryFailedException(['sqlQuery' => $sqlQuery, 'parameters' => $parameters,
                'query' => $query, 'boundParameters' => $boundParameters]);
        }

        $result = $query->get_result();

        if ($result->num_rows < 1) {
            throw new MySQLiNothingSelectedException([$sqlQuery, $query, $result]);
        }

        $rows = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    function insert($sqlQuery, $parameters)
    {
        global $SQL_PREFIX;

        $sqlQuery = str_replace("tbl__", $SQL_PREFIX, $sqlQuery);

        if (!$query = $this->mysqli->prepare($sqlQuery)) {
            throw new MySQLiStatementNotPreparedException([$sqlQuery, $query]);
        }

        $bind = [];
        $parameterTypes = '';

        foreach ($parameters as $parameter) {
            foreach ($parameter as $type => $data) {
                $parameterTypes = $parameterTypes . $type;
                switch ($type) {
                    case 's':
                        $bind[] = (string)$data;
                        break;
                    case 'i':
                        $bind[] = (int)$data;
                        break;
                    case 'd':
                        $bind[] = (double)$data;
                        break;
                    case 'b':
                        $bind[] = $data;
                        break;
                    default:
                        throw new WhatTheHeckIsThisException([$type]);
                        break;
                }
            }
        }

        // Can't use foreach because the memory address for the AS variable changes and so all parameters get the same value.
        for ($i = 0; $i < count($bind); $i++) {
            $boundParameters[] =  &$bind[$i];
        }

        array_unshift($boundParameters, $parameterTypes);

        call_user_func_array(array($query, 'bind_param'), $boundParameters);

        $query->execute();

        //error_log('$query: ' . print_r($query,true));
        //error_log('$this->mysqli: ' . print_r($this->mysqli,true));

        $errorData = ['sqlQuery' => $sqlQuery, 'parameters' => $parameters, 'query' => $query, 'boundParameters' => $boundParameters, 'mysqli' => $this->mysqli];

        if ($query->errno > 0 OR $query->affected_rows < 0) {
            throw new MySQLiInsertQueryFailedException($errorData);
        }

        if ($query->affected_rows < 1) {
            throw new MySQLiRowNotInsertedException($errorData);
        }

        return $query;
        //$query->close();

    }
}