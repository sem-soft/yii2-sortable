<?php
/**
 * Файл класса SortableGridViewAsset.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\widgets\gridview;

use yii\web\AssetBundle;

/**
 * Бандл ассетов, необходимых для работы виджета @see SortableGridView
 */
class SortableGridViewAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@sem/sortable/widgets/gridview/assets';

    /**
     * @var array
     */
    public $js = [
        'js/sortable-gridview.js'
    ];

    /**
     * @var array
     */
    public $css = [
        'css/sortable-gridview.css'
    ];

    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset'
    ];
}
