<?php
/**
 * Created by PhpStorm.
 * User: Serge Mashkov
 * Date: 29/05/2018
 * Time: 14:37
 */

use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use yii\widgets\Pjax;

/** @var $this yii\web\View */
/** @var $isModal boolean */
/** @var $model xpbl4\import\models\ImportForm */
/** @var $destination \yii\db\ActiveRecord */
/** @var $form yii\widgets\ActiveForm */

$_labels = $destination->hasMethod('importLabels') ? $destination->importLabels() : $destination->attributeLabels();
?>
<?php Pjax::begin(['id' => 'pjax-import-form', 'formSelector' => false, 'timeout' => false]); ?>
<?php $form = ActiveForm::begin(['id' => uniqid('import-'), 'action' => ['import'], 'options' => ['data-pjax' => '#pjax-import-form']]); ?>
<div class="box-body">
    All the rows from the uploaded file will be added to the database.<br />
    The file must have a unique ID from the database to update the existing records.<br /><br />
    The EXCEL(csv, xls, xlsx) file can be imported.<br /><br />

	<?= $form->errorSummary($model); ?>

    <?php
    if (!empty($model->result)) {
        $type = 'success';
	    $message = [];
        if (count($model->result['complete']) > 0)
	        $message[] = Yii::t('app', 'The {count, plural, =0{no records} =1{one record} other{# records}} successfully imported.', ['count' => count($model->result['complete'])]);
        if (!empty($model->result['error'])) {
	        $type = 'danger';
	        if (count($model->result['complete']) > 0) $type = 'warning';
	        $message[] = Yii::t('app', 'The {count, plural, =0{no records} =1{one record} other{# records}} not imported.', ['count' => count($model->result['error'])]);

	        $_summary = [];
	        foreach ($model->result['error'] as $_row => $_message) {
		        $_summary[] = '<li>row #'.$_row.': '.$_message[0].'</li>';
	        }
	        if (!empty($_summary)) $message[] = '<div class="box-overflow"><ul>'.implode('', $_summary).'</ul></div>';
        }

	    echo '<div class="alert alert-'.$type.'">'.implode('<br />', $message).'</div>';
    }
    ?>

    <?= !empty($model->filename) ?
	    $form->field($model, 'filename', [
            'template' => "{label}\n<div class=\"input-group\">{input}<span class=\"input-group-addon\">Count: ".($model->count)."</span></div>\n{hint}\n{error}",
        ])->textInput(['disabled' => true]).Html::activeHiddenInput($model, 'filename') :
        $form->field($model, 'filename')->fileInput()->error(false) ?>

    <div class="row">
        <div class="col-sm-6"><?= $form->field($model, 'offset')->error(false); ?></div>
        <div class="col-sm-6"><?= $form->field($model, 'limit')->error(false); ?></div>
    </div>

<?php
    if (!empty($model->headers)) {
	    $defaultKeys = array_filter($_labels, function($key) use ($destination) {
		    return in_array($key, $destination::primaryKey());
	    }, ARRAY_FILTER_USE_KEY);
	    //$defaultKeys = \common\helpers\DBHelper::valueById($_labels, $destination::primaryKey());

?>
    <?= $form->field($model, 'process')->hiddenInput(['value' => 1])->label(false); ?>
    <div class="hint">
        <ul>
            <li><strong>Source</strong> - the name of column in imported file</li>
            <li><strong>Key</strong> - this column(s) will be used as unique key to find records for update (Default: <?= implode(', ', $defaultKeys); ?>)</li>
            <li><strong>Model</strong> - the name of column in the database table</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-sm-12">
        <table class="table table-bordered table-import">
            <thead>
            <tr>
                <th class="col-sm-5">Source</th>
                <th class="col-sm-2 text-center"><span class="glyphicon glyphicon-chevron-right"></span></th>
                <th class="col-sm-5">Model</th>
            </tr>
            </thead>
            <tbody style="max-height: 250px; overflow: scroll;">
            <?php foreach ($model->headers as $_id => $_header): ?>
            <tr<?= empty($_header['attribute']) ? ' class="has-error"' : ''; ?>>
                <td><label class="control-label" for="headers-<?= $_id; ?>-attribute"><?= $_header['label']; ?></label><?= Html::activeHiddenInput($model, 'headers['.$_id.'][label]'); ?></td>
                <td class="text-center"><?= Html::activeCheckbox($model, 'headers['.$_id.'][key]', ['label' => false, 'data' => ['toggle' => 'tooltip', 'title' => 'Unique key']]); ?></td>
                <td><?= Html::activeDropDownList($model, 'headers['.$_id.'][attribute]', \yii\helpers\ArrayHelper::merge(['none' => '-= Ignore =-'], $_labels), ['prompt' => '[Select]', 'class' => 'form-control input-sm']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php
    }
?>
</div>

<div class="box-footer text-right">
	<?= Html::submitButton('<i class="glyphicon glyphicon-import"></i> '.Yii::t('app', 'Import'), ['class' => 'btn btn-success pjax-submit']) ?>
	<?= Yii::$app->request->isAjax || $isModal ? Html::button(Yii::t('app', 'Close'), ['class' => 'btn btn-default', 'data-dismiss' => 'modal']) : '' ?>
</div>

<?php ActiveForm::end(); ?>
<?php Pjax::end(); ?>
