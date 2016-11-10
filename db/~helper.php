<?php

function DB()
{
    return call_user_func_array(PHPKit\PHPKit::get(strtolower(__FUNCTION__)), func_get_args());
}

function insert($table, $data)
{
    $prefix = DB()->getHandle()->getPrefix();
    
    $keys = $values = '';
    foreach (array_keys($data) as $key) {
        $keys .= '`'.$key.'`,';
        $values .= ':'.$key.',';
    }
    $keys = substr($keys, 0, -1);
    $values = substr($values, 0, -1);

    $sql = 'INSERT INTO `'.$prefix.$table.'`('.$keys.') VALUES('.$values.')';
        
    $params = [];
    foreach ($data as $key=>$val) {
        $params[':'.$key] = $val;
    }
    unset($data);
    
    return DB()->insert($sql, $params);
}

function delete($table, $where='', $order='', $limit='')
{
    $prefix = DB()->getHandle()->getPrefix();

    $params = [];
    
    if ($where && is_array($where)) {
        $str = '';
        foreach ($where as $k=>$v) {
            $arr = explode(' ', $k);
            
            if (!isset($arr[1])) $arr[1] = '=';

            if (strtolower($arr[1]) == 'in') {
                if (is_string($v)) $v = explode(',', $v);
                $str .= ' AND `'.$arr[0].'` '.$arr[1].'('.implode(',', array_fill(0, count($v), '?')).')';
                foreach ($v as $v) $params[] = $v;
            } else {
                $str .= ' AND `'.$arr[0].'` '.$arr[1].' ?';
                $params[] = $v;
            }
        }
        $where = ' WHERE '.substr($str, 5);
    }
    if (!is_string($where)) $where = '';
    
    $limit = $limit ? ' LIMIT '.$limit : '';

    $sql = 'DELETE FROM `'.$prefix.$table.'`'.$where.$limit;
    
    return DB()->delete($sql, $params);
}

function find()
{
    $arg = func_get_args(); array_push($arg, 1);
    return ($return = call_user_func_array('findAll', $arg)) ? $return[0] : $return;
}

function findAll($table)
{
    $arg = func_get_args();
    
    if (isset($arg[1]) && is_string($arg[1]) && ($arg[1]=='*' || preg_match('/^[a-z]([^<>=]|[a-z0-9,`\(\)])+$/', $arg[1]))) {
        $fields = $arg[1];
        $where = isset($arg[2]) ? $arg[2] : '';
        $order = isset($arg[3]) ? $arg[3] : '';
        $limit = isset($arg[4]) ? $arg[4] : '';
    } else {
        $fields = '*';
        $where = isset($arg[1]) ? $arg[1] : '';
        $order = isset($arg[2]) ? $arg[2] : '';
        $limit = isset($arg[3]) ? $arg[3] : '';
    }

    $prefix = DB()->getHandle()->getPrefix();
    
    $params = [];
    
    if ($where && is_array($where)) {
        $str = '';
        foreach ($where as $k=>$v) {
            $arr = explode(' ', $k);
            
            if (!isset($arr[1])) $arr[1] = '=';

            if (strtolower($arr[1]) == 'in') {
                if (is_string($v)) $v = explode(',', $v);
                $str .= ' AND `'.$arr[0].'` '.$arr[1].'('.implode(',', array_fill(0, count($v), '?')).')';
                foreach ($v as $v) $params[] = $v;
            } else {
                $str .= ' AND `'.$arr[0].'` '.$arr[1].' ?';
                $params[] = $v;
            }
        }
        $where = substr($str, 5);
    }
    $where = $where&&is_string($where) ? ' WHERE '.$where : '';
    
    $order = $order ? ' ORDER BY '.$order : '';

    $limit = $limit ? ' LIMIT '.$limit : '';

    $sql = 'SELECT '.$fields.' FROM `'.$prefix.$table.'`'.$where.$order.$limit;
    
    return DB()->select($sql, $params);
}

function update($table, $data, $where='', $limit='')
{
    $prefix = DB()->getHandle()->getPrefix();
    
    $params = [];
    
    if (is_array($data)) {
        $str = '';
        foreach ($data as $k=>$v) {
            $str .= '`'.$k.'`=?,';
            $params[] = $v;
        }
        $set = $str ? ' SET '.substr($str, 0, -1) : '';
    }

    if ($where && is_array($where)) {
        $str = '';
        foreach ($where as $k=>$v) {
            $arr = explode(' ', $k);
            
            if (!isset($arr[1])) $arr[1] = '=';

            if (strtolower($arr[1]) == 'in') {
                if (is_string($v)) $v = explode(',', $v);
                $str .= ' AND `'.$arr[0].'` '.$arr[1].'('.implode(',', array_fill(0, count($v), '?')).')';
                foreach ($v as $v) $params[] = $v;
            } else {
                $str .= ' AND `'.$arr[0].'` '.$arr[1].' ?';
                $params[] = $v;
            }
        }
        $where = ' WHERE '.substr($str, 5);
    }
    if (!is_string($where)) $where = '';

    $limit = $limit ? ' LIMIT '.$limit : '';

    $sql = 'UPDATE `'.$prefix.$table.'`'.$set.$where.$limit;
    
    return DB()->update($sql, $params);
}