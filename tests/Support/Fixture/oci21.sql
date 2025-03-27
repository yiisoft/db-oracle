BEGIN EXECUTE IMMEDIATE 'DROP TABLE "type"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--

/* STATEMENTS */

CREATE TABLE "type" (
  "int_col" integer NOT NULL,
  "int_col2" integer DEFAULT 1,
  "tinyint_col" number(3) DEFAULT 1,
  "smallint_col" smallint DEFAULT 1,
  "char_col" char(100) NOT NULL,
  "char_col2" varchar2(100) DEFAULT 'some''thing',
  "char_col3" varchar2(4000),
  "nvarchar_col" nvarchar2(100) DEFAULT '',
  "float_col" double precision NOT NULL,
  "float_col2" double precision DEFAULT 1.23,
  "blob_col" blob DEFAULT NULL,
  "numeric_col" decimal(5,2) DEFAULT 33.22,
  "timestamp_col" timestamp DEFAULT to_timestamp('2002-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss') NOT NULL,
  "timestamp_local" timestamp with local time zone,
  "time_col" interval day (0) to second(0) DEFAULT INTERVAL '0 10:33:21' DAY(0) TO SECOND(0),
  "interval_day_col" interval day (1) to second(0) DEFAULT INTERVAL '2 04:56:12' DAY(1) TO SECOND(0),
  "bool_col" char NOT NULL check ("bool_col" in (0,1)),
  "bool_col2" char DEFAULT 1 check("bool_col2" in (0,1)),
  "ts_default" TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  "bit_col" number(3) DEFAULT 130 NOT NULL,
  "json_col" json DEFAULT '{"a":1}'
);

/* TRIGGERS */

/* TRIGGERS */
