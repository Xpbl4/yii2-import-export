<?php

namespace xpbl4\import;

interface ImportInterface
{
	/**
	 * Process a row (typically import the row to a database).
	 * @param BaseReader $reader
	 * @param integer $row zero-based row number
	 * @param array $data row data
	 */
	public static function import($reader, $row, $data);
}