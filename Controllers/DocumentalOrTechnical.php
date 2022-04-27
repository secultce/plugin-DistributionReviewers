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
        $url = "https://mapacultural.secult.ce.gov.br/oportunidade/{$opportunityId}";
        $url_dev = "http://localhost:8080/oportunidade/{$opportunityId}#/tab=inscritos";

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
                op.id = {$opportunity->id}
                and r.status in (1,10)");


        if (!$registrations) {
            //$this->errorJson("Edital sem inscrições!");
            echo "<script>
            alert('Distribuição feita!');
            window.location.href = '{$url_dev}';
            </script>";
        }

        //Pegando avaliadores do edital
        $queryEvaluators = $opportunity->getEvaluationCommittee(false);
        $contEvaluators = count($queryEvaluators);

        // Verifica ultimo id da permission_cache_pending 
        $selectLastIdPcachePending  = $app->em->getConnection()->fetchAll("
            select id from permission_cache_pending 
            order by id desc
            limit 1
        ");
        $lastIdPcachePending = $selectLastIdPcachePending[0]['id'];
        $sequenciIdPcachePending = ++$lastIdPcachePending;

        // Verifica ultimo id da pcache
        $selectLastIdPcache = $app->em->getConnection()->fetchAll("
            select id from pcache 
            order by id desc
            limit 1
        ");
        $lastIdPcache = $selectLastIdPcache[0]['id'];
        $sequenciIdPcache = ++$lastIdPcache;
        $create_timestamp = date("Y-m-d H:i:s", time());

        // Verifica permissoes de avaliadores na pcache
        foreach ($queryEvaluators as $evaluator) {
            $user_id = $evaluator->user->id;
            $agent_id = $evaluator->id;

            // Deleta antigas permissoes do avaliador
            $query_delete = "
                DELETE FROM pcache
                where object_id = $opportunityId
                and user_id = $user_id
            ";
            $stmt_delete = $app->em->getConnection()->prepare($query_delete);
            $stmt_delete->execute();

            // Adiciona permissoes corretas
            $query_sendUserEvaluations = "
                insert into pcache (id, user_id, action, create_timestamp, object_type, object_id) values($sequenciIdPcache, $user_id, 'sendUserEvaluations', '$create_timestamp', 'MapasCulturais\Entities\Opportunity', $opportunityId);
            ";
            $stmt_insert_query_sendUserEvaluations = $app->em->getConnection()->prepare($query_sendUserEvaluations);
            $stmt_insert_query_sendUserEvaluations->execute();
            ++$sequenciIdPcache;

            $query_viewPrivateFiles = "
                insert into pcache (id, user_id, action, create_timestamp, object_type, object_id) values($sequenciIdPcache, $user_id, 'viewPrivateFiles', '$create_timestamp', 'MapasCulturais\Entities\Opportunity', $opportunityId);
            ";
            $stmt_insert_query_viewPrivateFiles = $app->em->getConnection()->prepare($query_viewPrivateFiles);
            $stmt_insert_query_viewPrivateFiles->execute();
            ++$sequenciIdPcache;

            $query_view = "
                insert into pcache (id, user_id, action, create_timestamp, object_type, object_id) values($sequenciIdPcache, $user_id, 'view', '$create_timestamp', 'MapasCulturais\Entities\Opportunity', $opportunityId);
            ";
            $stmt_insert_query_view = $app->em->getConnection()->prepare($query_view);
            $stmt_insert_query_view->execute();
            ++$sequenciIdPcache;

            $query_evaluateRegistrations = "
                insert into pcache (id, user_id, action, create_timestamp, object_type, object_id) values($sequenciIdPcache, $user_id, 'evaluateRegistrations', '$create_timestamp', 'MapasCulturais\Entities\Opportunity', $opportunityId);
            ";
            $stmt_insert_query_evaluateRegistrations = $app->em->getConnection()->prepare($query_evaluateRegistrations);
            $stmt_insert_query_evaluateRegistrations->execute();
            ++$sequenciIdPcache;

            $query_pendingAgent = "
                insert into permission_cache_pending (id, object_id, object_type, status) values($sequenciIdPcachePending, $agent_id, 'MapasCulturais\Entities\Agent', '1');
            ";
            $stmt_insert_query_pendingAgent = $app->em->getConnection()->prepare($query_pendingAgent);
            $stmt_insert_query_pendingAgent->execute();
            ++$sequenciIdPcachePending;

            $query_pendingOpportunity = "
                insert into permission_cache_pending (id, object_id, object_type, status) values($sequenciIdPcachePending, $agent_id, 'MapasCulturais\Entities\Opportunity', '1');
            ";
            $stmt_insert_query_pendingOpportunity = $app->em->getConnection()->prepare($query_pendingOpportunity);
            $stmt_insert_query_pendingOpportunity->execute();
            ++$sequenciIdPcachePending;
        }
        $quantityPerAppraiser = intval(count($registrations) / $contEvaluators);

        // Separa em arrays as inscrições pela quantidade de avaliadores
        function separate($registrations, $quantityPerAppraiser, $contEvaluators)
        {
            $result = [[]];
            $group = 0;

            for ($i = 0; $i < count($registrations); $i++) {
                if (!isset($result[$group])) {
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

        // Separando id de avaliadores
        $evaluators  = [];
        foreach ($queryEvaluators as $key => $valor) {
            array_push($evaluators, $valor->user->id);
        }

        $previous = 0;
        $include = [];

        //Variaveis de debug...
        $cacheExcludes = [];

        foreach ($evaluators as $key => $valor) {
            $previous = $key;
            $include[$key] = $valor;
            $evaluators[$previous] = $include[$previous];
            $includes = $include;
            $excludes = $evaluators;
            unset($excludes[$key]);

            foreach ($registrationsSeparate[$key] as $groupRegistrations) {
                foreach ($groupRegistrations as $registrations) {
                    $strInclude = implode($includes, ',');
                    $e2 = `'` . implode("','", $excludes) . `'`;
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

                    $jsonIncludes = '{"include":["' . $strInclude . '"],"exclude":["' . $strExcludes . '"]}';

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

        echo "<script>
            alert('Distribuição Concluída!');
            window.location.href = '{$url_dev}';
            </script>";
    }
}
