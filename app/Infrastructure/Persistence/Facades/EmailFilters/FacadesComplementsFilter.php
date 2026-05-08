<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;

class FacadesComplementsFilter implements IFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Value must be an array of objects with complements');
        }

        // Não faz JOIN aqui pois o repository já faz leftJoin com 'ec'
        // Array de objetos: cada objeto é uma condição OR, dentro do objeto os campos são AND
        // Exemplo: [{status:1, copia:1}, {status:0, copia:2}]
        // SQL: ((status=1 AND copia=1) OR (status=0 AND copia=2))
        $query->where(function ($orQuery) use ($value) {
            $first = true;
            foreach ($value as $complementCondition) {
                // Converte objeto para array para iterar
                $conditionArray = is_object($complementCondition) 
                    ? (array) $complementCondition 
                    : $complementCondition;
                
                // Cada objeto do array é uma condição OR
                // Usa where para o primeiro e orWhere para os demais
                $callback = function ($andQuery) use ($conditionArray) {
                    // Garante que ec.complement_data não seja NULL apenas uma vez por condição OR
                    $andQuery->whereNotNull('ec.complement_data');
                    
                    // Dentro de cada objeto, os campos são combinados com AND
                    foreach ($conditionArray as $key => $val) {
                        if (is_array($val)) {
                            // Se o valor é um array, cria condições OR para cada valor
                            $andQuery->where(function ($subOrQuery) use ($key, $val) {
                                $firstSub = true;
                                foreach ($val as $v) {
                                    $jsonEncoded = json_encode([$key => $v]);
                                    
                                    if ($firstSub) {
                                        $subOrQuery->whereRaw("ec.complement_data @> ?", [$jsonEncoded]);
                                        $firstSub = false;
                                    } else {
                                        $subOrQuery->orWhereRaw("ec.complement_data @> ?", [$jsonEncoded]);
                                    }
                                }
                            });
                        } else {
                            // Para valores numéricos, tenta tanto o tipo numérico quanto string
                            if (is_numeric($val)) {
                                $jsonNumber = json_encode([$key => $val]);
                                $jsonString = json_encode([$key => (string)$val]);
                                
                                $andQuery->where(function ($numQuery) use ($jsonNumber, $jsonString) {
                                    $numQuery->whereRaw("ec.complement_data @> ?", [$jsonNumber])
                                             ->orWhereRaw("ec.complement_data @> ?", [$jsonString]);
                                });
                            } else {
                                $jsonEncoded = json_encode([$key => $val]);
                                $andQuery->whereRaw("ec.complement_data @> ?", [$jsonEncoded]);
                            }
                        }
                    }
                };
                
                if ($first) {
                    $orQuery->where($callback);
                    $first = false;
                } else {
                    $orQuery->orWhere($callback);
                }
            }
        });

        return $query;
    }
}
