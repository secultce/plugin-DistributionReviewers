<style>
   edit-box#report-evaluation-technical .mc-arrow{border-width: 0px !important;}
</style>
<?php
use MapasCulturais\App;
use MapasCulturais\i;
use PhpOffice\PhpWord\Style;

$route = App::i()->createUrl('documentalOrTechnical', 'basicData', ['id'=>$entity->id]);

?>

<!--botão para buscar inscrito -->
<a class="btn btn-default" ng-click="editbox.open('report-evaluation-technical-options', $event)"
    rel="noopener noreferrer">Distribuir avaliações</a>

<!-- Formulário -->
<edit-box id="report-evaluation-technical-options" position="top"
   title="<?php i::esc_attr_e('Atenção')?>"
    cancel-label="Cancelar" close-on-cancel="true">
    
    <form class="form-report-evaluation-technical-options">
        <p>Distribuir avaliações por quantidade igualitária de avaliadores.</p>
        <button ng-click="editbox.open('report-evaluation-technical', $event)" class="btn btn-primary" type="submit">Distribuir</button>
    </form>
</edit-box>

<edit-box id="report-evaluation-technical" position="center" style="display: grid; "
   title="<?php i::esc_attr_e('Atenção')?>"
    cancel-label="Cancelar" close-on-cancel="true">
    
    <form class="form"action="<?=$route?>" method="GET">
        <p>Tem certeza que deseja distribuir avaliações por quantidade igualitária de avaliadores ?</p>
        <button  class="btn btn-primary" type="submit">Sim, tenho certeza.</button>
    </form>
</edit-box>
