<?php

namespace app\modules\handle_post\controllers;

use yii\web\Controller;

/**
 * Default controller for the `handle_post` module
 */
class MainController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
