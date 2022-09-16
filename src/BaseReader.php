<?php

namespace xpbl4\import;

/**
 * Reads files.
 * @package xpbl4\import
 */
abstract class BaseReader extends \yii\base\BaseObject
{
	/**
	 * @var ImportInterface|\yii\db\ActiveRecord|string importer instance or class name
	 */
	public $model;

	/**
	 * @var array data
	 */
	public $rows = [];

	/**
	 * @var array import errors
	 */
	private $_errors = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if (!$this->model) {
			throw new \yii\base\InvalidConfigException('The "model" property must be set.');
		}
		if (is_string($this->model)) {
			$this->model = new $this->model;
		}
	}

	/**
	 * Adds an error.
	 * @param integer $row
	 * @param mixed $message
	 */
	public function addError($row, $message)
	{
		$this->_errors[] = ['row' => $row, 'message' => $message];
	}

	/**
	 * @return array errors
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * @param $row integer
	 * @return array row errors
	 */
	public function getError($row)
	{
		$_result = [];
		$_errors = $this->getErrors();
		foreach ($_errors as $_error) {
			if ($_error['row'] == $row) $_result[] = $_error['message'];
		}

		return !empty($_result) ? $_result : null;
	}

	/**
	 * Imports data via the configured importer.
	 * @param string $filename
	 * @return bool
	 */
	public function import($filename)
	{
		$this->_errors = [];
		$this->read($filename);

		foreach ($this->rows as $i => $row) {
			if (!$this->model->import($this, $i, $row)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Reads from a file.
	 * @param string $filename
	 */
	protected abstract function read($filename);
}