<?php
/**
 * @package    Cli_Scripts
 * @author     Dmitry Rekun <d.rekuns@gmail.com>
 * @copyright  Copyright (C) 2020 JPathRu. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// We are a valid entry point.
const _JEXEC = 1;

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines.
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// System configuration.
$config = new JConfig;

define('JDEBUG', $config->debug);

/**
 * This script will create an article with custom fields.
 *
 * @since  1.0
 */
class ArticleCreateCli extends CliApplication
{
	/**
	 * Site application.
	 *
	 * @var    SiteApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Entry point for CLI script.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		$this->out('Starting article creation');

		try
		{
			$this->app = Factory::getApplication('site');

			$this->createArticle();
		}
		catch (Exception $e)
		{
			$this->out('Oops...' . $e->getMessage() . ' :: ' . $e->getTraceAsString());

			return;
		}

		$this->out('Article was created successfully');
	}

	/**
	 * Creates an article.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 * @throws  \RuntimeException
	 */
	private function createArticle()
	{
		define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_content');

		BaseDatabaseModel::addIncludePath(JPATH_COMPONENT . '/models/', 'ContentModel');

		/** @var ContentModelArticle $model */
		$model = BaseDatabaseModel::getInstance('Article', 'ContentModel');

		$article = [
			'id'        => 0,
			'title'     => 'Article Title', // Title
			'alias'     => '', // Empty alias to avoid notice warnings
			'introtext' => 'Article Text', // Text
			'catid'     => 2, // Category
			'state'     => 0, // Publishing state
			'language'  => '*', // Language
			'access'    => $this->app->get('access', 1) // Access level
		];

		// Load the form.
		$form = $model->getForm($article, false);

		if (!$form)
		{
			throw new \RuntimeException('Error getting form: ' . $model->getError());
		}

		// Validate the form.
		if (!$model->validate($form, $article))
		{
			throw new \RuntimeException('Error validating article: ' . $model->getError());
		}

		// Emulate save task.
		$this->app->input->set('task', 'save');

		// Save an article.
		if (!$model->save($article))
		{
			throw new \RuntimeException('Error saving article: ' . $model->getError());
		}

		$this->saveArticleFields($model->getItem()->id);
	}

	/**
	 * Saves article custom fields.
	 *
	 * @param   integer  $articleId  Article ID.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function saveArticleFields($articleId)
	{
		\JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models', 'FieldsModel');

		/** @var FieldsModelField $model */
		$model = BaseDatabaseModel::getInstance('Field', 'FieldsModel', array('ignore_request' => true));

		// Define the fields: Field ID => Field value.
		$fields = [
			1 => 'Text',
			3 => ['Value 3', 'Value 1'],
			4 => [2, 1]
		];

		// Save the fields.
		foreach ($fields as $key => $value)
		{
			$model->setFieldValue($key, $articleId, $value);
		}
	}
}

CliApplication::getInstance('ArticleCreateCli')->execute();
