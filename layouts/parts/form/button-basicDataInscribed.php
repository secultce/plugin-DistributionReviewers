<?php
use MapasCulturais\App;
use MapasCulturais\i;

$route = App::i()->createUrl('documentalOrTechnical', 'basicData', ['id'=>$entity->id]);

?>

<!--botão para buscar inscrito -->
<a class="btn btn-default" ng-click="editbox.open('report-evaluation-technical-options', $event)"
    rel="noopener noreferrer">Distribuir avaliações</a>

<!-- Formulário -->
<edit-box id="report-evaluation-technical-options" position="top"
    title="<?php i::esc_attr_e('Atenção')?>"
    cancel-label="Cancelar" close-on-cancel="true">
    
    <form class="form-report-evaluation-technical-options"
        action="<?=$route?>" method="GET">
        <p>Distribuir avaliações por quantidade de avaliadores.</p>
        <button class="btn btn-primary" type="submit">Distribuir...</button>
    </form>
</edit-box>
