# Oracle driver for Yii Database Change Log

## 1.2.1 under development

- Enh #248: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Bug #250: Fix `Command::insertWithReturningPks()` method for table without primary keys (@Tigrov)

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
