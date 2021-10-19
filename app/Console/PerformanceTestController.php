<?php

namespace App\Console;

use App\Model\Document;
use App\Model\CurrentStock;

class PerformanceTestController extends \yii\console\Controller
{
    /** @var int  Total allowed time in seconds */
    protected $testingTime = 10;

    /** @var int  Timeout for usleep(), it is running on every iteration to prevent high processor usage and on overload */
    protected $usleepTimeout = 1000;

    /** @var int  Max allowed concurrent processes, just in case, usually should not be reached */
    protected $maxConcurrentProcesses = 200;

    /** @var bool  If to emulate SQL queries as when user opens document page during real work */
    protected $emulateDocumentPage = false;

    public function options($actionID)
    {
        return ['testingTime', 'usleepTimeout', 'maxConcurrentProcesses', 'emulateDocumentPage'];
    }

    public function actionRunApp()
    {
        $this->runPerformanceTest('document-manager/close');
    }

    public function actionRunDb()
    {
        $this->runPerformanceTest('document-manager/close-by-procedure');
    }

    public function actionRunPage()
    {
        $this->runPerformanceTest('document-manager/emulate-document-page');
    }

    public function runPerformanceTest($command)
    {
        $this->resetData();

        $usleepTimeout = $this->usleepTimeout;

        $processList = [];
        $processCount = 0;

        $startTime = microtime(true);
        $currentTime = $startTime;
        while (true) {
            $prevCurrentTime = $currentTime;
            $currentTime = microtime(true);
            if (intval($prevCurrentTime - $startTime, 1) - intval($currentTime - $startTime, 1)) {
                // print status every second
                $this->printStatus($processCount, count($processList), intval($currentTime - $startTime));
            }

            $this->removeCompleted($processList);
            if (count($processList) >= $this->maxConcurrentProcesses) {
                usleep($usleepTimeout);
                continue;
            }

            list($documentId, $closingType) = $this->generateActionParams();

            $cmd = 'php yii ' . $command . ' ' . $documentId . ' ' . $closingType;
            if ($this->emulateDocumentPage) {
                $cmd = 'php yii document-manager/emulate-document-page ' .  $documentId . ' && ' . $cmd;
            }

            $process = $this->runProcess($cmd);
            $processList[] = $process;
            $processCount++;

            if ($currentTime - $startTime > $this->testingTime) {
                break;
            }

            // prevent high CPU usage
            // timeout should be small
            usleep($usleepTimeout);
        }

        while (!empty($processList)) {
            $prevCurrentTime = $currentTime;
            $currentTime = microtime(true);
            if (intval($prevCurrentTime - $startTime, 1) - intval($currentTime - $startTime, 1)) {
                // print status every second
                $this->printStatus($processCount, count($processList), intval($currentTime - $startTime));
            }

            $this->removeCompleted($processList);
            usleep($usleepTimeout);
        }

        $stopTime = microtime(true);

        $seconds = ($stopTime - $startTime);

        echo 'Total requests: ' . $processCount . "\n";
        echo 'Total time: ' . $seconds . "\n";
        echo 'Requests per second: ' . $processCount / $seconds . "\n";
        echo 'Estimated request time: ' . $seconds / $processCount . "\n";
    }

    protected function generateActionParams()
    {
        $documentId = rand(1, 10000);
        $closingType = [1, -1][rand(0, 1)];

        return [$documentId, $closingType];
    }

    protected function runProcess($cmd)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cwd = __DIR__ . '/../../';

        $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, null);

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return [$process, $pipes];
    }

    protected function printStatus($totalCount, $currentCount, $timeDiff)
    {
        echo $currentCount . ' / ' . $totalCount . ' / ' . $timeDiff . "    \r";
    }

    protected function removeCompleted(&$processList)
    {
        foreach ($processList as $index => $processData) {
            list($resource, $pipes) = $processData;

            // clear data from pipes to prevent process getting stuck on output
            while ($line = fread($pipes[1], 100 * 1024)) echo $line;
            while ($line = fread($pipes[2], 100 * 1024)) echo $line;

            $status = proc_get_status($resource);
            if ($status['running'] === false) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($resource);
                unset($processList[$index]);
            }
        }
    }

    protected function resetData()
    {
        CurrentStock::deleteAll();
        Document::updateAll(['status' => 0]);
    }

    public function actionRunPureCalls($callNumber = 600)
    {
        $documentManagerController = new DocumentManagerController('document-manager', $this->module);

        $this->resetData();

        $startTime = microtime(true);
        for ($i = 0; $i < $callNumber; $i++) {
            if ($i % 10 === 0) {
                echo $i . "\r";
            }

            list($documentId, $closingType) = $this->generateActionParams();

            try {
                $documentManagerController->actionClose($documentId, $closingType);
            } catch (\Exception $ex) {
                // just skip
            }
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

            list($documentId, $closingType) = $this->generateActionParams();

            try {
                $documentManagerController->actionCloseByProcedure($documentId, $closingType);
            } catch (\Exception $ex) {
                // just skip
            }
        }
        $stopTime = microtime(true);
        $timeDiff = $stopTime - $startTime;

        echo 'DB:' . "\n";
        echo 'Call number: ' . $callNumber . "\n";
        echo 'Total time: ' . $timeDiff . "\n";
        echo 'Calls per second: ' . ($callNumber / $timeDiff) . "\n";
    }
}
