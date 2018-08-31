var SortableGridView = (function($) {

    /**
     * Конструктор объекта-контейнера
     *
     * @param $wrapper обертка, внутри которой лежит таблица и спомогательные блоки
     * @constructor
     */
    var SortableContainer = function($wrapper) {
        this.$wrapper = $wrapper;
        this.$errorBlock = this.$wrapper.find('.error-summary');
        this.$table = this.$wrapper.find('tbody');
        this.$dummyLink = this.$wrapper.find('#sort-dummy-' + this.$wrapper.attr('id'));
        this.url = this.$dummyLink.attr('data-sort_url');
        this.isPjax = $wrapper.attr('data-is_pjax');
    }

    /**
     * Устанавливает сообщение об ошибке сортировки в специальный блок
     * @param jqXHR
     * @deprecated из-за того что, отказались от чистой AJAX-загрузки.
     */
    SortableContainer.prototype.setError = function (jqXHR) {
        if (jqXHR.responseJSON) {
            errorText = jqXHR.responseJSON.message;
        } else {
            errorText = jqXHR.responseText;
        }
        this.$errorBlock.addClass('alert alert-error');
        this.$errorBlock.text(errorText);
    }

    /**
     * Очищает блок с ошибками сортировки
     * @deprecated из-за того что, отказалиьс от чистой AJAX-загрузки.
     */
    SortableContainer.prototype.clearError = function () {
        this.$errorBlock.removeClass('alert alert-error');
        this.$errorBlock.text('');
    }

    /**
     * Если включен режим Pjax, то дергается событие у ссылки-пустышки и отправляется AJAX-запрос.
     * Иначе производится прямой переход по ссылке
     * @param $tr
     */
    SortableContainer.prototype.swap = function ($tr) {
        var url = this.url
            + '?currentKey=' + encodeURIComponent($tr.attr('data-key'))
            + '&previousKey=' + encodeURIComponent(($tr.prev().attr('data-key') || null))
            + '&nextKey=' + encodeURIComponent(($tr.next().attr('data-key') || null));

        this.$dummyLink.attr(
            'href',
            url
        );

        if (this.isPjax == 1) {
            this.$dummyLink.trigger('click');
        } else {
            window.location.href = url;
        }
    }

    /**
     * Класс, отвечающий за drug-n-drop сортировку
     * @param $wrapper
     * @param url
     * @constructor
     */
    var SortableGridView = function ($wrapper, url) {

        this.$container = new SortableContainer($wrapper, url);

        var $container = this.$container;

        this.$container.$table.sortable({
            cursor: "move",
            revert: 200,
            axis: "y",
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },
            update: function(event, ui) {
                $container.swap(ui.item);
            },
            placeholder: "tr-placeholder-highlight",
            start: function (event, ui) {
                ui.item.addClass('tr-drug-highlight');
                // Изменяем высоту подложк - чуть больше высоты перетаскиваемой строки
                $('.tr-placeholder-highlight').height(ui.item.height() * 1.1);
            },
            stop: function (event, ui) {
                ui.item.removeClass('tr-drug-highlight');
            },
        });
        this.$container.$table.disableSelection();
    }

    return SortableGridView;
})(jQuery);