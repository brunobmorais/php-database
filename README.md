# Database @BMoraisCode

[![Maintainer](http://img.shields.io/badge/maintainer-@brunomoraisti-blue.svg?style=flat-square)](https://twitter.com/brunomoraisti)
[![Source Code](http://img.shields.io/badge/source-bmorais/database-blue.svg?style=flat-square)](https://github.com/brunomoraisti/database)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)
[![Latest Version](https://img.shields.io/github/release/brunomoraisti/php-database.svg?style=flat-square)](https://github.com/brunomoraisti/database/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Quality Score](https://img.shields.io/scrutinizer/g/brunomoraisti/php-database.svg?style=flat-square)](https://scrutinizer-ci.com/g/brunomoraisti/php-database)
[![Total Downloads](https://img.shields.io/packagist/dt/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)

###### The database is a persistent abstraction component of your database that PDO has prepared instructions for performing common routines such as registering, reading, editing, and removing data.

O database é um componente para abstração de persistência no seu banco de dados que usa PDO com prepared statements
para executar rotinas comuns como cadastrar, ler, editar e remover dados.

## About BMoraisCode

###### BMorais is a set of small and optimized PHP components for common tasks. Held by Robson V. Leite and the UpInside team. With them you perform routine tasks with fewer lines, writing less and doing much more.

BMoraisCode é um conjunto de pequenos e otimizados componentes PHP para tarefas comuns. Mantido por Bruno Morais. Com eles você executa tarefas rotineiras com poucas linhas, escrevendo menos e fazendo muito mais.

### Highlights

- Easy to set up (Fácil de configurar)
- Total CRUD asbtration (Asbtração total do CRUD)
- Create safe models (Crie de modelos seguros)
- Composer ready (Pronto para o composer)
- PSR-2 compliant (Compatível com PSR-2)

## Installation

Database is available via Composer:

```bash
"bmorais/database": "1.0.*"
```

or run

```bash
composer require bmorais/database
```

## Documentation

###### For details on how to use the Data Layer, see the sample folder with details in the component directory

Para mais detalhes sobre como usar o Database, veja a pasta de exemplo com detalhes no diretório do componente

#### connection

###### To begin using the Data Layer, you need to connect to the database (MariaDB / MySql). For more connections [PDO connections manual on PHP.net](https://www.php.net/manual/pt_BR/pdo.drivers.php)

Para começar a usar o Data Layer precisamos de uma conexão com o seu banco de dados. Para ver as conexões possíveis
acesse o [manual de conexões do PDO em PHP.net](https://www.php.net/manual/pt_BR/pdo.drivers.php)

```php
const CONFIG_DATA_LAYER = [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "database",
    "username" => "user",
    "passwd" => "",
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8, lc_time_names = 'pt_BR'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::MYSQL_ATTR_FOUND_ROWS => true
    ],
    "directory_models" => "App\\Models\\",
    "display_errors_details" => true
];
```

#### your model

###### The Database is based on an MVC structure with the Layer Super Type and Active Record design patterns. Soon to consume it is necessary to create the model of your table and inherit the Data Layer.

O database é baseado em uma estrutura MVC com os padrões de projeto Layer Super Type e Active Record. Logo para
consumir é necessário criar o modelo de sua tabela e herdar o CRUD.

```php
<?php

class Usuario extends Crud
{
    public function __construct()
    {
        $this->database = "nomeBD";
        $this->tableName = "USUARIO";
        $this->classModel = "AutUserModel";
    }

    // BUSCAR E RETORNAR OBJ
    public function buscarIdObj($codusuario):?array{
        $result = $this->select("*","WHERE CODUSUARIO=?",[$codusuario]);
        if ($result){
            return $result;
        } else {
            return null;
        }
    }

    // BUSCAR E RETORNAR UM MODEL
    public function buscarIdModelExample($codusuario):?AutUserModel{
        $result = $this->select("*","WHERE CODUSUARIO=?",[$codusuario], true);
        if ($result){
            return $result;
        } else {
            return null;
        }
    }

    // BUSCAR E RETORNAR UM MODEL ATRAVES DE SQL
    public function buscarIdModelExample2($codusuario): ?AutUserModel{

        $sql = "SELECT * FROM AUT_USER AS U WHERE U.CODUSUARIO=?";
        $params = array($codusuario);
        $result = $this->executeSQL($sql,$params);
        if (!empty($result)){
            return $this->getObjModel($result,$this->classModel);
        } else {
            return null;
        }

    }
}
```

## Contributing

Please see [CONTRIBUTING](https://github.com/brunomoraisti/database/blob/master/CONTRIBUTING.md) for details.

## Support

###### Security: If you discover any security related issues, please email cursos@upinside.com.br instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para contato@bmorais.com em vez de usar o
rastreador de problemas.

Thank you

## Credits

- [Bruno Morais](https://github.com/brunomoraisti) (Developer)

## License

The MIT License (MIT). Please see [License File](https://github.com/brunomoraisti/database/blob/master/LICENSE) for more
information.