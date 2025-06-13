# 💊 PHPStan steroids for Dibi

![Checks](https://github.com/vojtech-dobes/phpstan-dibi-steroids/actions/workflows/checks.yml/badge.svg?branch=master&event=push)

PHPStan extension for [Dibi](https://dibiphp.com/) database library.

✅ infers types your queries return<br />
✅ supports MySQL, MariaDB, Postgres & Sqlite<br />
✅ understands views, CTEs, joins, WHEREs<br />
✅ doesn't require actual database<br />
✅ handles `fetchAssoc`, `setRowClass`…<br />



## Installation

To install the latest version, run the following command:

```
composer require vojtech-dobes/phpstan-dibi-steroids
```



### Configuration

Although this extension doesn't require actual database to work, it still requires instance of `Dibi\Connection` due to some internal stuff. It doesn't have to hold any data, but it should be same kind of database your app uses. Minimal configuration in your `phpstan.neon` can look like this:


```neon
includes:
  - vendor/vojtech-dobes/phpstan-dibi-steroids/extension.neon

parameters:
  dibi:
    database: Dibi\Connection([
      driver: pdo
      dsn: "sqlite::memory:"
    ])

    generatedDir: <path to temp directory>
```

Option `database` is actually a shortcut. The extension supports if you have multiple databases with different schema. In such case, you can setup `databases` instead where key is arbitrary name you give corresponding database. The `database` shortcut implicitly uses name `main`, so it's a shortcut for:

```neon
parameters:
  dibi:
    databases:
      main: Dibi\Connection([
        driver: pdo
        dsn: "sqlite::memory:"
      ])
````



### Database schema

The extension needs to know schema of your database. There are 2 ways you can do this.

#### Without database

This method requires that your database schema is described as `CREATE TABLE` statements in static file(s). You have to provide path to this file or files under the name of the database specified in the config above.

```neon
services:
  - class: Vojtechdobes\PHPStan\Dibi\StatementFilesSchemaProvider
    arguments:
      statementFiles:
        main: %rootDir%/../../../database.sql
```

`StatementFilesSchemaProvider` will parse the contents and construct database schema from the information in the file.

#### With database

If you prefer asking an actual database for the schema, you can use `ReflectorSchemaProvider`. Config then looks like this:

```neon
services:
  - Vojtechdobes\PHPStan\Dibi\ReflectorSchemaProvider
```



### How it works

The extension makes several Dibi classes generic (using PHPStan stubs): `Dibi\Row`, `Dibi\Result` & `Dibi\Connection`. All of these extra generic parameters are optional, so PHPStan won't complain about your codebase where such parameters are not specified.

#### `Dibi\Connection`

- `TDatabase of string`

  This parameter can be used to specify a database in case of having multiple databases. Not specifying it will match the implicit `main` name.
