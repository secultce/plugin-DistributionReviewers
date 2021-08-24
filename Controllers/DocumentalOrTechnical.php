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

        $queryEvaluators = $app->em->getConnection()->fetchAll("
            select distinct
                re.user_id as id_usuario_avaliador,
                ag.id as id_agent_avaliador,
                ag.name as nome_avaliador
                
            from 
                public.registration as r
                    left join public.opportunity as op
                        on op.id = r.opportunity_id
                    left join public.registration_evaluation as re
                        on re.registration_id = r.id
                            left join  public.agent as ag
                                on ag.user_id = re.user_id	
            where
                op.parent_id = {$opportunity->id}");

        if(!$queryEvaluators) {
            $this->errorJson("Edital sem avaliadores!");
        }

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
                op.parent_id = {$opportunity->id}");

        if(!$registrations) {
            $this->errorJson("Edital sem inscrições!");
        }

        $evaluators = count($queryEvaluators);
        $data = [];

        $quantityPerAppraiser = intval(count($registrations) / $evaluators);

        // Separa em arrays as inscrições pela quantidade de avaliadores
        function separate($registrations, $quantityPerAppraiser, $evaluators) {
            $result = [[]];
            $group = 0;

            for ($i = 0; $i < count($registrations); $i++) {
                if(!isset($result[$group])) {
                    $result[$group] = array();
                }
                array_push($result[$group], $registrations[$i]);

                if (($i + 1) % $quantityPerAppraiser === 0 && count($result) != $evaluators) {
                    $group = $group + 1;
                }
            }

            return $result;
        }

        $registrationsSeparate = separate($registrations, $quantityPerAppraiser, $evaluators);
        dd($registrationsSeparate);

        //Insere no banco as inscrições para cada avaliador...
        $query_insert = "
            insert into public.pcache 
            (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID, USER_ID, '@control', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'create', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'view', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'modify', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
            (SEQUENCE_ID,  USER_ID, 'viewPrivateFiles', 'TIMESTAMP_DO_DIA',     'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
            (SEQUENCE_ID,  USER_ID, 'viewPrivateData', 'TIMESTAMP_DO_DIA',    'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'createAgentRelation', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'createAgentRelationWithControl', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID, 82838, 'removeAgentRelation', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'removeAgentRelationWithControl', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID,  USER_ID, 'createSealRelation', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)

            insert into public.pcache 
                (id, user_id, action, create_timestamp, object_type, object_id)
            values
                (SEQUENCE_ID, USER_ID, 'removeSealRelation', 'TIMESTAMP_DO_DIA', 'MapasCulturais\Entities\Agent', AGENT_ID)
        ";

        $stmt_insert = $app->em->getConnection()->prepare($query_insert);
        $stmt_insert->execute();
    }
}

?>