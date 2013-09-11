<?php

class extjsHelper
{
    public static function ext_getAppNamespace() {
        return Yii::app()->params['extjs_app_namespace'];
    }

    public static function ext_getNamespace() {
        $args = func_get_args();
        return func_num_args() ? implode('.', $args) : '';
    }

    public static function ext_getSuffix() {
        $args = func_get_args();
        return func_num_args() ? implode('_', $args) : '';
    }

    public static function ext_getHelper() {
        return new Extjs4Plugin(array('theme'=>'blue'));
    }

    public static function ext_getSelectedColumnModelFields($fields) {
        $selected_fields = array();
        foreach($fields as $key => $field) {
            if ($field['gridColumn']) {
                $selected_fields[] = $key;
            }
        }
        return $selected_fields;
    }

    public static function ext_getSelectedColumnModelFieldWidths($fields) {
        $selected_field_widhts = array();
        foreach($fields as $key => $field) {
            if ($field['gridColumn']) {
                $selected_field_widhts[] = $field['gridColumnWidth'];
            }
        }
        return $selected_field_widhts;
    }

    public static function ext_appendColumnModelFields(&$cm_fields, $fields) {
        $cm_field_keys = self::ext_getSelectedColumnModelFields($fields);
        $cm_field_widths = self::ext_getSelectedColumnModelFieldWidths($fields);
        foreach($cm_field_keys as $key => $pos) {
            $field = $fields[$pos];
            $field_config = array(
               'header'=>$field['header'],
               'dataIndex'=>$field['name'],
               'align'=>$field['align'],
               'width'=>$cm_field_widths[$key],
               'sortable'=>(isset($field['sortDir'])) ? !empty($field['sortDir']) && (isset($field['sortable']) ? $field['sortable']:true) : false,
               'groupable'=>$field['groupable'],
            );
            if (!empty($field['xtype'])) $field_config['xtype'] = $field['xtype'];
            if (!empty($field['renderer'])) $field_config['renderer'] = "function(value, metadata, record, rowIndex, colIndex, store){".$field['renderer']."}";
            if (!empty($field['tpl'])) $field_config['tpl'] = $field['tpl'];
            if (!empty($field['editor'])) $field_config['editor'] = $field['editor'];
            if (!empty($field['css'])) $field_config['css'] = $field['css'];
            if (!empty($field['fixed'])) $field_config['fixed'] = $field['fixed'];
            if (!empty($field['menuDisabled'])) $field_config['menuDisabled'] = $field['menuDisabled'];
            if (!empty($field['resizable'])) $field_config['resizable'] = true;
            if (!empty($field['cellActions'])) $field_config['cellActions'] = $field['cellActions'];
            if (!empty($field['id'])) $field_config['id'] = $field['id'];

            $cm_fields[] = $field_config;
        }
    }

    public static function ext_defineGridFilters($fields) {
        $filters = array();
        foreach($fields as $field) {
            if ($field['type']) {
                $filter = array(
                   'type'=>$field['type'],
                   'dataIndex'=>$field['name'],
                );
                $filters[] = $filter;
            }
        }
        return $filters;
    }

    public static function getModelFields($fields) {
        $config = array();
        foreach($fields as $field) {
            $field_config = array();
            $field_config['name'] = $field['name'];
//            $field_config['mapping'] = $field['mapping'];
            if (isset($field['type']) && $field['type']) $field_config['type'] = $field['type'];
            //if ($field['sortType']) $field_config['sortType'] = $field['sortType'];
            //if ($field['sortDir']) $field_config['sortDir'] = $field['sortDir'];
            if (isset($field['format']) && $field['format']) {
                if (isset($field['type']) && $field['type']=='date') {
                    $field_config['dateFormat'] = 'timestamp';
                } else {
                    $field_config['format'] = $field['format'];
                }
            }
            if (isset($field['defaultValue']) && $field['defaultValue']) $field_config['defaultValue'] = $field['defaultValue'];
            if (isset($field['useNull'])) $field_config['useNull'] = $field['useNull'];
            $config[] = $field_config;
        }
        return $config;
    }

    public static function getColumnFields($fields) {
        $ext = extjsHelper::ext_getHelper();
        $config = array();
        foreach($fields as $field) {
            if ($field['gridColumn']) {
                $field_config = array();
                $field_config['name'] = $field['name'];
                $field_config['header'] = $field['header'];
                $field_config['dataIndex'] = $field['name'];
                //if (isset($field['type']) && $field['type'] && $field['type'] != 'string') $field_config['xtype'] = $field['type'].'column';

                if (isset($field['type']) && $field['type']){
                    switch ($field['type']){
                        case 'string':
                            break;
                        case 'int':
                        case 'float':
                            $field_config['xtype'] = 'numbercolumn';
                            break;
                        default:
                            $field_config['xtype'] = $field['type'].'column';
                    }

                }

                $field_config['resizable'] = true;

                if (isset($field['xtype']) && $field['xtype']) $field_config['xtype'] = $field['xtype'];
                if (isset($field['flex']) && $field['flex']) $field_config['flex'] = $field['flex'];
                if (isset($field['width']) && $field['width']) $field_config['width'] = $field['width'];
                if (isset($field['format']) && $field['format']) $field_config['format'] = $field['format'];
                if (isset($field['tpl'])) $field_config['tpl'] = $field['tpl'];
                if (isset($field['align']) && $field['align']) $field_config['align'] = $field['align'];
                if (isset($field['sortType']) && $field['sortType']) $field_config['sortType'] = $field['sortType'];
                if (isset($field['sortDir']) && $field['sortDir']) $field_config['sortDir'] = $field['sortDir'];
                if (isset($field['renderer']) && $field['renderer']) $field_config['renderer'] = $field['renderer'];
                if (isset($field['filter']) && $field['filter']) $field_config['filter'] =$ext->asAnonymousClass($field['filter']);
                //if (isset($field['items']) && $field['items']) $field_config['items'] = /*$ext->asAnonymousClass($field['items'])*/'teszt';

                if (isset($field['hideable'])) $field_config['hideable'] = $field['hideable'];
                if (isset($field['sortable'])) $field_config['sortable'] = $field['sortable'];
                if (isset($field['groupable'])) $field_config['groupable'] = $field['groupable'];
                if (isset($field['resizable'])) {
                    if ($field['resizable'] == '') $field['resizable']=true;
                    $field_config['resizable'] = $field['resizable'];
                }

                if (isset($field['dateFormat']) && $field['dateFormat']) $field_config['dateFormat'] = $field['dateFormat'];
                if (isset($field['editor']) && $field['editor']) $field_config['editor'] = $field['editor'];

                if (isset($field['summaryType']) && $field['summaryType']) $field_config['summaryType'] = $field['summaryType'];
                if (isset($field['summaryRenderer']) && $field['summaryRenderer']) $field_config['summaryRenderer'] = $field['summaryRenderer'];

                if (isset($field['editor']) && $field['editor']) {
                    $field_config['editor'] = $field['editor'];
                }

                if (isset($field['type']) && $field['type'] == 'boolean') {
                    $field_config['trueText'] = Yii::t('msg', 'igen');
                    $field_config['falseText'] = Yii::t('msg', 'nem');
                }
                $config[] = $field_config;
            }
        }
        return $config;
    }

    public static function getHtmlColumnFields($fields) {
        $config = array();
        foreach($fields as $field) {
            if ($field['gridColumn']) {
                $field_config = array();
                $field_config['name'] = $field['name'];
                $field_config['header'] = $field['header'];
                $field_config['dataIndex'] = $field['name'];
                if (isset($field['type']) && $field['type'] && $field['type'] != 'string') $field_config['xtype'] = $field['type'].'column';
                if (isset($field['xtype']) && $field['xtype']) $field_config['xtype'] = $field['xtype'];
                if (isset($field['flex']) && $field['flex']) $field_config['flex'] = $field['flex'];
                if (isset($field['width']) && $field['width']) $field_config['width'] = $field['width'];
                if (isset($field['align']) && $field['align']) $field_config['align'] = $field['align'];
                if (isset($field['htmlRenderer']) && $field['htmlRenderer']) $field_config['htmlRenderer'] = $field['htmlRenderer'];

                if (isset($field['summaryHtmlRenderer']) && $field['summaryHtmlRenderer']) $field_config['summaryHtmlRenderer'] = $field['summaryHtmlRenderer'];

                $config[] = $field_config;
            }
        }
        return $config;
    }

    public static function getXlsColumnFields($fields) {
        $config = array();
        foreach($fields as $field) {
            if ($field['gridColumn']) {
                $field_config = array();
                $field_config['name'] = $field['name'];
                $field_config['header'] = $field['header'];
                $field_config['dataIndex'] = $field['name'];
                if (isset($field['xtype']) && $field['xtype']) $field_config['xtype'] = $field['xtype'];
                if (isset($field['xlstype']) && $field['xlstype']) $field_config['xlstype'] = $field['xlstype'];
                if (isset($field['align']) && $field['align']) $field_config['align'] = $field['align'];
                if (isset($field['xlsFormatter']) && $field['xlsFormatter']) $field_config['xlsFormatter'] = $field['xlsFormatter'];
                if (isset($field['xlsRenderer']) && $field['xlsRenderer']) $field_config['xlsRenderer'] = $field['xlsRenderer'];
                if (isset($field['related_fields']) && !empty($field['related_fields'])) $field_config['related_fields'] = $field['related_fields'];

                $config[] = $field_config;
            }
        }
        return $config;
    }


    public static function getSorterFields($fields) {
        $config = array();
        foreach($fields as $field) {
            if (isset($field['sortDir']) && $field['sortDir']) {
                $field_config = array();
                $field_config['property'] = $field['name'];
                $field_config['direction'] = $field['sortDir'];
                $config[] = $field_config;
            }
        }
        return $config;
    }

    public static function ext_defaultPagingToolbarConfig() {
        return array(
            'store'=>'',
            'displayInfo'=>true,
            'displayMsg'=>Yii::t('msg','Találat: {0} - {1} összesen: {2}'),
            'emptyMsg'=>Yii::t('msg','Nincs megjeleníthető adat'),
            'beforePageText'=>Yii::t('msg','Oldal'),
            'afterPageText'=>Yii::t('msg',' / {0}'),
            'firstText'=>Yii::t('msg','Első'),
            'lastText'=>Yii::t('msg','Utolsó'),
            'nextText'=>Yii::t('msg','Következő'),
            'prevText'=>Yii::t('msg','Előző'),
            'refreshText'=>Yii::t('msg','Frissítés'),
            'plugins' => array(array(
                'ptype' => 'pagesize',
                'beforeText' => '',
                'afterText' => Yii::t('msg','/oldal')
            )),
            'items'=>array(),
        );
    }

    public static function ext_defaultGroupingViewConfig() {
        return array(
            'forceFit'=>true,
            'hideGroupedColumn'=>true,
            'enableGroupingMenu'=>false,
            'showGroupName'=>true,
            'enableNoGroups'=>false,
            'startCollapsed'=>false,
            'columnsText'=>Yii::t('msg','Oszlopok'),
            'emptyGroupText'=>Yii::t('msg','(csoportosítás nélkül)'),
            'groupByText'=>Yii::t('msg','Csoportosítás'),
            'showGroupsText'=>Yii::t('msg','Csoportosítva'),
            'sortAscText'=>Yii::t('msg','Növekvő'),
            'sortDescText'=>Yii::t('msg','Csökkenő'),
        );
    }

    public static function ext_defaultViewConfig() {
        return array(
            'forceFit'=>true,
            'columnsText'=>Yii::t('msg','Oszlopok'),
            'emptyGroupText'=>Yii::t('msg','(csoportosítás nélkül)'),
        );
    }

    public static function ext_defineRowActionConfig($name, $tooltip, $source, $width = 15) {
        return array(
            'header'=>'',
            'width'=>$width+5,
            'actions'=>array(
                'name'=>$name,
                'iconCls'=>'icon-'.$name,
                'style'=>"width:".$width."px;",
                'tooltip'=>$tooltip,
            ),
            'callbacks'=>array(
                "'icon-".$name."'"=>"function(grid, record, action, row, col){".$source."}",
            ),
        );
    }

    public static function ext_defaultRowSelectionModelConfig() {
        return array('singleSelect'=>true);
    }

    public static function ext_defineButton($iconCls, $text, $handler, $pressed = false, $enableToggle = false, $tooltip = '', $id = '', $disabled = false, $other = array()) {
        $btn = array(
            'xtype'=>'button',
            'iconCls'=>$iconCls,
            'text'=>$text,
            'handler'=>$handler,
            'pressed'=>$pressed,
            'enableToggle'=>$enableToggle,
            'tooltip'=>$tooltip,
            'disabled'=>$disabled,
        );

        foreach ($other as $key => $value) {
            $btn[$key] = $value;
        }

        if ($id != '') $btn['id'] = $id;
        return $btn;
    }

    public static function ext_getPagerMaxPerPage($max_per_page = null) {
        return $max_per_page ? $max_per_page : Yii::app()->params['extjs_pager_max_per_page'];
    }
}
