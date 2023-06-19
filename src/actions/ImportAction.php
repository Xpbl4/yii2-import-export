<?php
/**
 * This file is part of yii2-import-export.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/xpbl4/yii2-import-export
 */

namespace xpbl4\import\actions;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\UploadedFile;

/**
 * UploadFileAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'import' => [
 *             'class' => 'xpbl4\import\actions\ImportAction',
 * 	           'form' => xpbl4\import\models\ImportForm::class,
 * 		       'model' => Post::class
 *         ]
 *     ];
 * }
 * ```
 *
 * @author Sergey Mashkov <xpbl4@motorwars.ru>
 *
 * @link https://github.com/xpbl4/yii2-import-export
 */
class ImportAction extends \yii\base\Action
{
	/**
	 * @var \yii\db\ActiveRecord model class name
	 */
	public $model;

	/**
	 * @var \yii\base\Model form class name
	 */
	public $form = '\xpbl4\import\models\ImportForm';

    /**
     * @var string Path to directory where files will be uploaded.
     */
    public $path;

	/**
	 * @var string Redirect url
	 */
	public $redirect;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->form === null)
            throw new InvalidConfigException('The "form" attribute must be set.');

        if ($this->model === null)
            throw new InvalidConfigException('The "model" attribute must be set.');

        if ($this->path === null)
			$this->path = \Yii::getAlias('@runtime/import');

		if ($this->redirect === null)
			$this->redirect = ['index'];
			//$this->redirect = Yii::$app->request->referrer;

		parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
	    ini_set('max_execution_time', 60);

	    $_result = Yii::$app->session->getFlash('import-result', ['limit' => 100, 'offset' => 0]);
	    $model = new $this->model;

		/** @var \xpbl4\import\models\ImportForm $form */
	    $form = new $this->form;
	    $form->setAttributes($_result);
	    $form->_time['start'] = microtime(true);

	    if ($form->load(Yii::$app->request->post())) {
		    $_importPath = $this->path;
		    if (!empty($form->filename)) {
			    $_fileupload = pathinfo($form->filename);
			    $_fileimport = $_importPath.'/'.md5($_fileupload['filename']).'.'.$_fileupload['extension'];
		    }

		    if (empty($form->filename) && ($_fileupload = UploadedFile::getInstance($form, 'filename'))) {
			    $form->filename = $_fileupload->name;

			    if (!file_exists($_importPath)) mkdir($_importPath, 0755, true);

			    $_fileimport = $_importPath.'/'.md5($_fileupload->baseName).'.'.$_fileupload->extension;
			    $_fileupload->saveAs($_fileimport);
		    }

		    if ($form->validate() && $form->import($model, $_fileimport)) {
			    $type = 'success-timeout';
			    $message = ['File successfully imported into the database.'];
			    if (!empty($form->result['created'])) $message[] = Yii::t('app/import', 'The {count, plural, =0{no records} =1{one record} other{# records}} successfully created.', ['count' => count($form->result['created'])]);
			    if (!empty($form->result['updated'])) $message[] = Yii::t('app/import', 'The {count, plural, =0{no records} =1{one record} other{# records}} successfully updated.', ['count' => count($form->result['updated'])]);

			    if (!empty($form->result['error'])) {
				    $type = 'danger';
				    $message = ['File imported into the database.'];
				    if (count($form->result['complete']) > 0) {
					    $type = 'warning';
					    $message[] = Yii::t('app/import', 'The {count, plural, =0{no records} =1{one record} other{# records}} successfully imported.', ['count' => count($form->result['complete'])]);
				    }
				    $message[] = Yii::t('app/import', 'The {count, plural, =0{no records} =1{one record} other{# records}} not imported.', ['count' => count($form->result['error'])]);

				    $_summary = [];
				    foreach ($form->result['error'] as $_row => $_message) {
					    $_summary[] = '<li>row #'.$_row.': '.$_message[0].'</li>';
				    }
				    if (!empty($_summary)) $message[] = '<div class="box-overflow"><ul>'.implode('', $_summary).'</ul></div>';
			    }

			    Yii::$app->getSession()->setFlash($type, implode('<br />', $message));

			    return $this->controller->redirect($this->redirect);
		    }
	    }

	    if (Yii::$app->request->isAjax || Yii::$app->request->isPjax)
		    return $this->controller->renderAjax('@xpbl4/import/views/form', ['model' => $form, 'destination' => $model]);

	    return $this->controller->render('@xpbl4/import/views/form', ['model' => $form, 'destination' => $model]);
	}
}
