<?php
/**
 * Файл класса MoveAction.php
 *
 * @copyright Copyright (c) 2018, Oleg Chulakov Studio
 * @link http://chulakov.com/
 */

namespace sem\sortable\actions;


use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;

abstract class MoveAction extends Action
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string Класс модели
     */
    public $modelClass;

    /**
     * @var string Атрибут сортировки
     */
    public $attribute = 'sort';

    /**
     * @var array|callable Фильтр для выбора участников сортировки
     */
    public $filter;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->response = Yii::$app->response;
        $this->request = Yii::$app->request;

        if ($this->isClearAjax) {
            $this->response->format = Response::FORMAT_JSON;
        }

        if (empty($this->modelClass) || !class_exists($this->modelClass)) {
            throw new InvalidConfigException('Некорректно настроен класс модели.');
        }

        if (empty($this->attribute)) {
            throw new InvalidConfigException('Требуется указать атрибут сортировки.');
        }
    }

    /**
     * Генерирует неудачный вариант ответа
     * @param string $message
     * @return mixed
     * @throws BadRequestHttpException
     */
    protected function errorResponse($message)
    {
        if ($this->isClearAjax) {
            throw new BadRequestHttpException($message);
        }

        Yii::$app->session->setFlash('sort-error', $message);

        return $this->response->redirect(Yii::$app->getUser()->getReturnUrl(), 302, !$this->isClearAjax ? false : true);
    }

    /**
     * Генерирует удачный вариант ответа
     * @return mixed
     */
    protected function successResponse()
    {
        if ($this->isClearAjax) {
            return [
                'success' => true
            ];
        }

        return $this->response->redirect(Yii::$app->getUser()->getReturnUrl(), 302, !$this->isClearAjax ? false : true);
    }

    /**
     * Выполянет проверку, является ли запрос чисто AJAX или используется технология Pjax
     * @return bool
     */
    protected function getIsClearAjax()
    {
        return !$this->request->isPjax && $this->request->isAjax;
    }

    /**
     * Производит поиск модели по первичному ключу
     * @param mixed $key
     * @return ActiveRecord|null
     */
    protected function findModel($key)
    {
        if (!is_array($key)) {
            // Декодим json, если пришел составной ключ, либо строку, если пришло значение простого идентификатора
            $key = Json::decode($key);
        }

        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;

        $tableName = $modelClass::tableName();

        $tablePk = $modelClass::primaryKey();

        $pk = [];

        if (ArrayHelper::isAssociative($key)) {

            foreach ($tablePk as $field) {

                if (!isset($key[$field])) {
                    $this->errorResponse("Неверное значение для первичного ключа");
                }

                $pk[$tableName . '.' . $field] = $key[$field];
            }

        } else {

            $pk[$tableName . '.' . $tablePk[0]] = $key;

        }

        return $modelClass::find()
            ->andWhere($pk)
            ->one();
    }
}