<?php

namespace xpbl4\import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads Excel files using PhpOffice\PhpSpreadsheet.
 * @package xpbl4\import
 */
class ExcelReader extends BaseReader
{

	/**
	 * @var array import results
	 */
	private $_result = [];

	/**
	 * @var array import headers
	 */
	private $_headers = [];

	/**
	 * Imports data via the configured importer.
	 * @param string $filename
	 */
	public function load($filename) {
		$this->read($filename);
	}

	/**
	 * Imports data via the configured importer.
	 * @param string $filename
	 * @return bool|array
	 */
	public function result($filename, $limit=null, $offset=null) {
		$max_time = ini_get("max_execution_time");
		$time_start = microtime(true);

		//$this->read($filename);

		$count = count($this->rows);
		if ($limit === null) $limit = $count;
		if ($offset === null) $offset = 0;

		$this->_result = [
			'count' => $count,
			'limit' => $limit,
			'offset' => $offset,
			'filename' => $filename
		];

		$this->_result['headers'] = $this->getHeaders();
		if ($this->checkHeaders()) {
			for ($i = max(1, $offset); $i < min($count, max(1, $offset) + $limit); ++$i) {
				$row = $this->rows[$i];
				$emptyRow = true;
				foreach ($row as $value) if (!empty($value)) $emptyRow = false;

				if (!$emptyRow) {
					if ($this->model->import($this, $i, $row)) $this->_result['complete'][] = $i;
					else $this->_result['error'][] = $i;

					$this->_result['last'] = $i;

					$time_end = microtime(true);
					if ($max_time * 0.75 < $time_end - $time_start) {
						$this->_result['offset'] = $i;
						$this->_result['timeout'] = true;

						return $this->_result;
					}
				}
			}
			$this->_result['success'] = true;
		} else
			$this->_result['error_headers'] = true;

		return $this->_result;
	}

	public function headers() {
		if (!empty($this->rows) && empty($this->_headers)) {
			$_headers_data = [];
			$_headers_names = [];
			$_labels = $this->model->hasMethod('importLabels') ? $this->model->importLabels() : $this->model->attributeLabels();
			$_primary_keys = $this->model->primaryKey();
			$defaultKeys = array_filter($_labels, function($key) use ($_primary_keys) {
				return in_array($key, $_primary_keys);
			}, ARRAY_FILTER_USE_KEY);

			$_model = $this->model;
			$_headers = $this->rows[0];

			array_walk($_headers, function(&$item, $key) use ($_labels, $_model, $defaultKeys, &$_headers_data, &$_headers_names) {
				$_header = ['label' => $item];
				$_attributes = array_keys($_labels, trim($item));

				if (!empty($item)) {
					//$item = 'col_'.$key;
					if (!empty($_attributes)) {
						foreach ($_attributes as $_attribute) {
							if (!in_array($_attribute, $_headers_names)) {
								$item = $_attribute;
								break;
							}
						}
					} elseif(in_array($item, $_headers_names)) {
						$item = $item.'_'.$key;
					}

					if ($_model->canSetProperty($item)) {
						$_headers_data[$item] = $key;
						$_header['attribute'] = $item;
						if (in_array($key, $defaultKeys)) $_header['key'] = true;
					}
					$_headers_names[$key] = $item;

					$this->addHeader($key, $_header);
				}
			});
		}

		return $this->_headers;
	}

	/**
	 * @param integer $col zero-based row number
	 * @param array $value row header
	 */
	public function addHeader($col, $value)
	{
		$this->_headers[$col] = $value;
	}

	/**
	 * @return array row headers
	 */
	public function getHeaders()
	{
		return $this->_headers;
	}

	/**
	 * @param array $value row headers
	 */
	public function setHeaders($value)
	{
		$this->_headers = $value;
	}

	public function checkHeaders()
	{
		foreach ($this->_headers as $_id => $_header) {
			if (!intval($_header['ignore']) && empty($_header['attribute'])) return false;
		}

		return true;
	}

	/**
	 * Reads from an Excel file.
	 * @param string $filename
	 */
	protected function read($filename)
	{
		/**  Identify the type of $inputFileName  **/
		$inputFileType = IOFactory::identify($filename);

		/**  Create a new Reader of the type that has been identified  **/
		$reader = IOFactory::createReader($inputFileType);

		$spreadsheet = $reader->load($filename);
		$worksheet = $spreadsheet->getActiveSheet();

		$this->rows = [];
		foreach ($worksheet->getRowIterator() as $row) {
			$dataRow = [];
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			foreach ($cellIterator as $cell) {
				if (ExcelDate::isDateTime($cell)) {
					// Convert Excel representations of dates to yyyy-MM-dd format
					$dataRow[] = gmdate('Y-m-d', ExcelDate::excelToTimestamp($cell->getValue()));
				} else {
					$dataRow[] = $cell->getValue();
				}
			}
			$this->rows[] = $dataRow;
		}
	}
}