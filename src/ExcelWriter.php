<?php

namespace xpbl4\import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Writes Excel files using PHPExcel.
 * @package xpbl4\import
 */
class ExcelWriter extends BaseWriter
{
	/**
	 * Writes to an Excel file.
	 * @param string $writerType PhpOffice\PhpSpreadsheet\Writer type
	 * @return string filename
	 */
	public function write($writerType = IOFactory::WRITER_XLSX)
	{
		$exporter = $this->source;
		$filename = \Yii::$app->runtimePath.'/'.uniqid().'.xlsx';

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		foreach ($exporter->export() as $rowNum => $row) {
			foreach ($row as $colNum => $value) {
				$sheet->setCellValueByColumnAndRow($colNum, $rowNum + 1, $value);
			}
		}

		$writer = IOFactory::createWriter($spreadsheet, $writerType);
		$writer->save($filename);

		return $filename;
	}
}
