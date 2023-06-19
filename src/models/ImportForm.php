<?php
/**
 * Created by PhpStorm.
 * User: Serge Mashkov
 * Date: 29/05/2018
 * Time: 13:04
 */
namespace xpbl4\import\models;

use xpbl4\import\ExcelReader;
use xpbl4\import\ImportInterface;
use Yii;
use yii\base\Model;

/**
 * ImportForm is the model behind the contact form.
 */
class ImportForm extends Model
{
	public $filename;
	public $offset = 0;
	public $limit = 100;
	public $count = 0;
	public $headers = [];
	public $process = 0;

	public $timeout = false;
	public $result = [];

	public $_time = [];
	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			[['offset', 'limit', 'process'], 'integer'],
			['headers', 'safe'],

			[['filename'], 'required'],
			[['filename'], 'file', 'skipOnEmpty' => true, 'extensions' => 'csv,xls,xlsx'],
		];
	}

	public function attributeLabels()
	{
		return [
			'limit' => Yii::t('app', 'Limit'),
			'offset' => Yii::t('app', 'Offset'),
			'filename' => Yii::t('app', 'Import File'),
			'headers' => Yii::t('app', 'Headers'),
		];
	}

	/**
	 * @param $model ImportInterface importer instance or class name
	 * @param $file
	 * @return bool|mixed
	 */
	public function import($model, $file)
	{
		$max_time = ini_get("max_execution_time");

		$reader = new ExcelReader(['model' => $model]);
		$reader->load($file);

		$this->count = count($reader->rows);
		if (empty($this->headers)) $this->headers = $reader->headers();
		if ($this->checkHeaders()) {
			if ($this->process) {
				$reader->setHeaders($this->headers);
				$this->result = ['complete' => [], 'error' => []];

				for ($i = max(1, $this->offset); $i < min($this->count, max(1, $this->offset) + $this->limit); ++$i) {
					$dataRow = $reader->rows[$i];
					$emptyRow = true;
					foreach ($dataRow as $value) if (!empty($value)) $emptyRow = false;

					if (!$emptyRow) {
						if ($imported = $model::import($reader, $i, $dataRow)) {
							$this->result['complete'][$i] = true;
							if ($imported->isNewRecord) $this->result['created'][$i] = $imported->id;
							else $this->result['updated'][$i] = $imported->id;
						} else {
							$this->result['error'][$i] = $reader->getError($i);
						}

						$this->_time['end'] = microtime(true);
						if ($max_time * 0.8 < $this->_time['end'] - $this->_time['start']) {
							$this->offset = $i;
							$this->timeout = true;

							$this->addError('timeout', 'Import run other timeout. Start import with new offset.');
							return false;
						}
					}
				}

				return true;
			}
		} else
			$this->addError('headers', 'Error on header checking! Please fill all required fields.');

		return false;
	}

	public function checkHeaders()
	{
		foreach ($this->headers as $_id => $_header) {
			if (!intval($_header['ignore']) && empty($_header['attribute'])) return false;
		}

		return true;
	}
}