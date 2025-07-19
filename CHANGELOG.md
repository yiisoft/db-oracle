# Oracle driver for Yii Database Change Log

## 2.0.0 under development

- Chg #332: Use `\InvalidArgumentException` instead of `Yiisoft\Db\Exception\InvalidArgumentException` (@DikoIbragimov)
- Enh #268: Rename `batchInsert()` to `insertBatch()` in `DMLQueryBuilder` and change parameters 
  from `$table, $columns, $rows` to `$table, $rows, $columns = []` (@Tigrov)
- Enh #260: Support `Traversable` values for `DMLQueryBuilder::batchInsert()` method with empty columns (@Tigrov)
- Enh #255, #321: Implement and use `SqlParser` class (@Tigrov)
- New #236: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Chg #272: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Enh #275: Refactor PHP type of `ColumnSchemaInterface` instances (@Tigrov)
- Enh #277: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- New #276, #288: Implement `ColumnFactory` class (@Tigrov)
- Enh #279: Separate column type constants (@Tigrov)
- New #280, #291: Realize `ColumnBuilder` class (@Tigrov)
- Enh #281: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #282, #291, #299, #302: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Bug #285: Fix `DMLQueryBuilder::insertBatch()` method (@Tigrov)
- Enh #283: Refactor `Dsn` class (@Tigrov)
- Enh #286: Use constructor to create columns and initialize properties (@Tigrov)
- Enh #288, #317: Refactor `Schema::findColumns()` method (@Tigrov)
- Enh #289: Refactor `Schema::normalizeDefaultValue()` method and move it to `ColumnFactory` class (@Tigrov)
- New #292: Override `QueryBuilder::prepareBinary()` method (@Tigrov)
- Chg #294: Update `QueryBuilder` constructor (@Tigrov)
- Enh #293: Use `ColumnDefinitionBuilder` to generate table column SQL representation (@Tigrov)
- Enh #296: Remove `ColumnInterface` (@Tigrov)
- Enh #298: Rename `ColumnSchemaInterface` to `ColumnInterface` (@Tigrov)
- Enh #298: Refactor `DMLQueryBuilder::prepareInsertValues()` method (@Tigrov)
- Enh #299: Add `ColumnDefinitionParser` class (@Tigrov)
- Enh #299: Convert database types to lower case (@Tigrov)
- Enh #300: Replace `DbArrayHelper::getColumn()` with `array_column()` (@Tigrov)
- New #301: Add `IndexType` class (@Tigrov)
- New #303: Support JSON type (@Tigrov)
- Bug #305: Explicitly mark nullable parameters (@vjik)
- Chg #306: Change supported PHP versions to `8.1 - 8.4` (@Tigrov)
- Enh #306: Minor refactoring (@Tigrov)
- New #307: Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and
  `DDLQueryBuilderInterface::dropTable()` methods (@vjik)
- Chg #310: Remove usage of `hasLimit()` and `hasOffset()` methods of `DQLQueryBuilder` class (@Tigrov)
- Enh #313: Refactor according changes in `db` package (@Tigrov)
- New #311: Add `caseSensitive` option to like condition (@vjik)
- Enh #315: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)
- Enh #319: Support `boolean` type (@Tigrov)
- Enh #318, #320: Use `DbArrayHelper::arrange()` instead of `DbArrayHelper::index()` method (@Tigrov)
- New #316: Realize `Schema::loadResultColumn()` method (@Tigrov)
- New #323: Use `DateTimeColumn` class for datetime column types (@Tigrov)
- Enh #324: Refactor `Command::insertWithReturningPks()` method (@Tigrov)
- Enh #325: Refactor `DMLQueryBuilder::upsert()` method (@Tigrov)
- Chg #326: Add alias in `DQLQueryBuilder::selectExists()` method for consistency with other DBMS (@Tigrov)
- Enh #327: Refactor constraints (@Tigrov)
- Chg #330: Rename `insertWithReturningPks()` to `insertReturningPks()` in `Command` and `DMLQueryBuilder` classes (@Tigrov)
- Enh #336: Provide `yiisoft/db-implementation` virtual package (@vjik)
- Enh #340: Adapt to removing `ParamInterface` from `yiisoft/db` package (@vjik)

## 1.3.0 March 21, 2024

- Enh #248: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Enh #251: Allow to use `DMLQueryBuilderInterface::batchInsert()` method with empty columns (@Tigrov)
- Enh #253: Resolve deprecated methods (@Tigrov)
- Bug #238: Fix execution `Query` without table(s) to select from (@Tigrov)
- Bug #250: Fix `Command::insertWithReturningPks()` method for table without primary keys (@Tigrov)
- Bug #254: Fix, table sequence name should be null if sequence name not found (@Tigrov)

## 1.2.0 November 12, 2023

- Enh #230: Improve column type #230 (@Tigrov)
- Enh #243: Move methods from `Command` to `AbstractPdoCommand` class (@Tigrov)
- Bug #233: Refactor `DMLQueryBuilder`, related with yiisoft/db#746 (@Tigrov)
- Bug #240: Remove `RECURSIVE` expression from CTE queries (@Tigrov)
- Bug #242: Fix `AbstractDMLQueryBuilder::batchInsert()` for values as associative arrays, 
  related with yiisoft/db#769 (@Tigrov)

## 1.1.0 July 24, 2023

- Enh #225: Typecast refactoring (@Tigrov)
- Enh #226: Add support for auto increment in primary key column. (@terabytesoftw)
- Bug #229: Fix bugs related with default value (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
