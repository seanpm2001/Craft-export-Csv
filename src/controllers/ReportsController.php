<?php
/**
 * Exports Controller
 *
 * @author    Camille Boissel
 * @package   CraftExportCsv
 * @since     1.0.1
 */

namespace kffein\craftexportcsv\controllers;

use kffein\craftexportcsv\CraftExportCsv;
use Craft;
use craft\web\Controller;
use craft\helpers\ElementHelper;

class ReportsController extends Controller
{
    /**
     * The active Plugin class
     *
     * @var Plugin
     */
    public $plugin;

    /**
     * @var kffein\craftexportcsv\models\Settings
     */
    public $settings;

    /**
     * @inheritdoc
     */
    public function init()
    {
        /**
         * For unknow reason Craft Controller set request + response to string
         * Manually reset the value fix the problem
         */
        $this->request = Craft::$app->request;
        $this->response = Craft::$app->response;

        $this->plugin = CraftExportCsv::$plugin;
        $this->settings = $this->plugin->getSettings();
    }

    /**
     *
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionIndex()
    {
        $section = null;

        // Fetch all exports data to include in the template
        $exportsInfos = [];
        if (!empty($this->settings->exports)) {
            foreach ($this->settings->exports as $export) {
                if ($export['sectionHandle']) {
                    $section = Craft::$app->getSections()->getSectionByHandle($export['sectionHandle']);
                    $export['section'] = $section;
                }
                $exportsInfos[] = $export;
            }
        }

        return $this->renderTemplate('craft-export-csv/reports/index', ['exports' => $exportsInfos]);
    }

    /**
     *
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionGenerate()
    {
        $id = Craft::$app->request->getParam('id');

        // Get the export settings by id
        $export = $this->plugin->exportsService->getExportById($id);

        // If for some reason there were no section selected
        if (!$export['sectionHandle']) {
            throw new \Exception('Can\'t start csv generator without section defined');
        }

        // Return an error for empty fields
        if (empty($export['fields'])) {
            Craft::$app->getSession()->setError('There are no field to generate the csv');
            return Craft::$app->controller
                ->redirect('craft-export-csv');
        }

        $this->plugin->reportsService->generateCsvLines($export);

        // Redirect with notice to start the queue
        Craft::$app->getSession()->setNotice('Csv generator started');
        return Craft::$app->controller
                ->redirect('craft-export-csv');
    }

    /**
     * Download generated files by id
     */
    public function actionDownload()
    {
        $id = Craft::$app->request->getParam('id');

        // Fetch the export by id
        $export = $this->plugin->exportsService->getExportById($id);

        $folder = CRAFT_BASE_PATH . '/storage/reports/';

        // if (file_exists($folder . $export['lastSavedFilename'])) {
        // Downlading the file
        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header(
            sprintf('Content-Disposition: attachment; filename=%s', ElementHelper::normalizeSlug($export['lastSavedFilename']))
        );
        readfile($folder . $export['lastSavedFilename']);
        // } else {
        //     // If it does not exist we tell the export setting to reset his
        //     // dateUpdated so it knows it need to be regenerated
        //     Craft::$app->getSession()->setError('File not found');
        //     CraftExportCsv::getInstance()->exportsService->setExportDate($export['id'], null);
        //     return Craft::$app->controller
        //         ->redirect('craft-export-csv');
        // }

        die();
    }
}
