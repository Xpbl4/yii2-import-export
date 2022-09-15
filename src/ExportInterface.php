<?php

namespace xpbl4\import;

interface ExportInterface
{
	/**
	 * @return array data to export
	 */
	public function export();
}