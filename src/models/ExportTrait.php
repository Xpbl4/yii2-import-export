<?php
/**
 * @author: Sergey Mashkov (serge@asse.com)
 * Date: 6/18/23 4:29 PM
 * Project: asse-db-template
 */

namespace xpbl4\import\models;

use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

trait ExportTrait
{

	public function export()
	{
		$query = $this->getQuery();

		$offset = $this->offset;
		$limit = $this->limit;

		if ($limit > 0) {
			$query->limit($limit);
		}

		if ($offset > 0) {
			$query->offset($offset);
		}

		$models = $query->all();

		$data = [];
		foreach ($models as $model) {
			$data[] = $model->attributesExport();
		}

		return $data;
	}

	public function attributesExport()
	{
		return $this->attributes;
	}

	/**
	 * Returns the data cell value.
	 * @param mixed $model the data model
	 * @param mixed $key the key associated with the data model
	 * @param int $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
	 * @return string the data cell value
	 */
	public function getDataCellValue($model, $key, $index)
	{
		if ($this->value !== null) {
			if (is_string($this->value)) {
				return ArrayHelper::getValue($model, $this->value);
			}

			return call_user_func($this->value, $model, $key, $index, $this);
		} elseif ($this->attribute !== null) {
			return ArrayHelper::getValue($model, $this->attribute);
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function renderDataCellContent($model, $key, $index)
	{
		if ($this->content === null) {
			return $this->grid->formatter->format($this->getDataCellValue($model, $key, $index), $this->format);
		}

		if ($this->content !== null) {
			return call_user_func($this->content, $model, $key, $index, $this);
		}

		return $this->grid->emptyCell;
	}

	/**
	 * Creates an object based on a string in the format of "attribute:format|headerClass:label".
	 * @param string $text the column specification string
	 * @return DynamicModel the column instance
	 * @throws \yii\base\InvalidConfigException if the column specification is invalid
	 */
	public function createDataColumn($config)
	{
		$model = new DynamicModel(['attribute', 'format', 'label', 'visible', 'hidden', 'options']);

		if (!is_array($config)) {
			if (!preg_match('/^([^:]+)(:([^:]+))?(:(.*))?$/', $config, $matches)) {
				throw new \yii\base\InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
			}
			$config = [
				'attribute' => $matches[1],
				'format' => 'text',
				'label' => isset($matches[5]) ? $matches[5] : $this->getAttributeLabel($matches[1]),
				'options' => [],
			];
			if (isset($matches[3])) {
				$_formatOptions = @explode('|', $matches[3]);
				$config['format'] = $_formatOptions[0];
				if (isset($_formatOptions[1])) $config['options']['class'] = $_formatOptions[1];
			}
		}
		$model->setAttributes($config, false);

		return $model;
	}

}