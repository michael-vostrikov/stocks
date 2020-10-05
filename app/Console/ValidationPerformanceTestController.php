<?php

namespace App\Console;

use App\Model\DataForm;
use App\Model\Document;
use App\Model\CurrentStock;

class ValidationPerformanceTestController extends PerformanceTestController
{
    protected $testingTime = 10;
    protected $emulateDocumentPage = false;
    protected $usleepTimeout = 100;

    protected function resetData()
    {
        \Yii::$app->db->createCommand('DELETE FROM data_for_validation_app')->execute();
        \Yii::$app->db->createCommand('DELETE FROM data_for_validation_db')->execute();
    }

    protected function generateActionParams()
    {
        $needError = (rand(0, 999) >= 950);

        return [$needError, 0];
    }

    public function actionRunApp()
    {
        $this->runPerformanceTest('validation/insert-app');
    }

    public function actionRunDb()
    {
        $this->runPerformanceTest('validation/insert-db');
    }

    public function actionRunPureCalls($callNumber = 600)
    {
        $controller = new ValidationController('validation', $this->module);

        $this->resetData();

        $startTime = microtime(true);
        for ($i = 0; $i < $callNumber; $i++) {
            if ($i % 10 === 0) {
                echo $i . "\r";
            }

            list($needError, $stub) = $this->generateActionParams();

            ob_start();
            $controller->actionInsertApp($needError);
            ob_get_clean();
        }
        $stopTime = microtime(true);
        $timeDiff = $stopTime - $startTime;

        echo 'App:' . "\n";
        echo 'Call number: ' . $callNumber . "\n";
        echo 'Total time: ' . $timeDiff . "\n";
        echo 'Calls per second: ' . ($callNumber / $timeDiff) . "\n";


        echo "\n";
        $this->resetData();

        $startTime = microtime(true);
        for ($i = 0; $i < $callNumber; $i++) {
            if ($i % 10 === 0) {
                echo $i . "\r";
            }

            list($needError, $stub) = $this->generateActionParams();

            ob_start();
            $controller->actionInsertDb($needError);
            ob_get_clean();
        }
        $stopTime = microtime(true);
        $timeDiff = $stopTime - $startTime;

        echo 'DB:' . "\n";
        echo 'Call number: ' . $callNumber . "\n";
        echo 'Total time: ' . $timeDiff . "\n";
        echo 'Calls per second: ' . ($callNumber / $timeDiff) . "\n";
    }
}
