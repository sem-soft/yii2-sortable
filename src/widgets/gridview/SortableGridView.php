<?php
/**
 * Файл класса SortableGridViewAsset.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\widgets\gridview;

use Yii;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\base\InvalidConfigException;

/**
 * Виджет реализует вывод сортируемой таблицы сущностей на основе @see GridView
 * Примеры подключения и конфигурации в представлениях
 * 1. Использование без технологии Pjax - с перезагрузкой всей страницы
 * ```php
 *  <?= SortableGridView::widget([
 *      'sortActionRoute' => ['swap'],
 *      'dataProvider' => $dataProvider,
 *      'columns' => [
 *          ['class' => 'yii\grid\SerialColumn'],
 *          ...
 *      ],
 *  ]); ?>
 * ```
 * 2. Использование с технологией Pjax - обновляется только табличное пространство
 * ```php
 *  <?php \yii\widgets\Pjax::begin();?>
 *  <?= SortableGridView::widget([
 *      'sortActionRoute' => ['swap'],
 *      'dataProvider' => $dataProvider,
 *      'columns' => [
 *          ['class' => 'yii\grid\SerialColumn'],
 *          ...
 *      ],
 *  ]); ?>
 *  <?php \yii\widgets\Pjax::end();?>
 * ```
 */
class SortableGridView extends GridView
{
    /**
     * @var string
     */
    public $layout = "{sortErrorsBlock}\n{summary}\n{items}\n{pager}";

    /**
     * @var array|string роут действия по изменению сортировки
     */
    public $sortActionRoute;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (!$this->sortActionRoute) {
            throw new InvalidConfigException('Route to sorting action "sortActionRoute" must be specified');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        parent::run();
        $this->registerClientJs();
    }

    /**
     * Регистрация клиентского JS-скрипта
     */
    protected function registerClientJs()
    {
        $view = $this->getView();
        SortableGridViewAsset::register($view);
        $view->registerJs("new SortableGridView($('#{$this->options['id']}'))", View::POS_END);
    }

    /**
     * @inheritdoc
     */
    public function renderSection($name)
    {
        switch ($name) {
            case '{sortErrorsBlock}':
                return $this->renderSortErrorsBlock();
            default:
                return parent::renderSection($name);
        }
    }

    /**
     * Отрисовывает пустой блок для сообщений об ошибках сортировки
     * @return string
     */
    public function renderSortErrorsBlock()
    {
        $class = "";

        if ($error = Yii::$app->session->getFlash('sort-error', '')) {
            $class = " alert alert-error";
        }

        return Html::tag('div', $error, [
            'class' => "error-summary{$class}",
        ]);
    }

    /**
     * Добавляем в рендер ссылку-куклу с URL-адресом для ресорта сущностей
     * {@inheritdoc}
     */
    public function renderCaption()
    {
        $sortUrl = Url::to($this->sortActionRoute);
        $dummyLink = Html::a('', '', [
            'id' => "sort-dummy-{$this->options['id']}",
            'data' => [
                'sort_url' => $sortUrl
            ],
            'style' => [
                'display' => 'none'
            ]
        ]);

        if (!$parent = parent::renderCaption()) {
            return $dummyLink;
        } else {
            return $parent . $dummyLink;
        }
    }
}