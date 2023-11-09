<?php
namespace App\Daos;

use BMorais\Database\Crud;
use App\Libs\FuncoesLib;
use App\Models\RecuperaSenhaModel;

class ExampleDao extends Crud{


    public function __construct()
    {
        $this->setTable("EXAMPLE");
        $this->setClassModel("examplexModel");
    }
}