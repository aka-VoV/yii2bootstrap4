<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace digitv\bootstrap\widgets;

use digitv\bootstrap\assets\BootstrapPluginAsset;
use digitv\bootstrap\Html;
use digitv\bootstrap\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

/**
 * Tabs renders a Tab bootstrap javascript component.
 *
 * For example:
 *
 * ```php
 * echo Tabs::widget([
 *     'items' => [
 *         [
 *             'label' => 'One',
 *             'content' => 'Anim pariatur cliche...',
 *             'active' => true
 *         ],
 *         [
 *             'label' => 'Two',
 *             'content' => 'Anim pariatur cliche...',
 *             'headerOptions' => [...],
 *             'options' => ['id' => 'myveryownID'],
 *         ],
 *         [
 *             'label' => 'Example',
 *             'url' => 'http://www.example.com',
 *         ],
 *         [
 *             'label' => 'Dropdown',
 *             'items' => [
 *                  [
 *                      'label' => 'DropdownA',
 *                      'content' => 'DropdownA, Anim pariatur cliche...',
 *                  ],
 *                  [
 *                      'label' => 'DropdownB',
 *                      'content' => 'DropdownB, Anim pariatur cliche...',
 *                  ],
 *                  [
 *                      'label' => 'External Link',
 *                      'url' => 'http://www.example.com',
 *                  ],
 *             ],
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @see http://getbootstrap.com/javascript/#tabs
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @since 2.0
 */
class Tabs extends Widget
{
    /**
     * @var array list of tabs in the tabs widget. Each array element represents a single
     * tab with the following structure:
     *
     * - label: string, required, the tab header label.
     * - encode: boolean, optional, whether this label should be HTML-encoded. This param will override
     *   global `$this->encodeLabels` param.
     * - headerOptions: array, optional, the HTML attributes of the tab header.
     * - linkOptions: array, optional, the HTML attributes of the tab header link tags.
     * - content: string, optional, the content (HTML) of the tab pane.
     * - url: string, optional, an external URL. When this is specified, clicking on this tab will bring
     *   the browser to this URL. This option is available since version 2.0.4.
     * - options: array, optional, the HTML attributes of the tab pane container.
     * - active: boolean, optional, whether this item tab header and pane should be active. If no item is marked as
     *   'active' explicitly - the first one will be activated.
     * - visible: boolean, optional, whether the item tab header and pane should be visible or not. Defaults to true.
     * - items: array, optional, can be used instead of `content` to specify a dropdown items
     *   configuration array. Each item can hold three extra keys, besides the above ones:
     *     * active: boolean, optional, whether the item tab header and pane should be visible or not.
     *     * content: string, required if `items` is not set. The content (HTML) of the tab pane.
     *     * contentOptions: optional, array, the HTML attributes of the tab content container.
     */
    public $items = [];
    /**
     * @var array list of HTML attributes for the item container tags. This will be overwritten
     * by the "options" set in individual [[items]]. The following special options are recognized:
     *
     * - tag: string, defaults to "div", the tag name of the item container tags.
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $itemOptions = [];
    /**
     * @var array list of HTML attributes for the header container tags. This will be overwritten
     * by the "headerOptions" set in individual [[items]].
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $headerOptions = [];
    /**
     * @var array list of HTML attributes for the tab header link tags. This will be overwritten
     * by the "linkOptions" set in individual [[items]].
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $linkOptions = [];
    /**
     * @var boolean whether the labels for header items should be HTML-encoded.
     */
    public $encodeLabels = true;
    /**
     * @var string specifies the Bootstrap tab styling.
     */
    public $navType = 'nav-tabs';
    /**
     * @var boolean whether to render the `tab-content` container and its content. You may set this property
     * to be false so that you can manually render `tab-content` yourself in case your tab contents are complex.
     * @since 2.0.1
     */
    public $renderTabContent = true;
    /**
     * @var array list of HTML attributes for the `tab-content` container. This will always contain the CSS class `tab-content`.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     * @since 2.0.7
     */
    public $tabContentOptions = [];
    /**
     * @var string name of a class to use for rendering dropdowns withing this widget. Defaults to [[Dropdown]].
     * @since 2.0.7
     */
    public $dropdownClass = 'digitv\bootstrap\widgets\Dropdown';

    private $hasDropDown = false;


    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();
        $this->options['role'] = 'tablist';
        Html::addCssClass($this->options, ['widget' => 'nav', $this->navType]);
        Html::addCssClass($this->tabContentOptions, 'tab-content');
        Html::addCssClass($this->headerOptions, 'nav-item');
        Html::addCssClass($this->linkOptions, 'nav-link');
    }

    /**
     * Renders the widget.
     */
    public function run()
    {
        $items = $this->renderItems();
        $this->registerPlugin('tab');
        return $items;
    }

    /**
     * Renders tab items as specified on [[items]].
     * @return string the rendering result.
     * @throws InvalidConfigException.
     */
    protected function renderItems()
    {
        $headers = [];
        $panes = [];

        if (!$this->hasActiveTab()) {
            $this->activateFirstVisibleTab();
        }

        foreach ($this->items as $n => $item) {
            if (!ArrayHelper::remove($item, 'visible', true)) {
                continue;
            }
            if (!array_key_exists('label', $item)) {
                throw new InvalidConfigException("The 'label' option is required.");
            }
            $encodeLabel = isset($item['encode']) ? $item['encode'] : $this->encodeLabels;
            $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
            $headerOptions = array_merge($this->headerOptions, ArrayHelper::getValue($item, 'headerOptions', []));
            $linkOptions = array_merge($this->linkOptions, ArrayHelper::getValue($item, 'linkOptions', []));

            if (isset($item['items'])) {
                Html::addCssClass($linkOptions, ['widget' => 'dropdown-toggle']);

                if ($this->renderDropdown($n, $item['items'], $panes)) {
                    Html::addCssClass($linkOptions, 'active');
                }

                Html::addCssClass($linkOptions, ['widget' => 'dropdown-toggle']);
                if (!isset($linkOptions['data-toggle'])) {
                    $linkOptions['data-toggle'] = 'dropdown';
                }

                /** @var Widget $dropdownClass */
                $dropdownClass = $this->dropdownClass;
                $header = Html::a($label, "#", $linkOptions) . "\n"
                    . $dropdownClass::widget(['items' => $item['items'], 'clientOptions' => false, 'view' => $this->getView()]);
                $this->hasDropDown = true;
            } else {
                $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
                $options['id'] = ArrayHelper::getValue($options, 'id', $this->options['id'] . '-tab' . $n);

                Html::addCssClass($options, ['widget' => 'tab-pane']);
                if (ArrayHelper::remove($item, 'active')) {
                    Html::addCssClass($options, 'active show');
                    Html::addCssClass($linkOptions, 'active');
                }

                if (isset($item['url'])) {
                    $header = Html::a($label, $item['url'], $linkOptions);
                } else {
                    if (!isset($linkOptions['data-toggle'])) {
                        $linkOptions['data-toggle'] = 'tab';
                    }
                    $header = Html::a($label, '#' . $options['id'], $linkOptions);
                }

                if ($this->renderTabContent) {
                    $tag = ArrayHelper::remove($options, 'tag', 'div');
                    $panes[] = Html::tag($tag, isset($item['content']) ? $item['content'] : '', $options);
                }
            }

            $headers[] = Html::tag('li', $header, $headerOptions);
        }

        return $this->renderNavTabs($headers, $this->options)
            . $this->renderPanes($panes);
    }

    /**
     * @return bool if there's active tab defined
     */
    protected function hasActiveTab()
    {
        foreach ($this->items as $item) {
            if (isset($item['active']) && $item['active'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the first visible tab as active.
     *
     * This method activates the first tab that is visible and
     * not explicitly set to inactive (`'active' => false`).
     * @since 2.0.7
     */
    protected function activateFirstVisibleTab()
    {
        foreach ($this->items as $i => $item) {
            $active = ArrayHelper::getValue($item, 'active', null);
            $visible = ArrayHelper::getValue($item, 'visible', true);
            if ($visible && $active !== false) {
                $this->items[$i]['active'] = true;
                return;
            }
        }
    }

    /**
     * Normalizes dropdown item options by removing tab specific keys `content` and `contentOptions`, and also
     * configure `panes` accordingly.
     * @param string $itemNumber number of the item
     * @param array $items the dropdown items configuration.
     * @param array $panes the panes reference array.
     * @return bool whether any of the dropdown items is `active` or not.
     * @throws InvalidConfigException
     */
    protected function renderDropdown($itemNumber, &$items, &$panes)
    {
        $itemActive = false;

        foreach ($items as $n => &$item) {
            if (is_string($item)) {
                continue;
            }
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            if (!(array_key_exists('content', $item) xor array_key_exists('url', $item))) {
                throw new InvalidConfigException("Either the 'content' or the 'url' option is required, but only one can be set.");
            }
            if (array_key_exists('url', $item)) {
                continue;
            }

            $content = ArrayHelper::remove($item, 'content');
            $options = ArrayHelper::remove($item, 'contentOptions', []);
            Html::addCssClass($options, ['widget' => 'tab-pane']);
            if (ArrayHelper::remove($item, 'active')) {
                Html::addCssClass($options, 'active');
                Html::addCssClass($item['options'], 'active');
                $itemActive = true;
            }

            $options['id'] = ArrayHelper::getValue($options, 'id', $this->options['id'] . '-dd' . $itemNumber . '-tab' . $n);
            $item['url'] = '#' . $options['id'];
            if (!isset($item['linkOptions']['data-toggle'])) {
                $item['linkOptions']['data-toggle'] = 'tab';
            }
            $panes[] = Html::tag('div', $content, $options);

            unset($item);
        }

        return $itemActive;
    }

    /**
     * Renders tab panes.
     *
     * @param array $panes
     * @return string the rendering result.
     * @since 2.0.7
     */
    public function renderPanes($panes)
    {
        return $this->renderTabContent ? "\n" . Html::tag('div', implode("\n", $panes), $this->tabContentOptions) : '';
    }

    protected function renderNavTabs($items = [], $options = []) {
        return Nav::widget([
            'items' => $items,
            'options' => $options,
        ]);
    }

    /**
     * Registers a specific Bootstrap plugin and the related events
     * @param string $name the name of the Bootstrap plugin
     */
    protected function registerPlugin($name)
    {
        $view = $this->getView();

        BootstrapPluginAsset::register($view);

        if($this->hasDropDown && !isset($this->clientEvents['shown.bs.tab'])) {
            //FIX dropdown links
            $this->clientEvents['shown.bs.tab'] = ['a', new JsExpression("function(e) {
            var btn = $(this), nav = btn.parents('.nav:first'), dropDown = btn.parents('.dropdown-menu:first');
                btn.parents('.nav:first').find('[data-toggle=\"tab\"]').not(btn).removeClass('active');
                if(dropDown.length) {
                    dropDown.siblings('.dropdown-toggle').addClass('active');
                }
            }")];
        }

        $this->registerClientEvents();
    }

    /**
     * Registers JS event handlers that are listed in [[clientEvents]].
     * @since 2.0.2
     */
    protected function registerClientEvents()
    {
        if (!empty($this->clientEvents)) {
            $id = $this->options['id'];
            $js = [];
            foreach ($this->clientEvents as $event => $handler) {
                if(is_array($handler) && count($handler) === 2) {
                    $subSelector = $handler[0];
                    $handlerCallback = $handler[1];
                    $js[] = "jQuery('#$id').on('$event', '$subSelector', $handlerCallback);";
                } else {
                    $js[] = "jQuery('#$id').on('$event', $handler);";
                }
            }
            $this->getView()->registerJs(implode("\n", $js));
        }
    }
}
