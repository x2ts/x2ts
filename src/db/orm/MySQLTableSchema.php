<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/8/18
 * Time: 下午3:44
 */

namespace x2ts\db\orm;


use x2ts\Toolkit;

/**
 * Class MySQLTableSchema
 *
 * @package x2ts\db\orm
 */
class MySQLTableSchema extends TableSchema {
    protected static $tables = array();

    public function load() {
        $db = $this->db;
        $cols = $db->query(
            'SELECT * FROM `information_schema`.`COLUMNS`' .
            ' WHERE `TABLE_SCHEMA`=:s AND `TABLE_NAME`=:n',
            [
                ':s' => $db->getDbName(),
                ':n' => $this->name,
            ]
        );
        $columns = [];
        $keys = [];
        $relations = [];
        foreach ($cols as $col) {
            $column = new Column();
            $column->name = $col['COLUMN_NAME'];
            $column->type = $col['DATA_TYPE'];
            $column->canBeNull = $col['IS_NULLABLE'] === 'YES';
            $column->defaultValue = $col['COLUMN_DEFAULT'];
            $column->position = $col['ORDINAL_POSITION'];
            if ($col['COLUMN_KEY'] === 'PRI') {
                $column->isPK = true;
                $keys['PK'] = $col['COLUMN_NAME'];
            }
            if ($col['COLUMN_KEY'] === 'UNI') {
                $column->isUQ = true;
                $keys['UQ'][] = $col['COLUMN_NAME'];
            }
            if ($col['COLUMN_KEY'] === 'MUL') {
                $keys['MU'][] = $col['COLUMN_NAME'];
            }
            $columns[$column->name] = $column;
        }
        // Find out belong-to-relation
        $rels = $db->query(
            'SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA`=:s AND `TABLE_NAME`=:n AND `REFERENCED_TABLE_NAME` IS NOT NULL',
            [':s' => $db->getDbName(), ':n' => $this->name]
        );
        foreach ($rels as $rel) {
            $relation = new BelongToRelation();
            $relation->property = $rel['COLUMN_NAME'];
            $relation->foreignTableName = $rel['REFERENCED_TABLE_NAME'];
            $relation->foreignModelName = Toolkit::toCamelCase($rel['REFERENCED_TABLE_NAME'], true);
            $relation->foreignTableField = $rel['REFERENCED_COLUMN_NAME'];
            if (strrpos($relation->property, '_id')) {
                $relation->name = substr($relation->property, 0, -3);
            } else {
                $relation->name = $relation->foreignTableName;
            }
            $relations[$relation->name] = $relation;
        }

        // Find out many-many-relation

        $rels = $db->query(
            <<<'SQL'
SELECT
  `TABLE_NAME`,
  `COLUMN_NAME`,
  `REFERENCED_TABLE_NAME`,
  `REFERENCED_COLUMN_NAME`
FROM
  `information_schema`.`KEY_COLUMN_USAGE`
  INNER JOIN
  (
    SELECT
      DISTINCT `TABLE_NAME`
    FROM
      `information_schema`.`KEY_COLUMN_USAGE`
      INNER JOIN
      (
        SELECT
          COUNT(*) AS `C`,
          `TABLE_NAME`
        FROM
          `information_schema`.`KEY_COLUMN_USAGE`
        WHERE
          `TABLE_SCHEMA` = :s AND `CONSTRAINT_NAME` = 'PRIMARY'
        GROUP BY
          `TABLE_NAME`
        HAVING `C` = 2
      ) mmrt USING (`TABLE_NAME`)
    WHERE `REFERENCED_TABLE_NAME` = :n
  ) rt USING (`TABLE_NAME`)
WHERE `TABLE_SCHEMA`=:sk AND `REFERENCED_TABLE_NAME` IS NOT NULL;
SQL
            ,
            [':s' => $db->dbName, ':n' => $this->name, ':sk' => $db->dbName]
        );
        $relationTableFKs = [];
        foreach ($rels as $rel) {
            $relationTableFKs[$rel['TABLE_NAME']][] = $rel;
        }
        foreach ($relationTableFKs as $rTableName => $fks) {
            if (count($fks) !== 2) {
                continue;
            }
            if ($fks[0]['REFERENCED_TABLE_NAME'] === $this->name) {
                list ($thisTableFK, $thatTableFK) = $fks;
            } else {
                list ($thatTableFK, $thisTableFK) = $fks;
            }
            $relation = new ManyManyRelation();
            $relation->name = Toolkit::pluralize($thatTableFK['REFERENCED_TABLE_NAME']);
            $relation->property = $thisTableFK['REFERENCED_COLUMN_NAME'];
            $relation->relationTableName = $rTableName;
            $relation->relationTableFieldThis = $thisTableFK['COLUMN_NAME'];
            $relation->relationTableFieldThat = $thatTableFK['COLUMN_NAME'];
            $relation->foreignTableName = $thatTableFK['REFERENCED_TABLE_NAME'];
            $relation->foreignTableField = $thatTableFK['REFERENCED_COLUMN_NAME'];
            $relation->foreignModelName = Toolkit::toCamelCase($thatTableFK['REFERENCED_TABLE_NAME'], true);
            $relations[$relation->name] = $relation;
        }

        // Find out has-many-relation
        $rels = $db->query(
            <<<'SQL'
SELECT
  kcu.REFERENCED_COLUMN_NAME,
  c.TABLE_NAME,
  c.COLUMN_NAME,
  c.COLUMN_KEY
FROM information_schema.KEY_COLUMN_USAGE AS kcu
  INNER JOIN information_schema.COLUMNS AS c
  USING (TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME)
WHERE TABLE_SCHEMA=:s AND REFERENCED_TABLE_NAME=:n
SQL
            ,
            [':s' => $db->dbName, ':n' => $this->name]
        );
        foreach ($rels as $rel) {
            if (array_key_exists($rel['TABLE_NAME'], $relationTableFKs)) {
                continue;
            }
            if ($rel['COLUMN_KEY'] === 'MUL') {
                $relation = new HasManyRelation();
            } else if ($rel['COLUMN_KEY'] === 'PRI' || $rel['COLUMN_KEY'] === 'UNI') {
                $relation = new HasOneRelation();
            } else {
                continue;
            }
            $relation->property = $rel['REFERENCED_COLUMN_NAME'];
            $relation->foreignTableName = $rel['TABLE_NAME'];
            $relation->foreignModelName = Toolkit::toCamelCase($rel['TABLE_NAME'], true);
            $relation->foreignTableField = $rel['COLUMN_NAME'];
            $relation->name = ($relation instanceof HasManyRelation) ?
                Toolkit::pluralize($rel['TABLE_NAME']) : $rel['TABLE_NAME'];
            if (array_key_exists($relation->name, $relations)) {
                $relations[$relation->name . ($relation instanceof HasOneRelation ? '_one' : '_many')]
                    = $relation;
            } else {
                $relations[$relation->name] = $relation;
            }
        }
        static::$tables[$this->db->dbName][$this->name] = array(
            'columns'   => $columns,
            'keys'      => $keys,
            'relations' => $relations,
        );
        if ($this->conf['useSchemaCache']) {
            $key = $this->getHash();
            $this->cache->set($key, static::$tables[$this->db->dbName][$this->name], $this->conf['schemaCacheDuration']);
        }
    }
}