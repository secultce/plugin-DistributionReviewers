<?php

namespace DistributionReviewers\Controllers;

use MapasCulturais\App;
use MapasCulturais\Controller;
use MapasCulturais\i;

class DocumentalOrTechnical extends Controller
{
    public function ALL_basicData() 
    {
        $app = App::i();

        $opportunityId = (int) $this->data['id'];
        $opportunity =  $app->repo("Opportunity")->find($opportunityId);

        // Pegando inscrições do edital
        $registrations = $app->em->getConnection()->fetchAll("
            select 
                r.id as inscricoes_para_avaliacao
            from 
                public.registration as r
                    left join public.opportunity as op
                        on op.id = r.opportunity_id
                    left join public.registration_evaluation as re
                        on re.registration_id = r.id
            where
                op.id = {$opportunity->id}");

        if(!$registrations) {
            $this->errorJson("Edital sem inscrições!");
        }

        // var_dump('registrations');
        // dd($registrations);

        //Pegando avaliadores do edital
        $queryEvaluators = $opportunity->getEvaluationCommittee(false);
        $contEvaluators = count($queryEvaluators);
        $data = [];
        // dd($queryEvaluators);

        $quantityPerAppraiser = intval(count($registrations) / $contEvaluators);

        // Separa em arrays as inscrições pela quantidade de avaliadores
        function separate($registrations, $quantityPerAppraiser, $contEvaluators) {
            $result = [[]];
            $group = 0;

            for($i = 0; $i < count($registrations); $i++) {
                if(!isset($result[$group])) {
                    $result[$group] = array();
                }
                array_push($result[$group], $registrations[$i]);

                if (($i + 1) % $quantityPerAppraiser === 0 && count($result) != $contEvaluators) {
                    $group = $group + 1;
                }
            }

            return $result;
        }

        $registrationsSeparate = separate($registrations, $quantityPerAppraiser, $contEvaluators);

        dd($queryEvaluators);
        // Separando id de avaliadores
        $evaluators  = [];
        foreach($queryEvaluators as $key => $valor) {
            array_push($evaluators, $valor->user->id);
        }

        $previous = 0;
        $include = [];

        //Variaveis de debug...
        $cacheGrupoDeInscricoes = [];
        $cacheIncludes = [];
        $cacheExcludes = [];

        $cacheKey1 = [];
        $cacheKey2 = [];


        foreach($evaluators as $key => $valor) {
            $previous = $key;

            $include[$key] = $valor;
            $evaluators[$previous] = $include[$previous];

            $includes = $include;
            unset($evaluators[$key]);
            $excludes = $evaluators;

            //cache
            array_push($cacheKey1, $key);
            array_push($cacheIncludes, $includes);

            foreach($registrationsSeparate[$key] as $groupRegistrations) {
                foreach($groupRegistrations as $registrations) {
                    $strInclude = implode($includes, ',');
                    $e2 = `'`.implode("','", $excludes).`'`;
                    $strExcludes = preg_replace('/(?<!^)\'(?!$)/', '"', $e2);

                    //cache
                    // array_push($cacheKey2, $previous);
                    array_push($cacheExcludes, $strExcludes);

                    // $query_update = `
                    //     UPDATE registration 
                    //     SET valuers_exceptions_list = '{"include":[{"$strInclude"}],"exclude":["$strExcludes"]}'
                    //     WHERE opportunity_id = {$opportunityId}
                    //     AND id = {$registrations}
                    // `;

                    // $valueExceptions = '{"include":[{"$strInclude"}],"exclude":["$strExcludes"]}';
                    // var_dump('query_update');
                    // dd($query_update);

                    $jsonIncludes = '{"include":["'.$strInclude.'"],"exclude":["'.$strExcludes.'"]}';

                    $query_update = "
                        UPDATE
                            public.registration
                        SET
                            valuers_exceptions_list = '$jsonIncludes'
                        WHERE
                            opportunity_id = $opportunityId
                            AND id = $registrations;
                    ";

                    // $query_update = `
                    //     UPDATE
                    //         public.registration
                    //     SET
                    //         valuers_exceptions_list = '{"include":[{"$strInclude"}],"exclude":["$strExcludes"]}'
                    //     WHERE
                    //         opportunity_id = $opportunityId
                    //         AND id $registrations;
                    // `;

                    // var_dump('query_update');
                    // dd($query_update);

                    $stmt_update = $app->em->getConnection()->prepare($query_update);
                    $stmt_update->execute();

                    // UPDATE registration 
                    // SET valuers_exceptions_list = '{"include":["21583"],"exclude":["86265","38568","83932","16529","35229","28167"]}'
                    // WHERE opportunity_id = 3313
                    // AND id = 53462173
                }
            }

            unset($include[$previous]);
            
        }

        // var_dump($cacheKey1);
        // var_dump($cacheIncludes);
        // var_dump($cacheExcludes);
        

        echo "Distribuição feita";
    }
}

?>