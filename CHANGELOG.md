# Oracle driver for Yii Database Change Log

## 2.0.0 under development

- Enh #268: Rename `batchInsert()` to `insertBatch()` in `DMLQueryBuilder` and change parameters 
  from `$table, $columns, $rows` to `$table, $rows, $columns = []` (@Tigrov)
- Enh #260: Support `Traversable` values for `DMLQueryBuilder::batchInsert()` method with empty columns (@Tigrov)
- Enh #255: Implement `SqlParser` and `ExpressionBuilder` driver classes (@Tigrov)
- New #236: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Chg #272: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Enh #275: Refactor PHP type of `ColumnSchemaInterface` instances (@Tigrov)
- Enh #277: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- New #276: Implement `ColumnFactory` class (@Tigrov)
- Enh #279: Separate column type constants (@Tigrov)
- New #280: Realize `ColumnBuilder` class (@Tigrov)
- Enh #281: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #282: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Bug #285: Fix `DMLQueryBuilder::insertBatch()` method (@Tigrov)
- Enh #283: Refactor `Dsn` class (@Tigrov)

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
