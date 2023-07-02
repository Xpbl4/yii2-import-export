<?php
/**
 * @author: Sergey Mashkov (serge@asse.com)
 * Date: 6/25/23 12:51 PM
 * Project: asse-db-template
 */

use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use yii\widgets\Pjax;

/** @var $this yii\web\View */
/** @var $isModal boolean */
/** @var $model xpbl4\import\models\ExportForm */
/** @var $source \yii\db\ActiveRecord|\xpbl4\import\ExportInterface */
/** @var $form yii\widgets\ActiveForm */

$_labels = $source->attributeLabels();
$exportJS = <<<JS
	$(".table-checked").selectRows();
	$(".table-sorted tbody").sortableWidget({
		'handle': '.sortable-widget-handler',
		'animation': 300
	});
JS;
$this->registerJs($exportJS, \yii\web\View::POS_READY);
?>
<?php Pjax::begin(['id' => 'pjax-export-form', 'formSelector' => false, 'timeout' => false]); ?>
<?php $form = ActiveForm::begin(['id' => uniqid('export-'), 'action' => ['export'], 'options' => ['data-pjax' => '#pjax-export-form']]); ?>

<div class="box-body">
	<?= $form->errorSummary($model); ?>

	<div class="row">
		<div class="col-sm-3"><?= $form->field($model, 'offset')->error(false); ?></div>
		<div class="col-sm-3"><?= $form->field($model, 'limit')->error(false); ?></div>
		<div class="col-sm-6"><?= $form->field($model, 'format')->dropDownList($model->exportFormats)->error(false); ?></div>
	</div>

<?php
	if (!empty($source->attributesExport())) {
		$attributes = $source->attributesExport();
	} else {
		$attributes = $source->attributes();
	}
?>
	<div class="row">
		<div class="col-sm-12">
			<table class="table table-bordered table-sorted table-checked">
				<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="select-on-check-all" value="1" checked /></th>
					<th class="priority-column"><span class="glyphicon glyphicon-sort"></span></th>
					<th class="col-sm-4">Attribute</th>
					<th>Format</th>
				</tr>
				</thead>
				<tbody style="max-height: 250px; overflow: scroll;">
<?php
	foreach ($attributes as $_key => $_value) {
		$_column = $source->createDataColumn($_value);
		$_id = $_column->attribute;
?>
					<tr>
						<td><?= Html::activeCheckbox($model, 'columns['.$_id.'][key]', ['label' => false, 'class' => 'select-on-check-row', 'checked' => 'checked']); ?></td>
						<td><div class="sortable-widget-handler" data-id="<?= $_id; ?>" data-offset="0">☰</div></td>
						<td class="form-column"><?= Html::activeTextInput($model, 'columns['.$_id.'][label]', ['placeholder' => $_column->label, 'class' => 'form-control input-sm']); ?></td>
						<td class="form-column"><?= Html::activeDropDownList($model, 'columns['.$_id.'][format]', $model->fieldFormats, ['prompt' => 'Default', 'class' => 'form-control input-sm']); ?></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</div>
	</div>

</div>

<div class="box-footer text-right">
	<?= Html::submitButton('<i class="glyphicon glyphicon-export"></i> '.Yii::t('app', 'Export'), ['class' => 'btn btn-success pjax-submit']) ?>
	<?= Yii::$app->request->isAjax || $isModal ? Html::button(Yii::t('app', 'Close'), ['class' => 'btn btn-default', 'data-dismiss' => 'modal']) : '' ?>
</div>

<?php ActiveForm::end(); ?>
<?php Pjax::end(); ?>
