<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Esta trait adiciona suporte a UUID como chave primaria nos modelos.
 * Quando aplicada a um modelo, ela gera automaticamente um UUID (em vez de um ID incremental)
 * sempre que um novo registro e criado.
 *
 * Alem disso, define o tipo da chave como string e desativa o incremento automatico.
 *
 * Uso:
 * Basta adicionar `use HasUuid;` dentro da classe do modelo.
 */

trait HasUuid
{

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
