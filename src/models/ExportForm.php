<?php
/**
 * Created by PhpStorm.
 * User: Serge Mashkov
 * Date: 29/05/2018
 * Time: 13:04
 */
namespace xpbl4\import\models;

use xpbl4\import\ExcelWriter;
use xpbl4\import\ExportInterface;
use Yii;
use yii\base\Model;

/**
 * ImportForm is the model behind the contact form.
 */
class ExportForm extends Model
{
	/** @var string HTML (Hyper Text Markup Language) export format */
	const FORMAT_HTML = 'html';

	/** @var string CSV (comma separated values) export format */
	const FORMAT_CSV = 'csv';

	/** @var string Text export format */
	const FORMAT_TEXT = 'txt';

	/** @var string PDF (Portable Document Format) export format */
	const FORMAT_PDF = 'pdf';

	/** @var string Microsoft Excel 95+ export format */
	const FORMAT_EXCEL = 'xls';

	/** @var string Microsoft Excel 2007+ export format */
	const FORMAT_EXCEL_X = 'xlsx';

	const FORMAT_LIST = [
		self::FORMAT_EXCEL_X => 'Excel 2007+',
		self::FORMAT_EXCEL => 'Excel - Microsoft Excel 95+',
		self::FORMAT_PDF => 'PDF - Portable Document Format',
		self::FORMAT_CSV => 'CSV - Comma Separated Values',
		self::FORMAT_TEXT => 'Text - Tab Delimited',
		self::FORMAT_HTML => 'HTML - Hyper Text Markup Language',
	];

	public $offset = 0;
	public $limit = 0;
	public $format = self::FORMAT_EXCEL_X;
	public $allowedFormats = [self::FORMAT_EXCEL_X, self::FORMAT_PDF];
	public $columns = [];
	public $process = 0;

	public $timeout = false;
	public $result;

	public $_time = [];
	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			[['offset', 'limit', 'process'], 'integer'],
			['columns', 'safe'],
		];
	}

	public function attributeLabels()
	{
		return [
			'limit' => Yii::t('app', 'Limit'),
			'offset' => Yii::t('app', 'Offset'),
			'format' => Yii::t('app', 'Export format'),
			'columns' => Yii::t('app', 'Columns'),
		];
	}

	public function getExportFormats()
	{
		return array_filter(self::FORMAT_LIST, function($key) {
			return in_array($key, $this->allowedFormats);
		}, ARRAY_FILTER_USE_KEY);
	}

	public function getFieldFormats()
	{
		return [
			'integer' => Yii::t('app', 'Integer'),
			'float' => Yii::t('app', 'Float'),
			'string' => Yii::t('app', 'String'),
			'date' => Yii::t('app', 'Date'),
			'datetime' => Yii::t('app', 'Datetime'),
			'time' => Yii::t('app', 'Time'),
			'boolean' => Yii::t('app', 'Boolean'),
		];
	}

	/**
	 * @param $model ExportInterface export model
	 * @param $file
	 * @return bool|mixed
	 */
	public function export($model, $filename)
	{
		$max_time = ini_get("max_execution_time");

		$reader = new ExcelWriter(['source' => $model]);

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
						if ($model->import($reader, $i, $dataRow)) {
							$this->result['complete'][$i] = true;
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