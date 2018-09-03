<?php
/**
 * Файл класса StepMoveAction.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\actions;

use yii\base\Exception;
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
class StepMoveAction extends MoveAction
{
    /**
     * @var string Позиция смещения
     */
    public $direction;

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
    }

    /**
     * @inheritdoc
     * @throws \yii\web\BadRequestHttpException
     */
    public function run($id)
    {
        if (!$model = $this->findModel($id)) {
            return $this->errorResponse("Сортируемая модель не найдена или была удалена ранее");
        }
        try {
            $queryBuilder = StepQueryBuilderFactory::getInstance($model, $this->direction, $this->attribute);
            $queryBuilder->setFilter($this->filter);
            $model->updateAttributes([
                $this->attribute => $queryBuilder->getQuery()->scalar()
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->successResponse();
    }
}