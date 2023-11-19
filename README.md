# Database @BMorais Code

[![Maintainer](http://img.shields.io/badge/maintainer-@brunobmorais-blue.svg?style=flat-square)](https://linkedin.com/in/brunobmorais)
[![Source Code](http://img.shields.io/badge/source-bmorais/database-blue.svg?style=flat-square)](https://github.com/brunobmorais/php-database)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)
[![Latest Version](https://img.shields.io/github/release/brunobmorais/php-database.svg?style=flat-square)](https://github.com/brunobmorais/php-database/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Quality Score](https://img.shields.io/scrutinizer/g/brunobmorais/php-database.svg?style=flat-square)](https://scrutinizer-ci.com/g/brunobmorais/php-database)
[![Total Downloads](https://img.shields.io/packagist/dt/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)

###### The database is a persistent abstraction component of your database that PDO has prepared instructions for performing common routines such as registering, reading, editing, and removing data.

O database é um componente para abstração de persistência no seu banco de dados que usa PDO com prepared statements
para executar rotinas comuns como cadastrar, ler, editar e remover dados.

## About BMorais Code

###### BMorais Code is a set of small and optimized PHP components for common tasks. Held by Bruno Morais. With them you perform routine tasks with fewer lines, writing less and doing much more.

BMorais Code é um conjunto de pequenos e otimizados componentes PHP para tarefas comuns. Mantido por Bruno Morais. Com eles você executa tarefas rotineiras com poucas linhas, escrevendo menos e fazendo muito mais.

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
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        PDO::ATTR_STRINGIFY_FETCHES => true
    ],
    "homologation" => "homologacao",
    "directory_models" => "App\\Models\\",
    "display_errors_details" => true,
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

    /**
    * @param $codusuario
    * @return array|null
    */
    public function buscarIdObj($codusuario)
    {
        $result = $this->select("*","WHERE CODUSUARIO=?",[$codusuario]);
        if ($result){
            return $result;
        } else {
            return null;
        }
    }

    /**
    * @param $codusuario
    * @return AutUserModel[] | null
     */
    public function buscarIdModelExample($codusuario)
    {
        $result = $this->select("*","WHERE CODUSUARIO=?",[$codusuario], true);
        if ($result){
            return $result;
        } else {
            return null;
        }
    }

    /**
    * @param $codusuario
    * @return AutUserModel[] | null
    */
    public function buscarIdModelExample2($codusuario)
    {
        $sql = "SELECT * FROM AUT_USER AS U WHERE U.CODUSUARIO=?";
        $params = array($codusuario);
        $result = $this->executeSQL($sql,$params);
        if (!empty($result)){
            return $this->getObjModel($result,$this->classModel);
        } else {
            return null;
        }
    }
    
    /**
    * @param $coduser
    * @return bool
     */
    public function updateUser($name, $email, $coduser)
    {
        $result = $this->update("NAME, EMAIL", array($name, $email), "CODUSER=?");
        if ($result){
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * @param $name
    * @return bool
    */
    public function insertUser($name, $email)
    {
        $result = $this->insert("NOME, EMAIL", array($name, $email));
        if ($result){
            return true;
        } else {
            return false;
        }
    }
    
    
}
```

## Contributing

Please see [CONTRIBUTING](https://github.com/brunobmorais/database/blob/master/CONTRIBUTING.md) for details.

## Support

###### Security: If you discover any security related issues, please email contato@bmorais.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para contato@bmorais.com em vez de usar o
rastreador de problemas.

Thank you

## Credits

- [Bruno Morais](https://github.com/brunobmorais) (Developer)

<!-- CONTRIBUTING -->
## Contributing

🚧 [Contributing Guidelines](https://github.com/angular/angular/blob/main/CONTRIBUTING.md) - Currently being updated 🚧

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the appropriate tag.
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Remember to include a tag, and to follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) and [Semantic Versioning](https://semver.org/) when uploading your commit and/or creating the issue.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- ACKNOWLEDGMENTS -->
## Aknowledgements
###### Thank you to all the people who contributed to this project, whithout you this project would not be here today.

Obrigado a todas as pessoas que contribuíram para este projeto, sem vocês este projeto não estaria aqui hoje.

<a href="https://github.com/flutterando/Uno/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=brunobmorais/php-database" />
</a>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## License

The MIT License (MIT). Please see [License File](https://github.com/brunobmorais/database/blob/master/LICENSE) for more
information.