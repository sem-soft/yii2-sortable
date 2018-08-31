<?php
/**
 * Файл класса DragDropMoveAction.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\actions;

use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\Response;
use sem\sortable\builders\queries\DragDropQueryBuilder;

/**
 * Реализует логику перемещениясущности в произвольное место списка по сортировочному атрибуту этой сущности и возможному фильтру.
 * Пример настройки компонента.
 *
 * ```php
 *  public function actions()
 *  {
 *
 *  return [
 *      ...
 *      'swap' => [
 *          'class' => 'common\modules\control\actions\sorter\DropMoveAction',
 *          'modelClass' => Document::class,
 *          'filter' => function (Query $query, Document $model) {
 *              if ($model->document_group_id) {
 *                  $query->andWhere([
 *                      'document_group_id' => $model->document_group_id
 *                  ]);
 *              }
 *          }
 *      ],
 *      ...
 *  ];
 * }
 * ```
 * @property-read bool isClearAjax
 * @todo Предусмотреть вариант, когда первичные ключи моделей - серриализованы в json в случае с составным ключом
 */
class DragDropMoveAction extends Action
{
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
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->modelClass) || !class_exists($this->modelClass)) {
            throw new InvalidConfigException('Некорректно настроен класс модели.');
        }

        if (empty($this->attribute)) {
            throw new InvalidConfigException('Требуется указать атрибут сортировки.');
        }

        $this->response = Yii::$app->response;
        $this->request = Yii::$app->request;

        if ($this->isClearAjax) {
            $this->response->format = Response::FORMAT_JSON;
        }
    }

    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function run()
    {
        $currentKey = Yii::$app->request->get('currentKey', null);
        $previousKey = Yii::$app->request->get('previousKey', null);
        $nextKey =  Yii::$app->request->get('nextKey', null);

        if (!$currentModel = $this->findModel($currentKey)) {
            return $this->errorResponse("Сортируемая модель не найдена или была удалена ранее");
        }

        $previousModel = $this->findModel($previousKey);
        $nextModel = $this->findModel($nextKey);

        if ((!$previousModel) && (!$nextModel)) {
            return $this->errorResponse("Предыдущий или последующий элемент должен существовать");
        }

        try {
            $builder = new DragDropQueryBuilder();
            $builder->setModel($currentModel);
            $builder->setNextModel($nextModel);
            $builder->setPreviousModel($previousModel);
            $builder->setFilter($this->filter);

            $currentModel->updateAttributes([
                $this->attribute => $builder->getQuery()->scalar()
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->successResponse();
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
     * @return ActiveRecord
     */
    protected function findModel($key)
    {
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        return $modelClass::findOne($key);
    }
}