<?php
/**
 * @author: Sergey Mashkov (serge@asse.com)
 * Date: 6/18/23 12:01 PM
 * Project: asse-db-template
 */

namespace xpbl4\import\models;

trait ImportTrait
{
	/**
	 * @inheritdoc
	 */
	public static function import($reader, $row, $data)
	{
		if ($row == 0) return true;

		$_primaryKey = [];
		$_data = [];
		foreach ($reader->headers AS $_id => $_header) {
			$_attribute = $_header['attribute'];
			if (!empty($_attribute) && $_attribute != 'none') {
				if (empty($_data[$_attribute])) $_data[$_attribute] = trim($data[$_id]);
				else $_data[$_attribute] .= ' ' . trim($data[$_id]);
				if ($_header['key']) $_primaryKey[$_attribute] = trim($data[$_id]);
			}
		}

		// Create model from data
		$model = null;
		if (!empty($_primaryKey)) $model = static::findOne($_primaryKey);
		if ($model == null) $model = new static();

		$transaction = \Yii::$app->db->beginTransaction();

		try {
			$model->setAttributes($_data);

			if($model->validate()) {
				if ($model->save()) {
					$transaction->commit();
				} else {
					$reader->addError($row, 'Save: '.implode(', ', $model->getFirstErrors()));

					return false;
				}
			} else {
				$reader->addError($row, 'Model: '.implode(', ', $model->getFirstErrors()));

				return false;
			}
		} catch (\Exception $e) {
			$transaction->rollBack();
			$reader->addError($row, 'Exception: '.$e->getMessage());

			return false;
		}

		return $model;
	}

}