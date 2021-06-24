<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model {
    public function getTableColumns() {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
    
    /**
     * insert or update a record
     *
     * @ref https://blog.csdn.net/szuaudi/article/details/105575139
     * @param array $values
     * @param array $value
     * @return bool
     */
    public function insertOrUpdate(Array $values, Array $value = [])
    {
        /* call method if child object has set___Attribute(). */
        // modified by undersky.
        $methods = get_class_methods($this);
        foreach ($values as $k => $v) {
            $setAttr = "set{$k}Attribute";
            if (in_array($setAttr, $methods)) {
                $this->$setAttr($v);
                $values[$k] = $this->attributes[$k];
            }
        }
        if (empty($value)) { $value = $values; }
        /* call method if child object has set___Attribute(). */

        $connection = $this->getConnection();          // 資料庫連結
        $builder    = $this->newQuery()->getQuery();   // 查詢構造器
        $grammar    = $builder->getGrammar();          // 語法器
        // 編譯Insert SQL
        $insert = $grammar->compileInsert($builder, $values); 
        // 編譯重復後的更新SQL。
        $update = $this->compileUpdateColumns($grammar, $value);
        // 組裝查詢SQL
        $query = $insert.' on duplicate key update '.$update;
        // 組裝SQL並綁定參數
        $bindings = $this->prepareBindingsForInsertOrUpdate($values, $value);
        // 執行資料庫查詢
        return $connection->insert($query, $bindings);
    }

    /**
     * Compile all of the columns for an update statement.
     *
     * @param Grammar $grammar
     * @param array $values
     * @return string
     */
    private function compileUpdateColumns($grammar, $values)
    {
        return collect($values)->map(function ($value, $key) use ($grammar) {
            return $grammar->wrap($key).' = '.$grammar->parameter($value);
        })->implode(', ');
    }

    /**
     * Prepare the bindings for an insert or update statement.
     *
     * @param array $values
     * @param array $value
     * @return array
     */
    private function prepareBindingsForInsertOrUpdate(Array $values, Array $value)
    {
        // Merge array of bindings
        $bindings = array_merge_recursive($values, [$value]);
        // Remove all of the expressions from a list of bindings.
        return array_values(array_filter(array_flatten($bindings, 1), function ($binding) {
            return ! $binding instanceof \Illuminate\Database\Query\Expression;
        }));
    }
}