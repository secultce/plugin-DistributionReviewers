<?php

namespace SeparateAssessments\Controllers;

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

        function separate($registrations, $quantityPerAppraiser, $evaluators) {
            $result = [[]];
            $group = 0;

            // Separa em arrays as inscrições pela quantidade de avaliadores
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
    }
}

?>