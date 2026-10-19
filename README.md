<a id="readme-top"></a>

# Database BMORAIS Code

[![Maintainer](http://img.shields.io/badge/maintainer-@brunobmorais-blue.svg?style=flat-square)](https://linkedin.com/in/brunobmorais)
[![Source Code](http://img.shields.io/badge/source-bmorais/database-blue.svg?style=flat-square)](https://github.com/brunobmorais/php-database)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)
[![Latest Version](https://img.shields.io/github/release/brunobmorais/php-database.svg?style=flat-square)](https://github.com/brunobmorais/php-database/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Quality Score](https://img.shields.io/scrutinizer/g/brunobmorais/php-database.svg?style=flat-square)](https://scrutinizer-ci.com/g/brunobmorais/php-database)
[![Total Downloads](https://img.shields.io/packagist/dt/bmorais/database.svg?style=flat-square)](https://packagist.org/packages/bmorais/database)

###### A PDO-based persistence layer with prepared statements, CRUD, a fluent query builder, transactions and tools for large datasets.

O **database** é uma camada de persistência sobre PDO, sempre com *prepared statements*, para as rotinas comuns de
cadastrar, ler, editar e remover dados. Inclui um query builder fluente, transações com rollback garantido e
recursos para grandes volumes (inserção em lote e leitura linha a linha).

## Sumário

- [Destaques](#destaques)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Criando um DAO](#criando-um-dao)
- [Models](#models)
- [CRUD](#crud)
- [SQL direto e leitura do resultado](#sql-direto-e-leitura-do-resultado)
- [Query builder](#query-builder)
- [Transações](#transações)
- [Grandes volumes](#grandes-volumes)
- [Segurança](#segurança)
- [Erros, log e debug](#erros-log-e-debug)
- [Conexões](#conexões)
- [Exemplo completo](#exemplo-completo)

## Destaques

- CRUD completo com poucas linhas, sempre com *prepared statements*
- Query builder fluente (joins, where/andWhere/orWhere, groupBy/having, orderBy, limit/offset)
- Mapeamento automático do resultado para models (`ModelAbstract`)
- `transaction()` com rollback em qualquer erro e nova tentativa em deadlock
- `insertMany()` (INSERT em lote) e `cursor()` (leitura linha a linha) para tabelas grandes
- Conexão compartilhada entre DAOs, reconexão segura e suporte a ambiente de homologação
- Pronto para o Composer

## Instalação

Requer PHP 8.0+ com as extensões `pdo`, `json` e `mbstring`.

```bash
composer require bmorais/database
```

ou no `composer.json`:

```json
"bmorais/database": "^3.0"
```

## Configuração

Defina a constante global `CONFIG_DATA_LAYER` antes de usar a biblioteca. Para outros bancos, veja o
[manual de drivers do PDO](https://www.php.net/manual/pt_BR/pdo.drivers.php).

```php
const CONFIG_DATA_LAYER = [
    "driver"   => "mysql",
    "host"     => "localhost",
    "port"     => "3306",
    "dbname"   => "database",
    "username" => "user",
    "passwd"   => "",
    "options"  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8MB4, lc_time_names = 'pt_BR'",
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        PDO::ATTR_STRINGIFY_FETCHES  => true,
    ],
    "homologation"           => "homologacao",   // sufixo do banco de homologação
    "homologation_active"    => false,           // opcional (veja abaixo)
    "directory_models"       => "App\\Models\\", // namespace dos models
    "display_errors_details" => true,            // guarda o SQL para getLogSQL()
];
```

| Chave | Descrição |
|---|---|
| `options` | Opções do PDO. Use `UTF8MB4` (o `UTF8` do MySQL não grava emoji). `ERRMODE_EXCEPTION` é obrigatório para o tratamento de erros. |
| `homologation` | Em homologação, esse sufixo é adicionado ao nome do banco (`app` → `appHomologacao`). |
| `homologation_active` | Opcional. Define explicitamente se o ambiente é de homologação. Sem ela, vale a variável de ambiente `DB_HOMOLOGATION` e, por último, a detecção por `$_SERVER['SERVER_NAME']`. |
| `directory_models` | Namespace usado para instanciar os models (`setClassModel("UsuarioModel")` → `App\Models\UsuarioModel`). |
| `display_errors_details` | Quando `true`, guarda a última consulta para `getLogSQL()`. |

> **Homologação em Apache:** com `UseCanonicalName Off` (padrão), o `SERVER_NAME` vem do header `Host` da requisição.
> Defina `"homologation_active" => false` no config de produção para que um `Host` forjado não direcione a produção
> para o banco de homologação. Em Nginx, o `SERVER_NAME` vem do `server_name` configurado.

## Criando um DAO

Cada tabela tem um DAO que estende `Crud` (CRUD e SQL direto) ou `CrudBuilder` (tudo do `Crud` + query builder).

```php
use BMorais\Database\CrudBuilder;

class UsuarioDao extends CrudBuilder
{
    public function __construct()
    {
        $this->setTableName("USUARIO", "U");   // tabela e alias opcional
        $this->setClassModel("UsuarioModel");  // model usado quando o retorno é em models
        // $this->setDatabase("outro_banco");  // opcional: banco diferente de CONFIG_DATA_LAYER["dbname"]
    }
}
```

Os métodos de CRUD (`select`, `insert*`, `update*`, `delete`) são públicos. Os de execução e builder
(`executeSQL`, `fetch*`, `selectBuilder`, `where`, `transaction`, `cursor`...) são protegidos e são usados dentro do DAO.

## Models

Os models estendem `ModelAbstract` e são preenchidos automaticamente pelo PDO ou a partir de arrays/JSON.

```php
namespace App\Models;

use BMorais\Database\ModelAbstract;

class UsuarioModel extends ModelAbstract
{
    protected ?int $CODUSUARIO = null;
    protected ?string $NOME = null;
    protected ?string $EMAIL = null;

    public function getNOME(): ?string { return $this->NOME; }
}
```

```php
$model = new UsuarioModel(["NOME" => "Ana", "EMAIL" => "ana@x.com"]); // ou fromMapToModel() / fromJsonToModel()

$model->toArray();  // ["CODUSUARIO" => null, "NOME" => "Ana", "EMAIL" => "ana@x.com"] (usa os getters)
$model->toObject(); // stdClass
$model->toJson();   // {"CODUSUARIO":null,"NOME":"Ana","EMAIL":"ana@x.com"}
```

Propriedades tipadas sem valor (`public int $id;`) são lidas como `null` na serialização.

## CRUD

```php
// SELECT: select(campos, complemento SQL, valores, retornarModel)
$usuarios = $this->select("CODUSUARIO, NOME", "WHERE ATIVO = ? ORDER BY NOME", [1]);   // stdClass[]
$models   = $this->select("*", "WHERE CODUSUARIO = ?", [$id], true);                   // UsuarioModel[]
$comJoin  = $this->select("U.NOME, P.TELEFONE", "LEFT JOIN PERFIL P ON P.CODUSUARIO = U.CODUSUARIO WHERE U.CODUSUARIO = ?", [$id]);

// INSERT
$this->insert("NOME, EMAIL", [$nome, $email]);
$id = $this->lastInsertId();

$this->insertArray(["NOME" => $nome, "EMAIL" => $email]);  // valores null são ignorados
$this->insertObject($model->toObject());                     // propriedades públicas do objeto
$this->insertModel($model);                                  // lê também propriedades protected/private

// UPDATE: valores das colunas + valores do where
$this->update("EMAIL", [$email, $id], "CODUSUARIO = ?");
$this->updateArray(["NOME" => $nome], "CODUSUARIO = ?", [$id]);       // null é ignorado
$this->updateObject($model->toObject(), "CODUSUARIO = ?", [$id]);
$this->updateModel($model, "CODUSUARIO = ?", [$id], ["EMAIL"]);       // grava NULL nas colunas listadas

// DELETE
$this->delete([$id], "CODUSUARIO = ?");
```

> Não passe `$_POST` direto para `insertArray`/`updateArray`: monte o array só com as colunas permitidas.
> `update()`/`delete()` sem WHERE afetam a tabela inteira (e registram um aviso no log de erros do PHP).

## SQL direto e leitura do resultado

```php
$this->executeSQL("SELECT * FROM USUARIO WHERE ATIVO = ?", [1]);
$lista = $this->fetchArrayObj();
```

| Método | Retorno |
|---|---|
| `fetchArrayObj()` | lista de `stdClass` |
| `fetchArrayAssoc()` | lista de arrays associativos |
| `fetchArrayClass(null, "OutroModel")` | lista de models (`classModel` ou a classe informada) |
| `fetchOneObj()` / `fetchOneAssoc()` / `fetchOneClass()` | um registro ou `null` |
| `rowCount()` | linhas afetadas/retornadas pelo último comando |

Os `fetch*` também aceitam o `PDOStatement` retornado por `executeSQL()`. Parâmetros nomeados (`:email`) também
funcionam; não misture com `?` na mesma consulta.

## Query builder

Disponível em DAOs que estendem `CrudBuilder`. A ordem das chamadas é livre: o SQL é montado na ordem correta e os
parâmetros acompanham cada trecho. O estado é limpo a cada `executeQuery()`.

```php
$resultado = $this->selectBuilder("U.CODUSUARIO, U.NOME, P.DESCRICAO")
    ->innerJoin("PERFIL", "P", "P.CODPERFIL = U.CODPERFIL")   // também leftJoin / rightJoin
    ->where("U.ATIVO = ?", [1])
    ->andWhere("U.NOME LIKE ?", ["%{$nome}%"])
    ->orWhere("U.EMAIL LIKE ?", ["%{$nome}%"])
    ->groupBy("U.CODUSUARIO")
    ->having("COUNT(*) > 0")
    ->orderBy("U.NOME")
    ->addOrderBy("U.CODUSUARIO", "DESC")
    ->limit("20")
    ->offset("40")
    ->executeQuery()
    ->fetchArrayObj();

$this->insertBuilder("NOME, EMAIL", [$nome, $email])->executeQuery();
$this->updateBuilder("NOME", [$nome])->where("CODUSUARIO = ?", [$id])->executeQuery();
$this->deleteBuilder()->where("CODUSUARIO = ?", [$id])->executeQuery();

// SQL livre como trecho principal
$this->query("SELECT ... UNION SELECT ...", [$a, $b])->executeQuery()->fetchArrayAssoc();
```

Para ordenação vinda do usuário, use uma whitelist de colunas e direção (veja `listarOrdenado()` no exemplo).

## Transações

**Forma recomendada:** `transaction()` faz o commit ao final e o rollback em **qualquer** erro, relançando a exceção.
O segundo parâmetro repete o bloco em deadlock (1213) ou *lock wait timeout* (1205). Dentro de uma transação já
aberta, apenas participa dela.

```php
$id = $this->transaction(function () use ($dados) {
    $this->insertArray($dados);
    $id = $this->lastInsertId();
    $this->query("INSERT INTO HISTORICO (CODUSUARIO, ACAO) VALUES (?, ?)", [$id, "CRIADO"])->executeQuery();
    return $id;
}, 3);
```

**Vários DAOs na mesma transação:** passe a mesma conexão com `setInstance()`.

```php
$perfilDao = new PerfilDao();
$perfilDao->setInstance($this->getInstance());

$this->transaction(function () use ($perfilDao, $dados) {
    $this->insertArray($dados["usuario"]);
    $perfilDao->insertArray($dados["perfil"]);
});
```

**Forma manual:** `beginTransaction()`, `commitTransaction()` e `rollBackTransaction()` ignoram chamadas repetidas.

```php
try {
    $this->beginTransaction();
    // ...
    $this->commitTransaction();
} catch (\Throwable $e) {        // não use catch (Error): DatabaseException é Exception
    $this->rollBackTransaction();
    throw $e;
}
```

## Grandes volumes

### Inserção em lote: `insertMany()`

Um INSERT com várias linhas por lote, dentro de uma transação (tudo ou nada). Todas as linhas devem ter as mesmas
colunas; `null` é gravado como `NULL`. Respeita o limite de 65.535 placeholders do MySQL.

```php
$total = $this->insertMany([
    ["NOME" => "Ana", "EMAIL" => "ana@x.com"],
    ["NOME" => "Bia", "EMAIL" => "bia@x.com"],
], 1000); // linhas por lote
```

Em MySQL 8.4, 20.000 linhas: **1.565 ms** com `insertArray` em laço contra **119 ms** com `insertMany` (13x).

### Leitura linha a linha: `cursor()`

Percorre o resultado sem carregar tudo em memória, ideal para exportações e relatórios. Usa uma conexão própria
(sem buffer no MySQL), fechada ao final ou quando o laço é interrompido.

```php
foreach ($this->cursor("SELECT * FROM PEDIDO WHERE DATA >= ?", [$inicio]) as $pedido) {
    fputcsv($arquivo, (array) $pedido, ",", "\"", "");
}

foreach ($this->cursor("SELECT * FROM PEDIDO", null, true) as $model) { /* instâncias de classModel */ }
```

Em MySQL 8.4, 300.000 linhas: `fetchArrayObj()` usa **258 MB** (estoura o `memory_limit` padrão de 128 MB);
`cursor()` usa **0,1 MB**. Por ser outra conexão, o cursor não enxerga dados ainda não commitados da transação atual.

### `IN (...)` seguro: `placeholders()`

```php
$sql = "SELECT * FROM USUARIO WHERE CODUSUARIO IN (" . $this->placeholders($ids) . ") AND ATIVO = ?";
$this->executeSQL($sql, [...array_values($ids), 1]);
```

Lista vazia vira `IN (NULL)`, que não retorna linhas (em vez de erro de sintaxe).

## Segurança

- Todos os valores vão como parâmetros (`?` ou `:nome`). **Nunca** concatene valores no SQL; para listas use
  `placeholders()`.
- Nomes de coluna em `insert*`/`update*`/`insertMany` são validados (inclusive chaves de arrays).
- `orderBy`, `addOrderBy`, `limit` e `offset` rejeitam `;`, `--`, `#` e `/* */`. Mesmo assim, valores vindos da
  requisição devem passar por whitelist (colunas) ou `(int)` (limite/página).
- A mensagem de falha de conexão não expõe usuário, host nem banco; o detalhe original fica em `getPrevious()`.
- Veja a nota sobre homologação em Apache na seção [Configuração](#configuração).

## Erros, log e debug

Erros de banco lançam `BMorais\Database\DatabaseException`:

```php
try {
    $this->select("*", "WHERE CODUSUARIO = ?", [$id]);
} catch (DatabaseException $e) {
    $e->getMessage();      // mensagem (inclui a do driver, ex.: código 1062 em chave duplicada)
    $e->getQuery();        // SQL executado
    $e->getParameters();   // parâmetros
    $e->getPrevious();     // PDOException original (errorInfo)
    error_log(json_encode($e->getContextInfo())); // tudo acima, para log — não exiba ao usuário
}
```

- **Debug:** `$debug = true` em `select/insert/update/delete` imprime o SQL **sem executar**; `debug()` no builder
  imprime a consulta em construção e encerra o script. Use só em desenvolvimento.
- **Log:** com `display_errors_details` ativo, `getLogSQL()` devolve a última consulta com os valores no lugar dos
  parâmetros (valores acima de 64 KB aparecem como `[N bytes]`).

## Conexões

- DAOs que apontam para o mesmo banco compartilham uma única conexão PDO.
- Se a conexão cair (MySQL 2006/2013), ela é refeita e o comando repetido apenas quando é seguro: fora de transação e,
  no 2013, só para leituras.
- `closeConnections()` fecha as conexões em cache (útil em workers e processos longos); conexões com transação
  aberta são mantidas.

## Exemplo completo

A pasta [`example`](example) tem um config de referência ([`config.php`](example/config.php)) e um DAO com todas as
formas de uso ([`ExampleDao.php`](example/ExampleDao.php)): CRUD, builder, fetch, transações, grandes volumes,
debug e tratamento de erros.

## Suporte

Se você descobrir algum problema relacionado à segurança, envie um e-mail para contato@bmorais.com em vez de usar o
rastreador de issues.

## Contribuindo

Contribuições são bem-vindas. Faça um fork, crie uma branch (`git checkout -b feature/minha-feature`), faça o commit e
abra um Pull Request. Siga [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) e
[Semantic Versioning](https://semver.org/), e não altere assinaturas de métodos existentes: o pacote é usado por
vários projetos.

## Créditos

- [Bruno Morais](https://github.com/brunobmorais) (Developer)
- [Athus Felipe](https://github.com/AthusFelipe) (Developer)

<a href="https://github.com/brunobmorais/php-database/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=brunobmorais/php-database" />
</a>

## Licença

The MIT License (MIT). Veja o [arquivo de licença](LICENSE.md).

<p align="right">(<a href="#readme-top">voltar ao topo</a>)</p>
