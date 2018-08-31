<?php
/**
 * Файл класса StepMoveAction.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\actions;

use yii\base\Action;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;
use sem\sortable\factories\StepQueryBuilderFactory;

/**
 * Реализует логику действия смещения сущности в списке на один шаг.
 * Примеры конфигурации действия в рамках контроллеров
 * ```php
 *  public function actions()
 *  {
 *
 *      $sortFilter = function (Query $query, Document $model) {
 *          if ($model->document_group_id) {
 *              $query->andWhere([
 *                  'document_group_id' => $model->document_group_id
 *              ]);
 *          }
 *      };
 *
 *      return [
 *          ...
 *          'up' => [
 *              'class' => 'sem\sortable\actions\StepMoveAction',
 *              'modelClass' => Document::class,
 *              'direction' => MoveDirection::DOWN,
 *              'filter' => $sortFilter
 *          ],
 *          'down' => [
 *              'class' => 'sem\sortable\actions\StepMoveAction',
 *              'modelClass' => Document::class,
 *              'direction' => MoveDirection::UP,
 *              'filter' => $sortFilter
 *          ],
 *          ...
 *      ];
 *  }
 * ```
 */
class StepMoveAction extends Action
{
    /**
     * @var string Позиция смещения
     */
    public $direction;
    /**
     * @var string Класс смещаемой модели
     */
    public $modelClass;
    /**
     * @var string Наименование атрибута порядка
     */
    public $attribute = 'sort';
    /**
     * @var array|callable Фильтр для выбора участников сортировки
     */
    public $filter;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!$this->direction) {
            throw new InvalidConfigException('Не указано направление смещения');
        }
        if (!$this->modelClass || !class_exists($this->modelClass)) {
            throw new InvalidConfigException('Не указан класс смещаемой модели');
        }
        if (!$this->attribute) {
            throw new InvalidConfigException('Не указано название атрибута порядка модели');
        }
    }

    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function run($id)
    {
        $model = $this->findModel($id);
        $queryBuilder = StepQueryBuilderFactory::getInstance($model, $this->direction, $this->attribute);
        $queryBuilder->setFilter($this->filter);
        $model->updateAttributes([
            $this->attribute => $queryBuilder->getQuery()->scalar()
        ]);

        return $this->controller->goBack();
    }

    /**
     * Поиск модели для смены позиции
     *
     * @param integer $id
     * @return ActiveRecord
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        if ($model = $modelClass::findOne($id)) {
            return $model;
        }
        throw new NotFoundHttpException("Перемещаемый объект не найден или был удален ранее");
    }
}