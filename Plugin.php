<?php
namespace DistributionReviewers;

use MapasCulturais\App;
use MapasCulturais\Entities;
use MapasCulturais\i;

class Plugin extends \MapasCulturais\Plugin {

    public function __construct(array $config = []) 
    {
        parent::__construct($config);
    }

    public function _init() 
    {
        $app = App::i();

        $app->hook('template(opportunity.single.header-inscritos):end', function () use ($app) {
            $opportunity = $this->controller->requestedEntity;
            $type_evaluation = $opportunity->evaluationMethodConfiguration->getDefinition()->slug;
            if ($type_evaluation == 'documentary' || $type_evaluation == 'technical') {
                $opportunity = $this->controller->requestedEntity;
                $this->part('form/button-basicDataInscribed', ['entity' => $opportunity]);
            }
        });
    }

    public function register() 
    {
        $app = App::i();
        $app->registerController('documentalOrTechnical', 'DistributionReviewers\Controllers\DocumentalOrTechnical');
    }

}

?>