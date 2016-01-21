<?php

class ApiController extends Controller
{
    private function switchModel($modelName)
    {
        $_model = false;
        switch (ucfirst($modelName))
        {
            case 'Products': {
                $_model = CActiveRecord::model('Products');
            } break;
            case 'Categories': {
                $_model = CActiveRecord::model('Categories');
            } break;
            default: {
                $this->_sendResponse(501, sprintf(
                    'Error: Mode <b>list</b> is not implemented for model <b>%s</b>',
                    $modelName) );
            }
        }
        return $_model;
    }

    public function actionList()
    {
        $this->_checkAuth();
        $ret = array();

        $_model = $this->switchModel($_GET['model']);

        if (isset($_model)) {
            $all_items = $_model->getAll($_GET);

            if(!empty($all_items)) {
                foreach ($all_items as $item) {
                    $ret[] = $item->attributes;
                }
            } else {
                $this->_sendResponse(200,
                    sprintf('No items where found for model <b>%s</b>', $_GET['model']) );
            }
        } else {
            $this->_sendResponse(501, sprintf(
                'Error: Mode <b>list</b> is not implemented for model <b>%s</b>',
                $_GET['model']) );
        }
        $this->_sendResponse(200, CJSON::encode($ret));
    }

    public function actionView()
    {
        $this->_checkAuth();
        $ret = array();

        $_model = $this->switchModel($_GET['model']);

        $item = $_model->findByPk($_GET['id']);

        if(!empty($item)) {
            $ret = $item->attributes;
            $this->_sendResponse(200, CJSON::encode($ret));
        } else {
            $this->_sendResponse(404, 'No Item found with id ' . $_GET['id']);
        }
    }

    public function actionUpdate()
    {
        $this->_checkAuth();
        $post = Yii::app()->request->rawBody;

        $_model = $this->switchModel($_GET['model']);

        $item = $_model->findByPk($_GET['id']);

        if (!empty($item))
        {
            if (!empty($post))
            {
                $data = CJSON::decode($post, true);

                foreach($data as $var => $value) {
                    if($_model->hasAttribute($var))
                        $_model->$var = $value;
                    else {
                        $this->_sendResponse(500,
                            sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>',
                                $var, $_GET['model']) );
                    }
                    $item->$var = $value;
                }

                if($item->save()) {
                    $this->_sendResponse(200, CJSON::encode($_model));
                }
                else {
                    $this->_sendResponse(500, $this->_getCreateErrorMessage($_model));
                }
            } else {
                $this->_sendResponse(500, 'No data to create item');
            }
        } else {
            $this->_sendResponse(400,
                sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );
        }

    }
    public function actionDelete()
    {
        $this->_checkAuth();
        $_model = $this->switchModel($_GET['model']);

        $item = $_model->findByPk($_GET['id']);

        if(empty($item))
            $this->_sendResponse(400,
                sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );

        if(!empty($item)) {
            $item->delete();
            $this->_sendResponse(200, 'Item was deleted');
        } else {
            $this->_sendResponse(500,
                sprintf("Error: Couldn't delete model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );
        }
    }

    public function actionCreate()
    {
        $this->_checkAuth();

        $post = Yii::app()->request->rawBody;

        $_model = $this->switchModel($_GET['model']);
        $_model->isNewRecord = true;

        if (!empty($post))
        {
            $data = CJSON::decode($post, true);

            foreach($data as $var => $value) {
                if($_model->hasAttribute($var))
                    $_model->$var = $value;
                else
                    $this->_sendResponse(500,
                        sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var,
                            $_GET['model']) );
            }

            if($_model->save()) {
                $this->_sendResponse(200, CJSON::encode($_model));
            }
            else {
                $this->_sendResponse(500, $this->_getCreateErrorMessage($_model));
            }
        } else {
            $this->_sendResponse(500, 'No data to create item');
        }

    }


    private function _sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        header('Content-type: ' . $content_type);

        echo $body;

        Yii::app()->end();
    }

    private function _getStatusCodeMessage($status)
    {
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    private function _getCreateErrorMessage($_model)
    {
        $msg = "<h1>Error</h1>";
        $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
        $msg .= "<ul>";
        foreach($_model->errors as $attribute=>$attr_errors) {
            $msg .= "<li>Attribute: $attribute</li>";
            $msg .= "<ul>";
            foreach($attr_errors as $attr_error)
                $msg .= "<li>$attr_error</li>";
            $msg .= "</ul>";
        }
        $msg .= "</ul>";
        return $msg;
    }

    private function _checkAuth()
    {
        if(!(isset($_SERVER['HTTP_USERNAME']) && isset($_SERVER['HTTP_PASSWORD']))) {
            $this->_sendResponse(401,'Unauthorized');
        }
        $username = $_SERVER['HTTP_USERNAME'];
        $password = $_SERVER['HTTP_PASSWORD'];
        // Find the user

        if($username != 'apiuser') {
            $this->_sendResponse(401, 'Error: User Name is invalid');
        } else if($password != 'pass') {
            $this->_sendResponse(401, 'Error: User Password is invalid');
        }
    }
}
