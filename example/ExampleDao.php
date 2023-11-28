<?php

use BMorais\Database\Crud;
use BMorais\Database\CrudBuilder;

class ExampleDao extends CrudBuilder {


    public function __construct()
    {
        $this->setTableName("EXAMPLE");
        $this->setClassModel("exampleModel");
    }

    public function findName(): array {
        $this->selectBuilder()
            ->where("NOME=?",["Joao"])
            ->orderBy("NOME")
            ->executeQuery()
            ->fetchArrayAssoc();
    }
}