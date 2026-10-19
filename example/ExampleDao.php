<?php

use BMorais\Database\CrudBuilder;
use BMorais\Database\DatabaseException;

/**
 * Exemplo de DAO com as opções de uso da biblioteca.
 *
 * Pré-requisitos:
 * - A constante CONFIG_DATA_LAYER definida (veja example/config.php).
 * - O model em CONFIG_DATA_LAYER["directory_models"] . "ExampleModel", por exemplo:
 *
 *   namespace App\Models;
 *
 *   class ExampleModel extends \BMorais\Database\ModelAbstract
 *   {
 *       protected ?int $ID = null;
 *       protected ?string $NOME = null;
 *       protected ?string $EMAIL = null;
 *       protected ?int $ATIVO = null;
 *   }
 *
 * Os métodos do Crud (select, insert, update, delete...) são públicos.
 * Os métodos do builder e de execução (selectBuilder, where, executeSQL, fetch*,
 * beginTransaction...) são protegidos, então devem ser chamados dentro do DAO.
 *
 * Regra geral: valores sempre como parâmetro (? ou :nome), nunca concatenados no SQL.
 */
class ExampleDao extends CrudBuilder
{
    public function __construct()
    {
        // Tabela e alias opcional (o alias é usado pelos selects: "FROM EXAMPLE AS E")
        $this->setTableName("EXAMPLE", "E");

        // Model usado por fetchArrayClass/fetchOneClass e por select(..., returnModel: true)
        $this->setClassModel("ExampleModel");

        // Opcional: outro banco além de CONFIG_DATA_LAYER["dbname"].
        // Em homologação, o sufixo (ex.: "Homologacao") é adicionado automaticamente.
        // $this->setDatabase("outro_banco");
    }

    /* =====================================================================
     * CRUD SIMPLES (métodos públicos do Crud)
     * ===================================================================== */

    /**
     * select(campos, complemento SQL, valores, retornarModel, debug)
     * @return array lista de stdClass
     */
    public function listarAtivos(): array
    {
        return $this->select("ID, NOME, EMAIL", "WHERE ATIVO = ? ORDER BY NOME", [1]);
    }

    /**
     * Com returnModel = true, cada linha vira uma instância de ExampleModel.
     * @return array
     */
    public function buscarPorIdComoModel(int $id): array
    {
        return $this->select("*", "WHERE ID = ?", [$id], true);
    }

    /**
     * O complemento do select também pode trazer JOINs. Sem alias em setTableName,
     * o alias pode vir no início do complemento: select("A.*", "A WHERE A.ID = ?", [$id]).
     */
    public function listarComPessoas(int $id): array
    {
        return $this->select(
            "E.NOME, P.TELEFONE",
            "LEFT JOIN PESSOA P ON P.EXAMPLE_ID = E.ID WHERE E.ID = ? ORDER BY P.NOME",
            [$id]
        );
    }

    /**
     * insert(colunas, valores). Retorna true; em caso de erro lança DatabaseException.
     */
    public function inserir(string $nome, string $email): ?string
    {
        $this->insert("NOME, EMAIL, ATIVO", [$nome, $email, 1]);
        return $this->lastInsertId();
    }

    /**
     * insertArray: as chaves são as colunas. Valores null são ignorados.
     * Não repasse $_POST direto: monte o array só com as colunas permitidas.
     */
    public function inserirArray(array $dados): bool
    {
        return $this->insertArray([
            "NOME"  => $dados["nome"] ?? null,
            "EMAIL" => $dados["email"] ?? null,
            "ATIVO" => 1,
        ]);
    }

    /**
     * insertObject: usa as propriedades públicas do objeto (as null são ignoradas).
     */
    public function inserirObjeto(string $nome, string $email): bool
    {
        $obj = new stdClass();
        $obj->NOME = $nome;
        $obj->EMAIL = $email;
        return $this->insertObject($obj);
    }

    /**
     * update(colunas, valores das colunas + valores do where, where)
     */
    public function atualizarEmail(int $id, string $email): bool
    {
        return $this->update("EMAIL", [$email, $id], "ID = ?");
    }

    /**
     * updateArray(dados, where, valores do where). Valores null são ignorados.
     * Também existe updateObject(objeto, where, valores do where).
     */
    public function atualizarArray(int $id, array $dados): bool
    {
        return $this->updateArray(["NOME" => $dados["nome"] ?? null, "EMAIL" => $dados["email"] ?? null], "ID = ?", [$id]);
    }

    /**
     * insertModel/updateModel leem também as propriedades protected/private do model
     * (para ModelAbstract, o mesmo que insertArray($model->toArray())).
     * updateModel grava NULL nas colunas do último parâmetro; as demais com null são ignoradas.
     */
    public function salvarModel(object $model, ?int $id = null): bool
    {
        if ($id === null) {
            return $this->insertModel($model);
        }
        return $this->updateModel($model, "ID = ?", [$id], ["EMAIL"]);
    }

    /**
     * delete(valores, where). Sem where, apaga a tabela inteira (e registra um aviso no log):
     * sempre informe o where.
     */
    public function remover(int $id): bool
    {
        return $this->delete([$id], "ID = ?");
    }

    /* =====================================================================
     * QUERY BUILDER
     * ===================================================================== */

    public function findName(): array
    {
        return $this->selectBuilder()
            ->where("NOME=?", ["Joao"])
            ->orderBy("NOME")
            ->executeQuery()
            ->fetchArrayAssoc();
    }

    /**
     * Builder completo. A ordem das chamadas é livre; o SQL é montado na ordem correta
     * e os parâmetros acompanham cada trecho.
     */
    public function pesquisar(string $nome, int $pagina = 1, int $porPagina = 20): array
    {
        return $this->selectBuilder("E.ID, E.NOME, P.TELEFONE")
            ->leftJoin("PESSOA", "P", "P.EXAMPLE_ID = E.ID")
            ->where("E.ATIVO = ?", [1])
            ->andWhere("E.NOME LIKE ?", ["%{$nome}%"])
            ->orWhere("E.EMAIL LIKE ?", ["%{$nome}%"])
            ->orderBy("E.NOME")
            ->addOrderBy("E.ID", "DESC")
            ->limit((string) $porPagina)
            ->offset((string) (($pagina - 1) * $porPagina))
            ->executeQuery()
            ->fetchArrayObj();
    }

    /**
     * Joins disponíveis: innerJoin, leftJoin e rightJoin (tabela, alias, condição).
     * Agrupamento: groupBy, having, andHaving, orHaving.
     */
    public function totalPorPessoa(): array
    {
        return $this->selectBuilder("P.NOME, COUNT(*) AS TOTAL")
            ->innerJoin("PESSOA", "P", "P.EXAMPLE_ID = E.ID")
            ->groupBy("P.NOME")
            ->having("COUNT(*) > 1")
            ->orderBy("TOTAL", "DESC")
            ->executeQuery()
            ->fetchArrayAssoc();
    }

    /**
     * Ordenação vinda do usuário: use uma whitelist, nunca o valor recebido direto.
     */
    public function listarOrdenado(string $coluna, string $direcao): array
    {
        $colunas = ["nome" => "E.NOME", "email" => "E.EMAIL", "id" => "E.ID"];
        $coluna = $colunas[$coluna] ?? "E.NOME";
        $direcao = strtoupper($direcao) === "DESC" ? "DESC" : "ASC";

        return $this->selectBuilder()
            ->orderBy($coluna, $direcao)
            ->executeQuery()
            ->fetchArrayObj();
    }

    /**
     * Parâmetros nomeados também funcionam (não misture com "?" na mesma consulta).
     */
    public function buscarPorEmail(string $email): ?stdClass
    {
        return $this->selectBuilder()
            ->where("E.EMAIL = :email", ["email" => $email])
            ->limit("1")
            ->executeQuery()
            ->fetchOneObj();
    }

    /**
     * insertBuilder / updateBuilder / deleteBuilder + rowCount()
     */
    public function exemplosEscritaBuilder(int $id, string $nome): int
    {
        $this->insertBuilder("NOME, ATIVO", [$nome, 1])->executeQuery();

        // Os parâmetros do SET vêm antes dos do WHERE, como no SQL
        $this->updateBuilder("NOME", [$nome])
            ->where("ID = ?", [$id])
            ->executeQuery();

        $this->deleteBuilder()
            ->where("ID = ?", [$id])
            ->executeQuery();

        return $this->rowCount(); // linhas afetadas pelo último comando
    }

    /**
     * query(): SQL livre como trecho principal (UNION, subconsultas, expressões...)
     * Também é possível continuar o encadeamento com where, orderBy, limit etc.
     */
    public function consultaLivre(int $id): array
    {
        return $this->query("SELECT ID, NOME FROM EXAMPLE WHERE ID = ? UNION SELECT ID, NOME FROM EXAMPLE_ARQUIVO WHERE ID = ?", [$id, $id])
            ->executeQuery()
            ->fetchArrayAssoc();
    }

    /* =====================================================================
     * EXECUÇÃO DIRETA + FORMAS DE LEITURA
     * ===================================================================== */

    public function findPeople($name): array
    {
        $sql = "SELECT * FROM PESSOA WHERE NOME=?";
        $this->executeSQL($sql, [$name]);
        return $this->fetchArrayObj();
    }

    /**
     * Formas de leitura do resultado da última consulta:
     * - fetchArrayObj()   → lista de stdClass
     * - fetchArrayAssoc() → lista de arrays associativos
     * - fetchArrayClass() → lista de models (classModel ou classe informada)
     * - fetchOneObj() / fetchOneAssoc() / fetchOneClass() → um registro ou null
     */
    public function exemplosFetch(int $id): array
    {
        $this->executeSQL("SELECT * FROM EXAMPLE WHERE ID = ?", [$id]);
        $obj = $this->fetchOneObj();

        $this->executeSQL("SELECT * FROM EXAMPLE WHERE ID = ?", [$id]);
        $assoc = $this->fetchOneAssoc();

        $this->executeSQL("SELECT * FROM EXAMPLE WHERE ID = ?", [$id]);
        $model = $this->fetchOneClass();               // usa setClassModel("ExampleModel")

        $this->executeSQL("SELECT * FROM EXAMPLE");
        $models = $this->fetchArrayClass(null, "ExampleModel"); // ou informe a classe

        // O PDOStatement retornado por executeSQL também pode ser passado aos fetch*
        $stmt = $this->executeSQL("SELECT * FROM EXAMPLE WHERE ATIVO = ?", [1]);
        $lista = $this->fetchArrayAssoc($stmt);

        return compact("obj", "assoc", "model", "models", "lista");
    }

    /* =====================================================================
     * TRANSAÇÃO
     * ===================================================================== */

    /**
     * Forma recomendada: transaction() faz o commit ao final e o rollback em QUALQUER erro
     * (a exceção é relançada). O segundo parâmetro repete o bloco em deadlock ou
     * lock wait timeout. Dentro de uma transação já aberta, apenas participa dela.
     */
    public function inserirComHistoricoTransaction(string $nome, string $email): string
    {
        return $this->transaction(function () use ($nome, $email) {
            $this->insert("NOME, EMAIL, ATIVO", [$nome, $email, 1]);
            $id = $this->lastInsertId();

            $this->query("INSERT INTO EXAMPLE_HISTORICO (EXAMPLE_ID, ACAO) VALUES (?, ?)", [$id, "CRIADO"])
                ->executeQuery();

            return $id;
        }, 3);
    }

    /**
     * Forma manual. begin/commit/rollBack ignoram chamadas repetidas (begin com transação
     * aberta não abre outra). DAOs da mesma base compartilham a conexão, então operações de
     * DAOs diferentes entre o begin e o commit fazem parte da mesma transação.
     *
     * Capture \Throwable (ou Exception): DatabaseException NÃO é Error, então
     * catch (Error $e) não faz o rollback quando uma consulta falha.
     */
    public function inserirComHistorico(string $nome, string $email): string
    {
        try {
            $this->beginTransaction();

            $this->insert("NOME, EMAIL, ATIVO", [$nome, $email, 1]);
            $id = $this->lastInsertId();

            $this->query("INSERT INTO EXAMPLE_HISTORICO (EXAMPLE_ID, ACAO) VALUES (?, ?)", [$id, "CRIADO"])
                ->executeQuery();

            $this->commitTransaction();
            return $id;
        } catch (\Throwable $e) {
            $this->rollBackTransaction();
            throw $e;
        }
    }

    /**
     * Transação envolvendo vários DAOs: passe a mesma conexão com setInstance()
     * antes do beginTransaction, para que todos participem da transação.
     */
    public function inserirComPessoa(string $nome, string $email, string $telefone): string
    {
        $pessoaDao = new PessoaDao();
        $pessoaDao->setInstance($this->getInstance());

        try {
            $this->beginTransaction();

            $this->insertArray(["NOME" => $nome, "EMAIL" => $email, "ATIVO" => 1]);
            $id = $this->lastInsertId();

            $pessoaDao->insertArray(["NOME" => $nome, "TELEFONE" => $telefone, "EXAMPLE_ID" => $id]);

            $this->commitTransaction();
            return $id;
        } catch (\Throwable $e) {
            $this->rollBackTransaction();
            throw $e;
        }
    }

    /* =====================================================================
     * GRANDES VOLUMES
     * ===================================================================== */

    /**
     * insertMany: um INSERT com várias linhas por lote (bem mais rápido que insertArray em laço).
     * Todas as linhas com as mesmas colunas; roda numa transação (tudo ou nada).
     * Retorna o total de linhas inseridas.
     */
    public function importar(array $registros): int
    {
        $linhas = [];
        foreach ($registros as $registro) {
            $linhas[] = [
                "NOME"  => $registro["nome"],
                "EMAIL" => $registro["email"],
                "ATIVO" => 1,
            ];
        }
        return $this->insertMany($linhas, 1000);
    }

    /**
     * placeholders: monta o IN (...) com "?" em vez de concatenar os valores.
     * Lista vazia vira IN (NULL), que não retorna linhas.
     */
    public function buscarPorIds(array $ids, int $ativo = 1): array
    {
        $sql = "SELECT * FROM EXAMPLE WHERE ID IN (" . $this->placeholders($ids) . ") AND ATIVO = ?";
        $this->executeSQL($sql, [...array_values($ids), $ativo]);
        return $this->fetchArrayObj();
    }

    /**
     * cursor: percorre o resultado linha a linha, sem carregar tudo em memória
     * (ex.: 300 mil linhas com 0,1 MB em vez de ~250 MB com fetchArrayObj).
     * Usa uma conexão própria: não enxerga dados ainda não commitados da transação atual.
     */
    public function exportarCsv(string $arquivo): int
    {
        $saida = fopen($arquivo, "w");
        fputcsv($saida, ["ID", "NOME", "EMAIL"], ",", "\"", "");

        $total = 0;
        foreach ($this->cursor("SELECT ID, NOME, EMAIL FROM EXAMPLE WHERE ATIVO = ? ORDER BY ID", [1]) as $linha) {
            fputcsv($saida, [$linha->ID, $linha->NOME, $linha->EMAIL], ",", "\"", "");
            $total++;
        }

        fclose($saida);
        return $total;
    }

    /**
     * cursor com returnModel = true: cada linha vem como instância de classModel.
     */
    public function processarModels(callable $processar): void
    {
        foreach ($this->cursor("SELECT * FROM EXAMPLE", null, true) as $model) {
            $processar($model);
        }
    }

    /* =====================================================================
     * DEBUG, LOG E ERROS
     * ===================================================================== */

    /**
     * - $debug = true em select/insert/update/delete imprime o SQL e NÃO executa.
     * - debug() no builder imprime o SQL em construção e encerra o script (exit).
     *   Use só em desenvolvimento.
     * - getLogSQL() devolve o último SQL com os valores no lugar dos parâmetros
     *   (requer CONFIG_DATA_LAYER["display_errors_details"] = true).
     */
    public function exemplosDebug(int $id): string
    {
        $this->select("*", "WHERE ID = ?", [$id], false, true); // só imprime

        // $this->selectBuilder()->where("ID = ?", [$id])->debug(); // imprime e encerra

        $this->select("*", "WHERE ID = ?", [$id]);
        return $this->getLogSQL(); // ex.: SELECT * FROM EXAMPLE AS E WHERE ID = 10
    }

    /**
     * Erros de banco lançam DatabaseException com a query e os parâmetros.
     * getContextInfo() traz os detalhes: grave em log, não mostre ao usuário.
     */
    public function exemploErro(): ?array
    {
        try {
            $this->select("COLUNA_INEXISTENTE");
            return null;
        } catch (DatabaseException $e) {
            error_log(json_encode($e->getContextInfo()));
            return [
                "mensagem" => $e->getMessage(),
                "query"    => $e->getQuery(),
                "params"   => $e->getParameters(),
            ];
        }
    }

    /**
     * Em workers/processos longos: fecha as conexões em cache
     * (conexões com transação aberta são mantidas).
     */
    public static function liberarConexoes(): void
    {
        self::closeConnections();
    }
}

/**
 * DAO auxiliar usado no exemplo de transação com vários DAOs.
 */
class PessoaDao extends \BMorais\Database\Crud
{
    public function __construct()
    {
        $this->setTableName("PESSOA");
    }
}
