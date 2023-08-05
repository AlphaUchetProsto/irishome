<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = "Ср. время ответа";

$this->params['breadcrumbs'][] = $this->title;

?>

<div class="grey-wrapper">
    <?php $form = ActiveForm::begin([
        'id' => 'filter-form',
        'method' => 'POST',
        'fieldConfig' => [
            'template' => "{label}{input}",
        ],
    ]) ?>
    <div class="row">
        <div class="col-auto">
            <?= $form->field($model, 'dateFrom', ['options' => ['class' => 'mb-0']])
                ->textInput(['type' => 'date']);
            ?>
        </div>
        <div class="col-auto">
            <?= $form->field($model, 'dateTo', ['options' => ['class' => 'mb-0']])
                ->textInput(['type' => 'date']);
            ?>
        </div>
        <!--<div class="col-auto">
            <div class="select-employee">
                <div class="selected-user">

                </div>
                <span class="btn-choose-user">
                <a href="javascript:void(0);" onclick="selectEmployees();"><i class="far fa-plus"></i>Выбрать сотрудника</a>
            </span>
            </div>
        </div>-->
        <div class="col-auto d-flex align-items-end">
            <?= Html::submitButton('Сформировать', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
    <?php $form::end(); ?>
</div>

<div class="wrapper-block mt-3">
    <table class="default-table">
        <thead>
        <tr>
            <th>Сотрудник</th>
            <th class="text-center">Среднее время ответа</th>
        </tr>
        </thead>
        <tbody>
            <?php if(!empty($report)) : ?>
                <?php foreach ($report as $name => $value) : ?>
                <tr>
                    <td class="column-small"><?= $name ?></td>
                    <td class="text-center column-small"><?= date('h:s', $value) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center">Нет данных</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>