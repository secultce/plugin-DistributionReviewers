<style>
    edit-box#report-evaluation-technical .mc-arrow {
        border-width: 0px !important;
    }
</style>
<?php

use MapasCulturais\App;
use MapasCulturais\i;
use PhpOffice\PhpWord\Style;

$route = App::i()->createUrl('documentalOrTechnical', 'basicData', ['id' => $entity->id]);

?>
<style>
    p {
        font-size: 13px;
    }
</style>

<!--botão para buscar inscrito -->
<a class="btn btn-default" ng-click="editbox.open('report-evaluation-technical-options', $event)" rel="noopener noreferrer">Distribuir avaliações</a>

<!-- Formulário -->
<edit-box id="report-evaluation-technical-options" position="top" title="<?php i::esc_attr_e('Atenção') ?>" cancel-label="Cancelar" close-on-cancel="true">

    <form class="form-report-evaluation-technical-options">
        <p>Deseja distribuir as avaliações por quantidade igualitária para cada avaliador?</p>
        <button ng-click="editbox.open('report-evaluation-technical', $event)" class="btn btn-primary" type="submit">Distribuir</button>
    </form>
</edit-box>

<edit-box id="report-evaluation-technical" position="center" style="display: grid; " title="<?php i::esc_attr_e('Atenção!') ?>" cancel-label="Cancelar" close-on-cancel="true">

    <form class="form" action="<?= $route ?>" method="GET">
        <p>Ao confirmar a distribuição as avaliações serão distribuidas de maneira irreversível.</p>
        <button class="btn btn-primary" type="submit">Confirmar a distribuição.</button>
    </form>
</edit-box>