<?php

namespace xpbl4\import;

use PHPExcel;
use PHPExcel_IOFactory;

/**
 * Writes Excel files using PHPExcel.
 * @package xpbl4\import
 */
class ExcelWriter extends BaseWriter
{
	/**
	 * Writes to an Excel file.
	 * @param string $writerType PHPExcel writer type
	 * @return string filename
	 */
	public function write($writerType = 'Excel2007')
	{
		$exporter = $this->source;
		$filename = \Yii::$app->runtimePath.'/'.uniqid().'.xlsx';

		$xl = new PHPExcel();
		$sheet = $xl->getSheet(0);

		foreach ($exporter->export() as $rowNum => $row) {
			foreach ($row as $colNum => $value) {
				$sheet->setCellValueByColumnAndRow($colNum, $rowNum + 1, $value);
			}
		}

		$writer = PHPExcel_IOFactory::createWriter($xl, $writerType);
		$writer->save($filename);

		return $filename;
	}
}
