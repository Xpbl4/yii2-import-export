<?php

namespace xpbl4\import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads Excel files using PHPExcel.
 * @package xpbl4\import
 */
class ExcelReader extends BaseReader
{
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