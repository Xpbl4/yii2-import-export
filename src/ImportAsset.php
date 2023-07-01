<?php
/**
 * @author: Sergey Mashkov (serge@asse.com)
 * Date: 4/24/23 10:59 AM
 * Project: asse-db-template
 */

namespace xpbl4\import;

class ImportAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@xpbl4/import/assets';

	public $js = [
		'js/jquery-select-columns.js',
	];

	public $css = [
		'css/modal-import.css',
	];

	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
		'yii\bootstrap\BootstrapPluginAsset',
	];
}
