<?php
namespace App\Service;

use Yii;

class BaseService
{
    public function runInTransaction(callable $actionCallback, $maxTryNumber = 10)
    {
        $transaction = null;

        return $this->executeWithRetry(
            function () use ($actionCallback, &$transaction) {
                $transaction = Yii::$app->db->beginTransaction();
                $res = $actionCallback();
                $transaction->commit();

                return $res;
            },
            function (\Exception $ex, int $tryNumber) use ($maxTryNumber, &$transaction) {
                $transaction->rollback();

                if ($ex instanceof \yii\db\Exception) {
                    return ($tryNumber < $maxTryNumber);
                }

                return false;
            }
        );
    }

    public function executeWithRetry(callable $actionCallback, callable $errorCallback)
    {
        $tryNumber = 0;
        while (true) {
            try {
                $res = $actionCallback();
                return $res;

            } catch (\Exception $ex) {
                $tryNumber++;
                $needContinue = $errorCallback($ex, $tryNumber);
                if ($needContinue) {
                    continue;
                }

                throw $ex;
            }
        }
    }
}
