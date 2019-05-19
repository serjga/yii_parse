<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use backend\models\Test;
use common\models\Robot;

/**
 * Site controller
 */
class TestController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actionGetPage()
    {
        $robot = new Robot;
        $robot->searchAll();
    }

}
