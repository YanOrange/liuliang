<?php

namespace plugin\curd\library;

use laytp\traits\Error;
use plugin\curd\model\curd\Field;
use plugin\curd\model\curd\Table;
use plugin\curd\model\Migrations;
use think\facade\Config;

class Curd
{
    use Error;
    protected
        $tableId, //要生成的表id
        $needCreateTable, //是否要执行生成数据表结构的命令
        $database, //要生成的表名链接数据库的标识
        $tableName, //要生成的表名
        $tableComment, //要生成的表注释
        $engine, //要生成的表存储引擎
        $collation, //要生成的表字符集
        $isHidePk, //是否隐藏主键列.2=不隐藏,1=隐藏
        $isCreateNumber, //是否生成序号列.2=不生成,1=生成
        $fields, //字段列表
        $midName, //中间名称，比如表名为lt_test_a_b那么这里的midName就是/test/a/B,拼接控制器和模型文件的路径和namespace都需要用到
        $controllerModelClassName, //控制器和模型的类名
        $migrationClassName, //要生成的数据迁移文件类名
        $migrationFileName, //要生成的数据迁移文件名
        $controllerFileName, //要生成的控制器文件名
        $modelFileName, //要生成的模型文件名
        $jsFileName, //要生成的js文件名
        $recycleJsFileName, //要生成的回收站的js文件名
        $htmlIndexFileName, //要生成的首页html文件名
        $htmlAddFileName, //要生成的添加html文件名
        $htmlEditFileName, //要生成的编辑html文件名
        $htmlDetailFileName, //要生成的查看html文件名
        $htmlRecycleFileName, //要生成的回收站html文件名
        $migrationParam, //生成数据迁移文件，模板需要用到的参数数组
        $controllerParam, //生成controller文件，模板需要用到的参数数组
        $modelParam, //生成model文件，模板需要用到的参数数组
        $jsParam, //生成js文件，模板需要用到的参数数组
        $recycleJsParam, //生成回收站js文件，模板需要用到的参数数组
        $htmlIndexParam, //生成index.html，模板需要用到的参数数组
        $htmlAddParam, //生成add.html，模板需要用到的参数数组
        $htmlEditParam, //生成edit.html，模板需要用到的参数数组
        $htmlDetailParam, //生成detail.html，模板需要用到的参数数组，暂时屏蔽
        $htmlRecycleParam, //生成recycle.html，模板需要用到的参数数组
        $recycleCols, //回收站需要的js字段列表
        $hasSoftDel //是否拥有软删除
    ;

    public function __construct($tableId, $needCreateTable)
    {
        $this->tableId         = $tableId;
        $this->needCreateTable = $needCreateTable;
    }

    public function execute()
    {
        $table = Table::find($this->tableId);
        if (!$table) {
            $this->setError('table_id参数错误');
            return false;
        }

        //这里来删除lt_migrations表对当前表的记录
        $this->database       = $table->database;
        $this->tableName      = $table->table;
        $this->tableComment   = $table->comment;
        $this->engine         = $table->engine;
        $this->collation      = $table->collation;
        $this->isHidePk       = $table->is_hide_pk;
        $this->isCreateNumber = $table->is_create_number;

        $this->fields = Field::where('table_id', '=', $this->tableId)->order(['show_sort' => 'desc', 'id' => 'asc'])->select();
        if (!$this->fields) {
            $this->setError($this->tableName . '还没有字段，请先添加字段');
            return false;
        }

        $this->hasSoftDel = false;

        //是否拥有软删除功能
        foreach ($this->fields as $k => $v) {
            if ($v->field == 'delete_time') {
                $this->hasSoftDel = true;
            }
        }

        $this->setParam();
        $this->create();
        return true;
    }

    /**
     * 设置参数，待生成
     */
    public function setParam()
    {
        $this->setMidFileName();
        $this->setMigrationFileName();
        $this->setFileName();
        $this->setMigrationParam();
        $this->setControllerParam();
        $this->setModelParam();
        $this->setJsParam();
        $this->setHtmlParam();
    }

    /**
     * 生成静态文件、控制器和模型文件
     */
    public function create()
    {
        $this->createMigration();
        $this->createController();
        $this->createModel();
        $this->createJs();
        $this->createHtml();
    }

    /**
     * 设置当前生成的类路径名称
     */
    protected function setMidFileName()
    {
        $arrTable                       = explode('_', $this->tableName);
        $basename                       = ucfirst($arrTable[count($arrTable) - 1]);
        $this->controllerModelClassName = $basename;
        array_shift($arrTable);
        array_pop($arrTable);
        if (count($arrTable)) {
            $strTable = implode('/', $arrTable) . '/' . $basename;
        } else {
            $strTable = $basename;
        }
        $this->midName = $strTable;
    }

    /**
     * 根据表名获取中间名
     * @param $tableName
     * @return string
     */
    protected function getMidName($tableName)
    {
        $arrTable                       = explode('_', $tableName);
        $basename                       = ucfirst($arrTable[count($arrTable) - 1]);
        $this->controllerModelClassName = $basename;
        array_shift($arrTable);
        array_pop($arrTable);
        if (count($arrTable)) {
            $strTable = implode(DS, $arrTable) . '/' . $basename;
        } else {
            $strTable = $basename;
        }
        return $strTable;
    }

    /**
     * 根据表名获取类路径名称
     * @param $table
     * @return string
     */
    protected function getNameByTable($table)
    {
        $arrTable = explode('_', $table);
        $basename = ucfirst($arrTable[count($arrTable) - 1]);
        array_shift($arrTable);//del_db_prefix
        array_pop($arrTable);
        if (count($arrTable)) {
            $strTable = implode('\\', $arrTable) . '\\' . $basename;
        } else {
            $strTable = $basename;
        }
        return $strTable;
    }

    //设置需要生成的数据迁移文件名
    protected function setMigrationFileName()
    {
        if ($this->needCreateTable) {
            $arrTable = explode('_', $this->tableName);
            array_shift($arrTable);
            foreach ($arrTable as $k => $name) {
                $arrTable[$k] = ucfirst($name);
            }
            $this->migrationClassName = implode('', $arrTable);
            $migrations               = new Migrations();
            $migration                = $migrations->where('migration_name', '=', ucfirst($this->migrationClassName))->find();
            if ($migration) {
                $this->migrationFileName = app()->getRootPath() . 'database' . DS . 'migrations' . DS . $migration->version . '_' . lcfirst($migration->migration_name) . '.php';
                $migration->delete();
            } else {
                $this->migrationFileName = app()->getRootPath() . 'database' . DS . 'migrations' . DS . date('YmdHis') . '_' . lcfirst(implode('', $arrTable)) . '.php';
            }
        }
    }

    protected function filterMidName($midName)
    {
        if(substr($midName, 0, 6) === 'admin/'){
            return substr($midName, 6);
        }
        return $midName;
    }

    //设置所有需要生成的文件名
    protected function setFileName()
    {
        $this->controllerFileName  = app()->getAppPath() . 'controller' . DS . 'admin' . DS . $this->filterMidName($this->midName) . '.php';
        $this->modelFileName       = app()->getAppPath() . 'model' . DS . $this->midName . '.php';
        $this->jsFileName          = app()->getRootPath() . 'public' . DS . 'static' . DS . 'admin' . DS . 'js' . DS . $this->filterMidName(strtolower($this->midName)) . '.js';
        $this->recycleJsFileName   = app()->getRootPath() . 'public' . DS . 'static' . DS . 'admin' . DS . 'js' . DS . $this->filterMidName(strtolower($this->midName)) . 'Recycle.js';
        $this->htmlIndexFileName   = app()->getRootPath() . 'public' . DS . 'admin' . DS . $this->filterMidName(strtolower($this->midName)) . DS . 'index.html';
        $this->htmlAddFileName     = app()->getRootPath() . 'public' . DS . 'admin' . DS . $this->filterMidName(strtolower($this->midName)) . DS . 'add.html';
        $this->htmlEditFileName    = app()->getRootPath() . 'public' . DS . 'admin' . DS . $this->filterMidName(strtolower($this->midName)) . DS . 'edit.html';
        $this->htmlRecycleFileName = app()->getRootPath() . 'public' . DS . 'admin' . DS . $this->filterMidName(strtolower($this->midName)) . DS . 'recycle.html';
    }

    //设置生成migration需要的参数
    protected function setMigrationParam()
    {
        $tplName              = 'migration' . DS . 'base';
        $data['className']    = $this->migrationClassName;
        $data['tableName']    = str_replace(Config::get("database.connections." . Config::get("database.default") . ".prefix"), '', $this->tableName);
        $data['engine']       = $this->engine;
        $data['tableComment'] = $this->tableComment;
        $data['collation']    = $this->collation;
        $fields               = '';
        foreach ($this->fields as $field) {
            $fieldData['field']    = $field->field;
            $fieldData['dataType'] = $field->data_type;
//            $fieldData['limit'] = $field->limit;
            $fieldData['null'] = ($field->is_empty == 2) ? 0 : 1;
            if (in_array($field->data_type, ['integer', 'biginteger', 'boolean', 'decimal', 'float'])) {
                $field->default = intval($field->default);
            }
            $fieldData['default'] = (!in_array($field->data_type, ['text', 'datetime', 'timestamp'])) ? ' \'default\' => \'' . $field->default . '\',' : ' ';
            if ($field->form_type == 'radio' || $field->form_type == 'select') {
                $comment = '';
                foreach ($field->addition['text'] as $k => $t) {
                    $comment .= $field->addition['value'][$k] . '=' . $t . ',';
                }
                if (is_string($field->addition['default'])) {
                    $comment .= '默认:' . $field->addition['default'];
                }
                $fieldData['comment'] = $field->comment . '.' . $comment;
            } elseif ($field->form_type == 'checkbox') {
                $comment = '';
                foreach ($field->addition['text'] as $k => $t) {
                    $comment .= $field->addition['value'][$k] . '=' . $t . ',';
                }
                $fieldData['comment'] = $field->comment . '.' . $comment . '默认:' . implode(',', $field->addition['default']);
            } elseif ($field->form_type == 'switch') {
                $comment              = $field->addition['close_value'] . '=' . $field->addition['close_text'] . ',' .
                    $field->addition['open_value'] . '=' . $field->addition['open_text'] .
                    ',默认:' . (($field->addition['default_status'] === 'close') ? $field->addition['close_value'] : $field->addition['open_value']);
                $fieldData['comment'] = $field->comment . '.' . $comment;
            } elseif ($field->form_type == 'xm_select' && $field->addition['data_from_type'] == 'data') {
                $comment = '';
                foreach ($field->addition['text'] as $k => $t) {
                    $comment .= $field->addition['value'][$k] . '=' . $t . ',';
                }
                if ($field->addition['default']) {
                    $comment .= '默认:' . implode(',', $field->addition['default']);
                }
                $fieldData['comment'] = $field->comment . '.' . $comment;
            } else {
                $fieldData['comment'] = $field->comment;
            }
            if (in_array($field->data_type, ["float", "decimal"])) {
                $fieldData['limitPrecisionScale'] = "'precision' => {$field->precision}, 'scale' => {$field->scale}, ";
            } elseif (in_array($field->data_type, ["text", "datetime", 'timestamp'])) {
                $fieldData['limitPrecisionScale'] = '';
            } else {
                $fieldData['limitPrecisionScale'] = "'limit' => {$field->limit}, ";
            }
            $fields .= $this->getReplacedTpl('migration' . DS . 'field', $fieldData) . "\n\t\t\t";
        }
        $data['fields']       = $fields;
        $this->migrationParam = ['tplName' => $tplName, 'data' => $data, 'fileName' => $this->migrationFileName];
    }

    //生成数据迁移文件并执行数据库迁移命令，生成数据表
    protected function createMigration()
    {
        if ($this->needCreateTable) {
            $this->writeToFile($this->migrationParam['tplName'], $this->migrationParam['data'], $this->migrationParam['fileName']);
            sleep(1);
//            system('cd ' . app()->getRootPath() . '&& php think migrate:run > /dev/null', $return);
            exec('php '.app()->getRootPath().'\think migrate:run');
        }
    }

    /**
     * 设置生成controller需要的参数
     * [
     *  'tplName'=>模板名,
     *  'data' => '执行替换模板的key=>value数组',
     *  'fileName' => '要生成的文件名'
     * ]
     */
    protected function setControllerParam()
    {
        $tplName                     = 'controller' . DS . 'base';
        $data['controllerNamespace'] = str_replace('/', '\\', dirname('app/controller/admin/' . $this->filterMidName($this->midName)));
        $data['tableComment']        = $this->tableComment;
        $data['modelName']           = strtolower(str_replace('/', '_', $this->midName));
        $data['modelClassName']      = $this->controllerModelClassName;
        $data['modelNamespace']      = str_replace('/', '\\', dirname('app/model/' . $this->midName));
        $data['controllerClassName'] = $this->controllerModelClassName;
        $data['indexFunction'] = $this->setIndexFunctionController();
        $data['infoFunction'] = $this->setInfoFunctionController();
        $data['recycleFunction'] = $this->setRecycleFunctionController();
        $data['addFunction'] = $this->setAddFunctionController();
        $data['editFunction'] = $this->setEditFunctionController();
        $data['tableEditFunction'] = $this->setTableEditFunctionController();
        $data['useAdminUserServiceFacade'] = '';
        $data['uploadDomainPackage'] = '';
        foreach ($this->fields as $k => $v) {
            if($v['form_type'] === 'admin_user_id'){
                $data['useAdminUserServiceFacade'] = "\nuse app\service\admin\UserServiceFacade;";
            }
            if($v['form_type'] == 'upload' || $v['form_type'] == 'editor'){
                $data['uploadDomainPackage'] = "\nuse laytp\library\UploadDomain;";
            }
        }
        $this->controllerParam       = ['tplName' => $tplName, 'data' => $data, 'fileName' => $this->controllerFileName];
    }

    //在index方法里，如果有关联模型，在查询时需要查询出关联模型数据
    protected function setIndexFunctionController()
    {
        $relationIndexFunctionLt = 'controller' . DS . 'index';
        $data                    = [];
        $data['withRelation']    = "";
        $with                    = [];

        foreach ($this->fields as $k => $v) {
            if ($v['relation']['table']) {
                $with[] = $v['relation']['fun_name'];
            }
            if ($v['form_type'] === 'upload' && $v['addition']['multi'] === 'single') {
                $with[] = $this->underlineToCamel($v['field']) . '_file';
            }
        }

        if ($with) {
            $data['withRelation']    = "->with(['" . implode("','", $with) . "'])";
        }

        $indexFunctionController = $this->getReplacedTpl($relationIndexFunctionLt, $data);
        return $indexFunctionController;
    }

    //在info方法里，如果有上传组件，在查询的时候需要把文件相关的数据查询出来
    protected function setInfoFunctionController(){
        $infoFunctionLt = 'controller' . DS . 'info';
        $data                    = [];
        $data['withRelation']    = "";
        $with                    = [];

        foreach ($this->fields as $k => $v) {
            if ($v['form_type'] === 'upload' && $v['addition']['multi'] === 'single') {
                $with[] = $this->underlineToCamel($v['field']) . '_file';
            }
        }

        if ($with) {
            $data['withRelation']    = "->with(['" . implode("','", $with) . "'])";
        }

        $infoFunctionController = $this->getReplacedTpl($infoFunctionLt, $data);
        return $infoFunctionController;
    }

    //在index方法里，如果有关联模型，在查询时需要查询出关联模型数据
    protected function setRecycleFunctionController()
    {
        $recycleFunctionController = '';
        $with                      = [];
        foreach ($this->fields as $k => $v) {
            if ($v['relation']['table']) {
                $with[] = $v['relation']['fun_name'];
            }
        }
        if ($with) {
            $relationIndexFunctionLt   = 'controller' . DS . 'recycle';
            $data['withRelation']      = "->with(['" . implode("','", $with) . "'])";
            $recycleFunctionController = $this->getReplacedTpl($relationIndexFunctionLt, $data);
        }
        return $recycleFunctionController;
    }

    //富文本编辑器字段，在添加和编辑的控制器方法里面，所有链接要去掉前缀
    protected function setAddFunctionController()
    {
        $arrUploadDomainFields = [];
        $adminUserId = '';
        foreach ($this->fields as $field) {
            if ($field->form_type == 'editor') {
                $arrUploadDomainFields[] = "\$post['{$field->field}'] = UploadDomain::delUploadDomain(\$post['{$field->field}'], '{$field->addition['upload_type']}');";
            }
            if($field->form_type == 'admin_user_id'){
                $adminUserId = "\$post['{$field->field}'] = UserServiceFacade::getUser()->id;";
            }
        }
        $data['uploadDomainFields'] = '';
        if ($arrUploadDomainFields || $adminUserId) {
            $data['uploadDomainFields'] = implode("\n\t\t", $arrUploadDomainFields);
            $data['adminUserId'] = $adminUserId;
            $addFunctionLt              = 'controller' . DS . 'add';
            $addFunctionController      = $this->getReplacedTpl($addFunctionLt, $data);
            return $addFunctionController;
        } else {
            return '';
        }
    }

    //富文本编辑器字段，在添加和编辑的控制器方法里面，所有链接要去掉前缀
    protected function setEditFunctionController()
    {
        $arrUploadDomainFields = [];
        $adminUserId = '';
        foreach ($this->fields as $k => $v) {
            if ($v['form_type'] == 'editor') {
                $arrUploadDomainFields[] = "\$post['{$v['field']}'] = UploadDomain::delUploadDomain(\$post['{$v['field']}'], '{$v['addition']['upload_type']}');";
            }
            if($v['form_type'] == 'admin_user_id'){
                $adminUserId = "\$post['{$v['field']}'] = UserServiceFacade::getUser()->id;";
            }
        }
        $data['uploadDomainFields'] = '';
        if ($arrUploadDomainFields || $adminUserId) {
            $data['uploadDomainFields'] = implode("\n\t\t", $arrUploadDomainFields);
            $data['adminUserId'] = $adminUserId;
            $editFunctionLt             = 'controller' . DS . 'edit';
            $editFunctionController     = $this->getReplacedTpl($editFunctionLt, $data);
            return $editFunctionController;
        } else {
            return '';
        }
    }

    //设置表格编辑方法
    protected function setTableEditFunctionController(){
        $functionContent = [];
        foreach ($this->fields as $k => $v) {
            if (($v['form_type'] == 'input' && isset($v['addition']['open_table_edit']) && $v['addition']['open_table_edit'] == 1) || $v['form_type'] == 'switch') {
                $data['field'] = $v['field'];
                $data['comment'] = $v['comment'];
                $data['funcName'] = ucfirst($this->underlineToCamel($v['field']));
                $tableEditFunctionLt = 'controller' . DS . 'tableEdit';
                $functionContent[]   = $this->getReplacedTpl($tableEditFunctionLt, $data);
            }
        }
        $tableEditFunctionController = implode("\n\t\t", $functionContent);
        return $tableEditFunctionController;
    }

    //生成controller层
    protected function createController()
    {
        $this->controllerParam['data']['hasSoftDel'] = "\n\tprotected \$hasSoftDel=0;//是否拥有软删除功能";
        //是否拥有软删除功能
        foreach ($this->fields as $k => $v) {
            if ($v['field'] == 'delete_time') {
                $this->controllerParam['data']['hasSoftDel'] = "\n\tprotected \$hasSoftDel=1;//是否拥有软删除功能";
                break;
            }
        }

        $this->writeToFile($this->controllerParam['tplName'], $this->controllerParam['data'], $this->controllerParam['fileName']);
    }

    /**
     * 设置生成model需要的参数
     * [
     *  'tplName'=>模板名,
     *  'data' => '执行替换模板的key=>value数组',
     *  'fileName' => '要生成的文件名'
     * ]
     */
    protected function setModelParam()
    {
        $tplName           = 'model' . DS . 'base';
        $arrTableName      = explode('_', $this->tableName);
        $tablePrefix       = $arrTableName['0'] . '_';
        $data['tableName'] = '';
        if ($tablePrefix != Config::get('database.connections.' . Config::get('database.default') . '.prefix')) {
            $data['tableName'] = 'protected $table = \'' . $this->tableName . '\';';
        }
        $data['tableComment']   = $this->tableComment;
        $data['modelName']      = strtolower(str_replace('/', '_', $this->midName));
        $data['modelClassName'] = $this->controllerModelClassName;
        $data['modelNamespace'] = str_replace('/', '\\', dirname('app/model/' . $this->midName));
        $data['relationModel']  = $this->setRelationModel();
        $data['autoTimeFormat'] = $this->autoTimeFormat();
        $data['getAttrFun']     = $this->getAttrFun();
        $this->modelParam       = ['tplName' => $tplName, 'data' => $data, 'fileName' => $this->modelFileName];
    }

    protected function setRelationModel()
    {
        $relationModelFunctionLt = 'model' . DS . 'relation_model_function';
        $arrayRelationModel      = [];
        $relationModel           = '';
        foreach ($this->fields as $k => $item) {
            if ($item['relation']['table']) {
                $data['relationFunctionName'] = $this->underlineToCamel($item['relation']['fun_name']);
                $data['relationType']         = $item['relation']['type'];
                $data['relationModelName'] = 'app\model\\' . $this->getNameByTable($item['relation']['table']);
                $data['foreignKey']   = $item['field'];
                $data['localKey']     = $item['relation']['field'];
                $arrayRelationModel[] = $this->getReplacedTpl($relationModelFunctionLt, $data);
            }

            if($item['form_type'] === 'upload' && $item['addition']['multi'] === 'single'){
                $data['relationFunctionName'] = $this->underlineToCamel($item['field']) . 'File';
                $data['relationType']         = 'belongsTo';
                $data['relationModelName'] = 'app\model\Files';
                $data['foreignKey']   = $item['field'];
                $data['localKey']     = 'id';
                $arrayRelationModel[] = $this->getReplacedTpl($relationModelFunctionLt, $data);
            }
        }
        if ($arrayRelationModel) {
            $relationModel = implode("\n\n\t", $arrayRelationModel);
        }
        return $relationModel;
    }

    //时间选择器，int类型，自动类型转换
    protected function autoTimeFormat()
    {
        $timeSet = [];
        foreach ($this->fields as $k => $v) {
            if ($v['form_type'] == 'laydate' && $v['data_type'] == 'integer') {
                if ($v['addition']['date_type'] == 'datetime') {
                    $timeSet[$v['field']] = "\n\t\t" . '\'' . $v['field'] . '\'  =>  \'timestamp:Y-m-d H:i:s\',';
                } else if ($v['addition']['date_type'] == 'month') {
                    $timeSet[$v['field']] = "\n\t\t" . '\'' . $v['field'] . '\'  =>  \'timestamp:Y-m\',';
                } else if ($v['addition']['date_type'] == 'date') {
                    $timeSet[$v['field']] = "\n\t\t" . '\'' . $v['field'] . '\'  =>  \'timestamp:Y-m-d\',';
                }
            }
        }
        if ($timeSet) {
            return 'protected $type = [' . implode('', $timeSet) . "\n\t];";
        } else {
            return '';
        }
    }

    protected function getAttrFun()
    {
        $fun = [];
        foreach ($this->fields as $k => $v) {
            if ($v['form_type'] == 'laydate' && $v['data_type'] == 'integer') {
                $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'IntAttr($value, $data)' . "\n\t" .
                    '{' . "\n\t\t" .
                    'return isset($data[\'' . $v['field'] . '\']) ? $data[\'' . $v['field'] . '\'] : 0;' . "\n\t" .
                    '}';
                $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'Attr($value, $data)' . "\n\t" .
                    '{' . "\n\t\t" .
                    'return (isset($data[\'' . $v['field'] . '\']) && $data[\'' . $v['field'] . '\']) ? $data[\'' . $v['field'] . '\'] : "";' . "\n\t" .
                    '}';
            }
            if ($v['form_type'] == 'laydate' && $v['data_type'] == 'datetime') {
                $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'IntAttr($value, $data)' . "\n\t" .
                    '{' . "\n\t\t" .
                    'return isset($data[\'' . $v['field'] . '\']) ? strtotime($data[\'' . $v['field'] . '\']) : 0;' . "\n\t" .
                    '}';
            }
            if ($v['form_type'] == 'upload' && $v['addition']['multi'] == 'multi') {
                $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'FileAttr($value, $data)' . "\n\t" .
                    '{' . "\n\t\t" .
                    'return (isset($data[\'' . $v['field'] . '\']) && $data[\'' . $v['field'] . '\']) ? UploadDomain::multiJoin($data[\''.$v['field'].'\']) : \'\';' . "\n\t" .
                    '}';
            }
            if ($v['form_type'] == 'editor') {
                if($v['addition']['upload_type'] != 'local'){
                    $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'Attr($value, $data)' . "\n\t" .
                        '{' . "\n\t\t" .
                        'return $value ? UploadDomain::addUploadDomain($value, \''.$v['addition']['upload_type'].'\') : \'\';' . "\n\t" .
                        '}';
                }else{
                    $fun[] = 'public function get' . ucfirst($this->underlineToCamel($v['field'])) . 'Attr($value, $data)' . "\n\t" .
                        '{' . "\n\t\t" .
                        'return $value ? UploadDomain::addUploadDomain($value) : \'\';' . "\n\t" .
                        '}';
                }
            }
        }
        if ($fun) {
            return implode("\n\n\t", $fun);
        } else {
            return '';
        }
    }

    protected function createModel()
    {
        //是否拥有软删除功能
        $this->modelParam['data']['softDelPackage']    = "";
        $this->modelParam['data']['useSoftDel']        = "";
        $this->modelParam['data']['defaultSoftDelete'] = "";
        $this->modelParam['data']['uploadDomainPackage'] = "";
        $this->modelParam['data']['append'] = "";
        $append = [];
        foreach ($this->fields as $k => $v) {
            if ($v['field'] == 'delete_time') {
                $this->modelParam['data']['softDelPackage'] = "\nuse think\model\concern\SoftDelete;";
                $this->modelParam['data']['useSoftDel']     = "\n\tuse SoftDelete;";
                if ($v['data_type'] === 'datetime' || $v['data_type'] === 'timestamp') {
                    $this->modelParam['data']['defaultSoftDelete'] = "";
                } else if ($v['data_type'] === 'integer') {
                    $this->modelParam['data']['defaultSoftDelete'] = "\n\tprotected \$defaultSoftDelete=0;";
                }
            }
            if(($v['form_type'] == 'upload' && $v['addition']['multi'] == 'multi') || $v['form_type'] == 'editor'){
                $this->modelParam['data']['uploadDomainPackage'] = "\nuse laytp\library\UploadDomain;";
            }
            if(($v['form_type'] == 'upload' && $v['addition']['multi'] == 'multi')){
                $append[] = '\'' . $v['field'] . '_file\'';
            }
        }
        if($append){
            $this->modelParam['data']['append'] = implode(',', $append);
        }
        $this->writeToFile($this->modelParam['tplName'], $this->modelParam['data'], $this->modelParam['fileName']);
    }

    protected function setJsParam()
    {
        $apiPrefix = $this->compatibleApiRoute();
        $tplName      = 'js' . DS . 'index';
        $cols         = "{type:'checkbox',fixed:'left'}\n\t\t\t\t";
        $recycleCols  = "{type:'checkbox',fixed:'left'}\n\t\t\t\t";
        $hasFirstCols = true;//是否已经有了正常的第一行数据
        //是否隐藏主键列
        if ($this->isHidePk != 1) {
            $hasFirstCols = true;
            $temp         = ",{field:'id',title:'ID',align:'center',width:80,fixed:'left'}";
            $cols         .= $temp;
            $recycleCols  .= $temp;
        } else {
            $temp = "//,{field:'id',title:'ID',align:'center',width:80}";
            $cols .= $temp;
            $recycleCols .= $temp;
        }
        //是否生成序号列
        if ($this->isCreateNumber == 1) {
            if ($hasFirstCols) {
                $temp = "\t\t\t\t,{field:'layui_number',title:'序号',align:'center',width:80,type:'numbers'}";
                $cols .= $temp;
                $recycleCols .= $temp;
            } else {
                $temp = "\t\t\t\t{field:'layui_number',title:'序号',align:'center',width:80,type:'numbers'}";
                $cols .= $temp;
                $recycleCols .= $temp;
            }
            if (!$hasFirstCols) $hasFirstCols = true;
        }
        foreach ($this->fields as $k => $v) {
//            if ($v['table_show'] == 2) continue;
            if (!$hasFirstCols) {
                $hasFirstCols = true;
                $temp         = "\t\t\t\t{field:'{$v['field']}',title:'{$v['comment']}'";
                $recycleTemp         = "\t\t\t\t{field:'{$v['field']}',title:'{$v['comment']}'";
            } else {
                $temp = "\n\t\t\t\t,{field:'{$v['field']}',title:'{$v['comment']}'";
                $recycleTemp = "\n\t\t\t\t,{field:'{$v['field']}',title:'{$v['comment']}'";
            }
            if ($v['cell_width']) {
                $temp .= ",width:{$v['cell_width']}";
                $recycleTemp .= ",width:{$v['cell_width']}";
            }
            $temp .= ",align:'center'";
            $recycleTemp .= ",align:'center'";
            //表头排序
            if ($v['is_thead_sort'] == 1) {
                $temp .= ",sort:true";
                $recycleTemp .= ",sort:true";
            }
            //关联模型js渲染
            if ($v['relation']['table'] && $v['relation']['show_field']) {
                if ($v['form_type'] === 'xm_select') {
                    if (isset($v['addition']['single_multi_type']) && $v['addition']['single_multi_type'] === 'single') {
                        $templet = "{{# if(d." . $v['relation']['fun_name'] . "){ }}{{d." . $v['relation']['fun_name'] . "." . $v['relation']['show_field'] . "}}{{# }else{ }}-{{# } }}";
                        $temp    .= ",templet:'<div>" . $templet . "</div>'";
                        $recycleTemp    .= ",templet:'<div>" . $templet . "</div>'";
                    }
                } else {
                    $templet = "{{# if(d." . $v['relation']['fun_name'] . "){ }}{{d." . $v['relation']['fun_name'] . "." . $v['relation']['show_field'] . "}}{{# }else{ }}-{{# } }}";
                    $temp    .= ",templet:'<div>" . $templet . "</div>'";
                    $recycleTemp    .= ",templet:'<div>" . $templet . "</div>'";
                }
            }
            //单行输入框，开启了表格编辑，需要渲染成editInput模板，没有开启的话要转义展示，避免XSS
            if ($v['form_type'] == 'input') {
                if(isset($v['addition']['open_table_edit']) && $v['addition']['open_table_edit'] == 1){
                    $editInputUrl = $apiPrefix . '/set' . ucfirst($this->underlineToCamel($v['field']));
                    $temp .= ",templet:function(d){
                        return laytpForm.tableForm.editInput('{$v['field']}',d,'{$editInputUrl}');
                    }";
                        $recycleTemp .= ",templet:function(d){
                        return laytpForm.tableForm.recycleEditInput('{$v['field']}',d,'{$editInputUrl}');
                    }";
                }else{
                    $temp .= ",templet:function(d){
                        return layui.laytpl('{{=d.{$v['field']}}}').render({{$v['field']}:d.{$v['field']}});
                    }";
                    $recycleTemp .= ",templet:function(d){
                        return layui.laytpl('{{=d.{$v['field']}}}').render({{$v['field']}:d.{$v['field']}});
                    }";
                }
            }
            //文本域要转义展示，避免XSS
            if ($v['form_type'] == 'textarea') {
                $temp .= ",templet:function(d){
                    return layui.laytpl('{{=d.{$v['field']}}}').render({{$v['field']}:d.{$v['field']}});
                }";
                $recycleTemp .= ",templet:function(d){
                    return layui.laytpl('{{=d.{$v['field']}}}').render({{$v['field']}:d.{$v['field']}});
                }";
            }
            //渲染单选按钮，单选按钮渲染成status模板
            if ($v['form_type'] == 'radio') {
                $items            = $v['addition'];
                $jsonArr['value'] = $items['value'];
                $jsonArr['text']  = $items['text'];
                $jsonObj          = json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
                $temp             .= ",templet:function(d){
                    return laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});
                }";
                $recycleTemp      .= ",templet:function(d){
                    return laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});
                }";
            }
            //渲染开关按钮，开关按钮渲染成开关
            if ($v['form_type'] == 'switch') {
                $items = $v['addition'];
                $temp  .= ",templet:function(d){
                    return laytpForm.tableForm.switch(\"{$v['field']}\", d, {
                        \"open\": {\"value\": {$items['open_value']}, \"text\": \"{$items['open_text']}\"},
                        \"close\": {\"value\": {$items['close_value']}, \"text\": \"{$items['close_text']}\"}
                    });
                }";
                $recycleTemp .= ",templet:function(d){
                    return laytpForm.tableForm.recycleSwitch(\"{$v['field']}\", d, {
                        \"open\": {\"value\": {$items['open_value']}, \"text\": \"{$items['open_text']}\"},
                        \"close\": {\"value\": {$items['close_value']}, \"text\": \"{$items['close_text']}\"}
                    });
                }";
            }
            //渲染下拉框，下拉框渲染成status模板
            if ($v['form_type'] == 'select') {
                $jsonObj = json_encode($v['addition'], JSON_UNESCAPED_UNICODE);
                $temp    .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
                $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
            }
            //复选框渲染成flag的模板
            if ($v['form_type'] == 'checkbox') {
                $jsonObj = json_encode($v['addition'], JSON_UNESCAPED_UNICODE);
                $temp    .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.flag(d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
                $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.flag(d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
            }
            //xmSelect，数据来源=data，单选，渲染成status
            if ($v['form_type'] == 'xm_select' && $v['addition']['data_from_type'] == 'data' && $v['addition']['single_multi_type'] == 'single') {
                $jsonArr['value']   = $v['addition']['value'];
                $jsonArr['text']    = $v['addition']['text'];
                $jsonArr['default'] = $v['addition']['default'];
                $jsonObj            = json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
                $temp               .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
                $recycleTemp        .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.status('{$v['field']}',d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
            }
            //xmSelect，数据来源=data，多选，渲染成flag
            if ($v['form_type'] == 'xm_select' && $v['addition']['data_from_type'] == 'data' && $v['addition']['single_multi_type'] == 'multi') {
                $jsonArr['value']   = $v['addition']['value'];
                $jsonArr['text']    = $v['addition']['text'];
                $jsonArr['default'] = $v['addition']['default'];
                $jsonObj            = json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
                $temp               .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.flag(d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
                $recycleTemp        .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.flag(d.{$v['field']},{$jsonObj});\n\t\t\t\t}";
            }
            //image模板
            if ($v['form_type'] == 'upload' && $v['addition']['accept'] == 'image') {
                if($v['addition']['multi'] == 'single'){
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.images(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.images(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                }else{
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.images(d.{$v['field']}_file.path);\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.images(d.{$v['field']}_file.path);\n\t\t\t\t}";
                }
            }
            //video模板
            if ($v['form_type'] == 'upload' && $v['addition']['accept'] == 'video') {
                if($v['addition']['multi'] == 'single'){
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.video(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.video(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                }else{
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.video(d.{$v['field']}_file.path);\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.video(d.{$v['field']}_file.path);\n\t\t\t\t}";
                }
            }
            //audio模板
            if ($v['form_type'] == 'upload' && $v['addition']['accept'] == 'audio') {
                if($v['addition']['multi'] == 'single'){
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.audio(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.audio(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                }else{
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.audio(d.{$v['field']}_file.path);\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.audio(d.{$v['field']}_file.path);\n\t\t\t\t}";
                }
            }
            //file模板
            if ($v['form_type'] == 'upload' && $v['addition']['accept'] == 'file') {
                if($v['addition']['multi'] == 'single'){
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.file(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn d.{$v['field']}_file ? laytp.tableFormatter.file(d.{$v['field']}_file.path) : \"\";\n\t\t\t\t}";
                }else{
                    $temp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.file(d.{$v['field']}_file.path);\n\t\t\t\t}";
                    $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.file(d.{$v['field']}_file.path);\n\t\t\t\t}";
                }
            }
            //colorPicker模板
            if ($v['form_type'] == 'color_picker') {
                $temp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.colorPicker(d.{$v['field']});\n\t\t\t\t}";
                $recycleTemp .= ",templet:function(d){\n\t\t\t\t\treturn laytp.tableFormatter.colorPicker(d.{$v['field']});\n\t\t\t\t}";
            }
            $temp .= "}";
            $recycleTemp .= "}";
            if ($v['table_show'] == 1) $cols .= $temp;
            $recycleCols .= $recycleTemp;
        }
        $data['cols']         = $cols;
        $data['cellMinWidth'] = 120;
        $data['apiPrefix'] = $this->compatibleApiRoute();
        $data['htmlPrefix'] = $this->compatibleHtmlPath();
        $this->jsParam        = ['tplName' => $tplName, 'data' => $data, 'fileName' => $this->jsFileName];

        $data['recycleCols']  = $recycleCols;
        $this->recycleJsParam = ['tplName' => 'js' . DS . 'recycle', 'data' => $data, 'fileName' => $this->recycleJsFileName];
    }

    public function compatibleApiRoute($midName = false){
        if(!$midName){
            $midName = strtolower($this->midName);
        }else{
            $midName = strtolower($midName);
        }
        if($midName === "user"){
            return "/admin.api.user";
        }
        if(substr($midName,0,5) . '.' != 'admin.'){
            return '/admin.' . str_replace('/', '.', $midName);
        }else{
            return '/' . str_replace('/', '.', $midName);
        }
    }

    public function compatibleHtmlPath(){
        $midName = strtolower($this->midName);
        if(substr($midName,0,5) != 'admin/'){
            return '/admin/' . strtolower($midName);
        }else{
            return '/' . strtolower($midName);
        }
    }

    /**
     * 获取一个数组，根据传入的参数，这个参数string的格式类似于:0=游泳,1=下棋,2=游戏,3=乒乓球,4=羽毛,5=跑步,6=爬山,7=美食,default=0;1;2
     * @param $string
     * @return array
     */
    public function getArrayByString($string)
    {
        $items       = explode(",", $string);
        $radio_items = [];//待选项数组
        foreach ($items as $k => $v) {
            $temp = explode('=', $v);
            if ($temp[0] != 'default') {
                $radio_items[$temp[0]] = $temp[1];
            }
        }
        return $radio_items;
    }

    //生成js文件
    protected function createJs()
    {
        $this->writeToFile($this->jsParam['tplName'], $this->jsParam['data'], $this->jsParam['fileName']);
        if ($this->hasSoftDel) {
            $this->writeToFile($this->recycleJsParam['tplName'], $this->recycleJsParam['data'], $this->recycleJsParam['fileName']);
        }
    }

    protected function setHtmlParam()
    {
        $indexTplName            = 'html' . DS . 'index';
        $indexData['jsFileName'] = $this->filterMidName(strtolower($this->midName)) . '.js';
        $indexSearchForm         = [];
        $recycleSearchForm         = [];
        $linkageSelectSearchHtml = [];
        $recycleLinkageSelectSearchHtml = [];
        $unSearchType            = ['password', 'upload', 'color_picker'];
        foreach ($this->fields as $k => $v) {
            if (!in_array($v['form_type'], $unSearchType)) {
                if (in_array($v['form_type'], ['linkage_select'])) {
                    if ($v['search_show'] == 1) {
                        $linkageSelectSearchHtml[$v['form_type']][$v['addition']['group_name']][] = $this->getSearchFormContent($v);
                    }
                    $recycleLinkageSelectSearchHtml[$v['form_type']][$v['addition']['group_name']][] = $this->getSearchFormContent($v);
                } else {
                    $searchItemContent = $this->getSearchFormContent($v);
                    if ($v['search_show'] == 1) {
                        $indexSearchForm[] = $this->getSearchFormItem($v, $searchItemContent);
                    }
                    $recycleSearchForm[] = $this->getSearchFormItem($v, $searchItemContent);
                }
            }
        }

        if (count($linkageSelectSearchHtml)) {
            foreach ($linkageSelectSearchHtml as $formType => $groupList) {
                foreach ($groupList as $groupName => $item) {
                    $indexSearchForm[] = $this->getLinkageSelectSearchFormGroup($groupName, join("\n\t\t\t\t", $item));
                }
            }
        }

        if (count($recycleLinkageSelectSearchHtml)) {
            foreach ($recycleLinkageSelectSearchHtml as $formType => $groupList) {
                foreach ($groupList as $groupName => $item) {
                    $recycleSearchForm[] = $this->getLinkageSelectSearchFormGroup($groupName, join("\n\t\t\t\t", $item));
                }
            }
        }

        $indexData['searchForm'] = implode("\n\n\t\t", $indexSearchForm);
        $indexData['apiPrefix'] = $this->compatibleApiRoute();
        $indexData['htmlPrefix'] = $this->compatibleHtmlPath();

        $this->htmlIndexParam = ['tplName' => $indexTplName, 'data' => $indexData, 'fileName' => $this->htmlIndexFileName];

        $addData               = [];
        $editData              = [];
//        $detailData            = [];
        $recycleData           = [];
        $linkageSelectAddHtml  = [];
        $linkageSelectEditHtml = [];
        foreach ($this->fields as $k => $v) {
            if (in_array($v['form_type'], ['linkage_select'])) {
                if ($v['add_show'] == 1) {
                    $linkageSelectAddHtml[$v['form_type']][$v['addition']['group_name']][] = $this->getFormItem($v, 'add');
                }
                if ($v['edit_show'] == 1) {
                    $linkageSelectEditHtml[$v['form_type']][$v['addition']['group_name']][] = $this->getFormItem($v, 'edit');
                }
            } else if (in_array($v['form_type'], ['admin_user_id'])) {
//                $addData[]  = $this->getFormItem($v, 'add');
//                $editData[] = $this->getFormItem($v, 'edit');
            } else {
                if ($v['add_show'] == 1) {
                    $addItemContent = $this->getFormItem($v, 'add');
                    $addData[]      = $this->getFormGroup($v['comment'], $addItemContent, $v['is_empty']);
                }
                if ($v['edit_show'] == 1) {
                    $editItemContent = $this->getFormItem($v, 'edit');
                    $editData[]      = $this->getFormGroup($v['comment'], $editItemContent, $v['is_empty']);
                }
            }
//            $detailData[] = $this->getDetailItem($v, $this->getDetailContent($v));
        }

        if (count($linkageSelectAddHtml)) {
            foreach ($linkageSelectAddHtml as $formType => $groupList) {
                foreach ($groupList as $groupName => $item) {
                    $addData[] = $this->getFormGroup($groupName, join("\n\t\t\t\t\t\t", $item), 0);
                }
            }
        }

        if (count($linkageSelectEditHtml)) {
            foreach ($linkageSelectEditHtml as $formType => $groupList) {
                foreach ($groupList as $groupName => $item) {
                    $editData[] = $this->getFormGroup($groupName, join("\n\t\t\t\t\t\t", $item), 0);
                }
            }
        }

        $addTplName             = 'html' . DS . 'add';
        $addForm['formContent'] = implode("\n\n", $addData);
        $addForm['action']      = $this->compatibleApiRoute() . '/add';
        $this->htmlAddParam     = ['tplName' => $addTplName, 'data' => $addForm, 'fileName' => $this->htmlAddFileName];

        $editTplName             = 'html' . DS . 'edit';
        $editForm['formContent'] = implode("\n\n", $editData);
        $editForm['action']      = $this->compatibleApiRoute() . '/edit';
        $editForm['infoAction']      = $this->compatibleApiRoute() . '/info';
        $this->htmlEditParam     = ['tplName' => $editTplName, 'data' => $editForm, 'fileName' => $this->htmlEditFileName];

//        $detailTplName                  = 'html' . DS . 'detail';
//        $detailHtmlData['tableContent'] = implode("\n", $detailData);
//        $this->htmlDetailParam          = ['tplName' => $detailTplName, 'data' => $detailHtmlData, 'fileName' => $this->htmlDetailFileName];

        $recycleTplName            = 'html' . DS . 'recycle';
        $recycleData['searchForm'] = implode("\n\n\t\t", $recycleSearchForm);
        $recycleData['jsFileName'] = strtolower($this->midName) . 'Recycle.js';
        $recycleData['apiPrefix'] = $this->compatibleApiRoute();
        $recycleData['htmlPrefix'] = $this->compatibleHtmlPath();
        $this->htmlRecycleParam    = ['tplName' => $recycleTplName, 'data' => $recycleData, 'fileName' => $this->htmlRecycleFileName];
    }

    protected function getDetailItem($info, $content)
    {
        return <<<EOD
        <tr>
            <td style="table-layout: fixed;word-break: break-all;">{$info['comment']}</td>
            <td style="table-layout: fixed;word-break: break-all;">{$content}</td>
        </tr>
EOD;
    }

    protected function getDetailContent($info)
    {
        //关联模型
        if ($info['relation']['table'] && $info['relation']['show_field']) {
            return "{{d." . $info['relation']['fun_name'] . "." . $info['relation']['show_field'] . "}}";
        } else if ($info['form_type'] === 'radio') {
            $additionArr = [];
            foreach ($info['addition']['value'] as $k => $item) {
                $additionArr[$item] = $info['addition']['text'][$k];
            }
            $additionJson = json_encode($additionArr, JSON_UNESCAPED_UNICODE);
            return "\n\t\t\t\t{{# let " . $info['field'] . "Json = " . $additionJson . " }}\n\t\t\t\t<span class='layui-icon layui-icon-circle-dot' style='color:#009688;font-size:14px;'>{{" . $info['field'] . "Json[d." . $info['field'] . "]}}</span>\n\t\t\t";
        } else if ($info['form_type'] === 'switch') {
            $additionArr  = [
                $info['addition']['close_value'] => $info['addition']['close_text'],
                $info['addition']['open_value']  => $info['addition']['open_text'],
            ];
            $additionJson = json_encode($additionArr, JSON_UNESCAPED_UNICODE);
            return "\n\t\t\t\t{{# let " . $info['field'] . "Json = " . $additionJson . " }}\n\t\t\t\t<span class='layui-icon layui-icon-circle-dot' style='color:#FF5722;font-size:14px;'>{{" . $info['field'] . "Json[d." . $info['field'] . "]}}</span>\n\t\t\t";
        } else if ($info['form_type'] === 'checkbox') {
            $custom      = ["#FF5722", "#009688", "#FFB800", "#2F4056", "#1E9FFF", "#393D49", "#999999", "#0b1bf8", "#7a0bf8", "#f00bf8", "#5FB878", "#1E9FFF", "#2F4056"];
            $additionArr = [];
            foreach ($info['addition']['value'] as $k => $item) {
                $additionArr[$item] = [
                    'text'  => $info['addition']['text'][$k],
                    'color' => $custom[$k],
                ];
            }
            $additionJson = json_encode($additionArr, JSON_UNESCAPED_UNICODE);
            $additionJson = substr($additionJson, 0, -1) . ' ' . substr($additionJson, -1, 1);
            return "\n\t\t\t\t{{# let " . $info['field'] . "Json = " . $additionJson . " }}\n\t\t\t\t" .
                "{{# let " . $info['field'] . "Arr = d." . $info['field'] . ".split(\",\"), " . $info['field'] . "K}}\n\t\t\t\t" .
                "{{# for(" . $info['field'] . "K in " . $info['field'] . "Arr){ }}\n\t\t\t\t\t"
                . "<span class=\"layui-btn layui-btn-xs\" style=\"background-color: {{" . $info['field'] . "Json[" . $info['field'] . "Arr[" . $info['field'] . "K]]['color']}}\">{{" . $info['field'] . "Json[" . $info['field'] . "Arr[" . $info['field'] . "K]]['text']}}</span>\n\t\t\t\t" .
                "{{# } }}\n\t\t\t";
        } else if ($info['form_type'] === 'select') {
            $additionArr = [];
            foreach ($info['addition']['value'] as $k => $item) {
                $additionArr[$item] = $info['addition']['text'][$k];
            }
            $additionJson = json_encode($additionArr, JSON_UNESCAPED_UNICODE);
            return "\n\t\t\t\t{{# let " . $info['field'] . "Json = " . $additionJson . " }}\n\t\t\t\t<span class='layui-icon layui-icon-circle-dot' style='color:#1E9FFF;font-size:14px;'>{{" . $info['field'] . "Json[d." . $info['field'] . "]}}</span>\n\t\t\t";
        } else if ($info['form_type'] === 'color_picker') {
            return "<span class='layui-badge' style='background-color:{{d." . $info['field'] . "}};font-size:14px;'>&nbsp;&nbsp;</span>\n\t\t\t";
        } else if ($info['form_type'] === 'upload') {
            if ($info['addition']['accept'] === 'image') {
                if ($info['addition']['multi'] === 'single') {
                    return "\n\t\t\t\t<a target=\"_blank\" href=\"{{d." . $info['field'] . "}}\"><img id=\"" . $info['field'] . "\" style=\"width:30px;height:30px;\" /></a>" .
                        "\n\t\t\t\t<script>" .
                        "\n\t\t\t\t\tvar " . $info['field']."T".$info['table_id'] . " = \"{{d." . $info['field'] . "}}\";" .
                        "\n\t\t\t\t\tdocument.getElementById(\"" . $info['field'] . "\").src = " . $info['field']."T".$info['table_id'] . ";" .
                        "\n\t\t\t\t</script>\n\t\t\t";
                }
                if ($info['addition']['multi'] === 'multi') {
                    return "\n\t\t\t\t{{# var " . $info['field'] . "Arr = d." . $info['field'] . ".split(\", \"), " . $info['field'] . "K}}" .
                        "\n\t\t\t\t{{# for(" . $info['field'] . "K in " . $info['field'] . "Arr){ }}" .
                        "\n\t\t\t\t\t<a target=\"_blank\" href=\"{{" . $info['field'] . "Arr[" . $info['field'] . "K]}}\"><img id=\"" . $info['field'] . "{{" . $info['field'] . "K}}\" style=\"width:30px;height:30px;\" /></a>" .
                        "\n\t\t\t\t\t<script>" .
                        "\n\t\t\t\t\t\tvar " . $info['field'] . "{{" . $info['field'] . "K}} = \"{{" . $info['field'] . "Arr[" . $info['field'] . "K]}}\";" .
                        "\n\t\t\t\t\t\tdocument.getElementById(\"" . $info['field'] . "{{" . $info['field'] . "K}}\").src = " . $info['field'] . "{{" . $info['field'] . "K}};" .
                        "\n\t\t\t\t\t</script>\n\t\t\t\t" .
                        "{{# } }}\n\t\t\t";
                }
            }else if ($info['addition']['accept'] === 'video') {
                if ($info['addition']['multi'] === 'single') {
                    return "\n\t\t\t\t<a href=\"javascript:void(0);\" onclick=\"layui.laytp.tableFormatter.showVideo('{{d." . $info['field'] . "}}')\" class=\"layui-link\">视频</a>\n\t\t\t";
                }
                if ($info['addition']['multi'] === 'multi') {
                    return "\n\t\t\t\t{{# var " . $info['field'] . "Arr = d." . $info['field'] . ".split(\", \"), " . $info['field'] . "K}}\n\t\t\t\t" .
                        "{{# for(" . $info['field'] . "K in " . $info['field'] . "Arr){ }}\n\t\t\t\t\t"
                        . "<a href=\"javascript:void(0);\" onclick=\"layui.laytp.tableFormatter.showVideo('{{" . $info['field'] . "Arr[" . $info['field'] . "K]}}')\" class=\"layui-link\">视频{{parseInt(" . $info['field'] . "K) + 1}}</a>\n\t\t\t\t" .
                        "{{# } }}\n\t\t\t";
                }
            }else if($info['addition']['accept'] === 'audio'){
                if ($info['addition']['multi'] === 'single') {
                    return "\n\t\t\t\t<audio src=\"{{d." . $info['field'] . "}}\" width=\"200px\" height=\"30px\" controls=\"controls\" preload=\"none\"></audio>\n\t\t\t";
                }
                if ($info['addition']['multi'] === 'multi') {
                    return "\n\t\t\t\t{{# var " . $info['field'] . "Arr = d." . $info['field'] . ".split(\", \"), " . $info['field'] . "K}}" .
                        "\n\t\t\t\t{{# for(" . $info['field'] . "K in " . $info['field'] . "Arr){ }}" .
                        "\n\t\t\t\t\t<audio id=\"" . $info['field'] . "{{" . $info['field'] . "K}}\" width=\"200px\" height=\"30px\" controls=\"controls\" preload=\"none\"></audio>" .
                        "\n\t\t\t\t\t<script>" .
                        "\n\t\t\t\t\t\tvar " . $info['field'] . "{{" . $info['field'] . "K}} = \"{{" . $info['field'] . "Arr[" . $info['field'] . "K]}}\";" .
                        "\n\t\t\t\t\t\tdocument.getElementById(\"" . $info['field'] . "{{" . $info['field'] . "K}}\").src = " . $info['field'] . "{{" . $info['field'] . "K}};" .
                        "\n\t\t\t\t\t</script>" .
                        "\n\t\t\t\t{{# } }}\n\t\t\t";
                }
            }else if($info['addition']['accept'] === 'file'){
                if ($info['addition']['multi'] === 'single') {
                    return "\n\t\t\t\t<a target=\"_blank\" href=\"{{d." . $info['field'] . "}}\" class=\"layui-link\">文件</a>\n\t\t\t";
                }
                if ($info['addition']['multi'] === 'multi') {
                    return "\n\t\t\t\t{{# var " . $info['field'] . "Arr = d." . $info['field'] . ".split(\", \"), " . $info['field'] . "K}}\n\t\t\t\t" .
                        "{{# for(" . $info['field'] . "K in " . $info['field'] . "Arr){ }}\n\t\t\t\t\t" .
                        "<a target=\"_blank\" href=\"{{d." . $info['field'] . "}}\" class=\"layui-link\">文件{{parseInt(" . $info['field'] . "K) + 1}}</a>\n\t\t\t\t" .
                        "{{# } }}\n\t\t\t";
                }
            }
        }else if($info['form_type'] === 'editor'){
            if($info['addition']['type'] === 'ueditor'){
                return "\n\t\t\t\t<iframe src=\"/admin/showUEditor.html?val={{window.btoa(unescape(encodeURIComponent(d.ueditor)))}}\" class=\"editor\" data-type=\"ueditor\" data-id=\"ueditor\" style=\"width:100%;height:560px;border: 0\"></iframe>\n\t\t\t";
            }
        }
        return "{{d." . $info['field'] . "}}";
    }

    //生成html模板文件
    protected function createHtml()
    {
        $this->writeToFile($this->htmlIndexParam['tplName'], $this->htmlIndexParam['data'], $this->htmlIndexParam['fileName']);
        $this->writeToFile($this->htmlAddParam['tplName'], $this->htmlAddParam['data'], $this->htmlAddParam['fileName']);
        $this->writeToFile($this->htmlEditParam['tplName'], $this->htmlEditParam['data'], $this->htmlEditParam['fileName']);
//        $this->writeToFile($this->htmlDetailParam['tplName'], $this->htmlDetailParam['data'], $this->htmlDetailParam['fileName']);
        //是否拥有软删除功能
        foreach ($this->fields as $k => $v) {
            if ($v->field == 'delete_time') {
                $this->writeToFile($this->htmlRecycleParam['tplName'], $this->htmlRecycleParam['data'], $this->htmlRecycleParam['fileName']);
                break;
            }
        }
    }

    protected function getFormItem($info, $type = 'add')
    {
        $func = 'get' . ucfirst($this->underlineToCamel($info['form_type'])) . 'Html';
        return $this->$func($info, $type);
    }

    /**
     * 获取表单分组数据
     * @param string $field
     * @param string $content
     * @param integer $isEmpty
     * @return string
     */
    protected function getFormGroup($field, $content, $isEmpty)
    {
        if ($isEmpty != 1) {
            $requiredHtml = " <text title=\"必填项\" style=\"color:red;\">*</text> ";
        } else {
            $requiredHtml = "";
        }
        return <<<EOD
\t\t\t\t<div class="layui-form-item">
    \t\t\t\t<label class="layui-form-label" title="{$field}">{$requiredHtml}{$field}</label>
    \t\t\t\t<div class="layui-input-block">
            \t\t\t{$content}
    \t\t\t\t</div>
\t\t\t\t</div>
EOD;
    }

    protected function getSearchFormContent($info)
    {
        $func = 'getSearch' . ucfirst($this->underlineToCamel($info['form_type'])) . 'Html';
        return $this->$func($info);
    }

    protected function getSearchFormItem($info, $content)
    {
        $fieldComment = $info['comment'];
        return <<<EOD
<div class="layui-form-item layui-inline">
                <label class="layui-form-label" title="{$fieldComment}">{$fieldComment}</label>
                <div class="layui-input-inline">
                    {$content}
                </div>
            </div>
EOD;
    }

    protected function getLinkageSelectSearchFormGroup($field, $content)
    {
        return <<<EOD
    <div class="layui-form-item layui-inline">
                <label class="layui-form-label" title="{$field}">{$field}</label>
                {$content}
            </div>
EOD;
    }

    /**
     * 接下来是html的各种控件模板内容生成，比如input、select、upload等等
     */

    /**
     * 获取input需要生成的html，在生成add和edit表单的时候可以用到
     * @param $info
     * @param $type string 类型，add或者edit
     * @return string
     */
    protected function getInputHtml($info, $type)
    {
        $name            = 'html' . DS . $type . DS . 'input';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        if ($info['is_empty'] == 2) {
            $data['verify'] = $info['addition']['verify'] ? 'required|' . $info['addition']['verify'] : 'required';
        } else {
            $data['verify'] = $info['addition']['verify'];
        }
        $data['defaultValue'] = ($info['default'] === "") ? "" : " value=\"" . $info['default'] ."\"";
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 获取input需要生成的html，在生成搜索表单时用到
     * @param $info
     * @return string
     */
    protected function getSearchInputHtml($info)
    {
        $name            = 'html' . DS . 'search' . DS . 'input';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * admin_id类型模板
     * @param $info
     * @param $type
     * @return string
     */
    protected function getAdminUserIdHtml($info, $type)
    {
        $name            = 'html' . DS . $type . DS . 'admin_id';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * admin_id类型模板
     * @param $info
     * @return string
     */
    protected function getSearchAdminUserIdHtml($info)
    {
        $name            = 'html' . DS . 'search' . DS . 'admin_id';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 获取input需要生成的html，在生成add和edit表单的时候可以用到
     * @param $info
     * @param $type string 类型，add或者edit
     * @return string
     */
    protected function getTextareaHtml($info, $type)
    {
        $name            = 'html' . DS . $type . DS . 'textarea';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        $data['verify']  = $info['is_empty'] == 1 ? '' : 'required';
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 获取input需要生成的html，在生成搜索表单时用到
     * @param $info
     * @return string
     */
    protected function getSearchTextareaHtml($info)
    {
        $name            = 'html' . DS . 'search' . DS . 'textarea';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 获取input需要生成的html，在生成add和edit表单的时候可以用到
     * @param $info
     * @param $type string 类型，add或者edit
     * @return string
     */
    protected function getRadioHtml($info, $type)
    {
        $items     = $info['addition'];
        $name      = 'html' . DS . $type . DS . 'radio';
        $radioHtml = '';
        foreach ($items['value'] as $k => $v) {
            $tempData['field']         = $info['field'];
            $tempData['value']         = $items['value'][$k];
            $tempData['title']         = $items['text'][$k];
            $tempData['checkedStatus'] = ($items['value'][$k] == $items['default']) ? 'checked="checked"' : '';
            $radioHtml                 .= $this->getReplacedTpl($name, $tempData) . "\n\t\t\t\t\t\t";
        }
        $radioHtml = rtrim($radioHtml, "\n\t\t\t");
//            $this->set_model_array_const($info['field_name'], $model_array_const);
        return $radioHtml;
    }

    protected function getSearchRadioHtml($info)
    {
        $name          = 'html' . DS . 'search' . DS . 'radio';
        $data['field'] = $info['field'];
        $items         = $info['addition'];
        $options       = '';
        foreach ($items['value'] as $k => $v) {
            $options .= "\t\t\t\t\t\t" . '<option value="' . $items['value'][$k] . '">' . $items['text'][$k] . '</option>' . "\n";
        }
        $options         = "\t\t\t\t" . '<option value=""></option>' . "\n" . rtrim($options, "\n");
        $data['options'] = $options;
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    protected function getSwitchHtml($info, $type)
    {
        $items                  = $info['addition'];
        $name                   = 'html' . DS . $type . DS . 'switch';
        $data['field']          = $info['field'];
        $data['close_value']    = $items['close_value'];
        $data['open_value']     = $items['open_value'];
        $data['checked_status'] = ($items['default_status'] == 'open') ? 'checked="checked"' : '';
        $data['lay_text']       = $items['open_text'] . '|' . $items['close_text'];
        return $this->getReplacedTpl($name, $data);
    }

    protected function getSearchSwitchHtml($info)
    {
        $name            = 'html' . DS . 'search' . DS . 'radio';
        $data['field']   = $info['field'];
        $items           = $info['addition'];
        $options         = '';
        $options         .= "\t\t\t\t\t\t" . '<option value="' . $items['close_value'] . '">' . $items['close_text'] . '</option>' . "\n";
        $options         .= "\t\t\t\t\t\t" . '<option value="' . $items['open_value'] . '">' . $items['open_text'] . '</option>' . "\n";
        $data['options'] = $options;
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    protected function getSearchCheckboxHtml($info)
    {
        $name          = 'html' . DS . 'search' . DS . 'checkbox';
        $data['field'] = $info['field'];
        $items         = $info['addition'];
        $data['max']   = count($items);
        $optionItems   = [];
//        $model_array_const = [];
        foreach ($items['value'] as $k => $v) {
            $optionItems[] = ['value' => $items['value'][$k], 'name' => $items['text'][$k]];
//            $model_array_const[(string)$temp[0]] = $temp[1];
        }
        $data['source'] = str_replace("\"", "'", json_encode($optionItems, JSON_UNESCAPED_UNICODE));
//        $this->set_controller_array_const($info['field_name'], $model_array_const);
        return $this->getReplacedTpl($name, $data);
    }

    protected function getCheckboxHtml($info, $type)
    {
        $name          = 'html' . DS . $type . DS . 'checkbox';
        $data['field'] = $info['field'];
        $items         = $info['addition'];
        $optionItems   = [];
        $checkboxHtml = '';
        foreach ($items['value'] as $k => $v) {
            $optionItems[] = ['value' => $items['value'][$k], 'text' => $items['text'][$k]];
        }

        $defaultValueArr = isset($items['default']) ? $items['default'] : [];

        foreach ($optionItems as $k => $v) {
            if($k == 0 && $type == 'edit'){
                $checkboxHtml .= "{{# var " . $info['field'] . "ValueArr = d." . $info['field'] . ".split(','); }}\n\t\t\t\t\t\t";
            }
            if ($type == 'add') {
                $data['checked'] = in_array($v['value'], $defaultValueArr) ? 'checked="checked"' : '';
            }
            $data['value'] = $v['value'];
            $data['text']  = $v['text'];
            $checkboxHtml  .= $this->getReplacedTpl($name, $data) . "\n\t\t\t\t\t\t";
        }
        $checkboxHtml = rtrim($checkboxHtml, "\n\t\t\t\t\t\t");

//        $this->set_model_array_const($info['field_name'], $model_array_const);
        return $checkboxHtml;
    }

    protected function getSearchSelectHtml($info)
    {
        $name          = 'html' . DS . 'search' . DS . 'radio';
        $data['field'] = $info['field'];
        $items         = $info['addition'];
        $options       = '';
        foreach ($items['value'] as $k => $v) {
            $options .= "\t\t\t\t\t\t" . '<option value="' . $items['value'][$k] . '">' . $items['text'][$k] . '</option>' . "\n";
        }
        $options         = "\t\t\t\t" . '<option value=""></option>' . "\n" . rtrim($options, "\n");
        $data['options'] = $options;
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 获取select需要生成的html，在生成add和edit表单的时候可以用到
     * @param $info
     * @param $type string 类型，add或者edit
     * @return string
     */
    protected function getSelectHtml($info, $type)
    {
        $name    = 'html' . DS . $type . DS . 'select';
        $items   = $info['addition'];
        $options = "\t\t\t\t\t\t\t" . '<option value="">请选择' . $info['comment'] . '</option>' . "\n";
        foreach ($items['value'] as $k => $v) {
            if ($type == 'add') {
                if ($items['value'][$k] === $items['default']) {
                    $options .= "\t\t\t\t\t\t\t" . '<option value="' . $v . '" selected="selected">' . $items['text'][$k] . '</option>' . "\n";
                } else {
                    $options .= "\t\t\t\t\t\t\t" . '<option value="' . $v . '">' . $items['text'][$k] . '</option>' . "\n";
                }
            } else {
                $options .= "\t\t\t\t\t\t\t" . '<option value="' . $v . '" {{# if(d.' . $info['field'] . '.toString() == \'' . $v . '\'){ }}selected="selected"{{# } }}>' . $items['text'][$k] . '</option>' . "\n";
            }
        }
        $data['options'] = $options;
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        $data['verify']  = $info['is_empty'] == 1 ? '' : 'required';
        return $this->getReplacedTpl($name, $data);
    }

    protected function getSearchXmSelectHtml($info)
    {
        $name          = 'html' . DS . 'search' . DS . 'xm_select';
        $data['field'] = $info['field'];
        if ($info['addition']['data_from_type'] === "data") {
            $data['sourceType'] = "data";
            $source             = [];
            foreach ($info['addition']['value'] as $k => $v) {
                $source[] = ['name' => $info['addition']['text'][$k], 'value' => $info['addition']['value'][$k]];
            }
            $data['source']       = str_replace('"', '\'', json_encode($source, JSON_UNESCAPED_UNICODE));
            $data['sourceTree']   = "false";
            $data['paging']       = "false";
            $data['textField']    = "";
            $data['subTextField'] = "";
            $data['iconField']    = "";
            $data['valueField']   = "";
        } else {
            $data['sourceType'] = "route";
            $tableName          = $info['addition']['table_id'];
            $midName            = str_replace('/', '.', $this->getMidName($tableName));
            $data['source'] = $this->compatibleApiRoute($midName) . '/index';
            $createType    = Table::where('table', '=', $tableName)->value('create_type');
            if(!$createType){
                $this->setError('生成失败。' . $tableName . '数据表未使用生成程序生成');
                return false;
            }
            $tableIsTree = ($createType == 1) ? 0 : 1;//取到数据表是否为无限极分类模型
            if ($tableIsTree) {
                $data['sourceTree'] = "true";
                $data['paging']     = "false";
            } else {
                $data['sourceTree'] = "false";
                $data['paging']     = "true";
            }
            $data['textField']    = $info['addition']['title_field'] ? "\n\t\t\t\t\t\t" . 'data-textField="' . $info['addition']['title_field'] . '"' : '';
            $data['subTextField'] = $info['addition']['sub_title_field'] ? "\n\t\t\t\t\t\t" . 'data-subTextField="' . $info['addition']['sub_title_field'] . '"' : '';
            $data['iconField']    = $info['addition']['icon_field'] ? "\n\t\t\t\t\t\t" . 'data-iconField="' . $info['addition']['icon_field'] . '"' : '';
            $data['valueField']   = "id";
        }
        $data['comment']   = $info['comment'] ? "\n\t\t\t\t\t\t" . 'data-placeholder="请选择' . $info['comment'] . '"' : '';
        $data['direction'] = $info['addition']['direction'] ? "\n\t\t\t\t\t\t" . 'data-direction="' . $info['addition']['direction'] . '"' : '';

        return $this->getReplacedTpl($name, $data);
    }

    protected function getXmSelectHtml($info, $type)
    {
        $name          = 'html' . DS . $type . DS . 'xm_select';
        $data['field'] = $info['field'];
        if ($info['addition']['data_from_type'] === "data") {
            $data['sourceType'] = "data";
            $source             = [];
            foreach ($info['addition']['value'] as $k => $v) {
                $source[] = ['name' => $info['addition']['text'][$k], 'value' => $info['addition']['value'][$k]];
            }
            $data['source']       = str_replace('"', '\'', json_encode($source, JSON_UNESCAPED_UNICODE));
            $data['sourceTree']   = "false";
            $data['paging']       = "false";
            $data['textField']    = "";
            $data['subTextField'] = "";
            $data['iconField']    = "";
            $data['valueField']   = "";
        } else {
            $data['sourceType'] = "route";
            $tableName          = $info['addition']['table_id'];
            $midName            = str_replace('/', '.', $this->getMidName($tableName));
            $data['source'] = $this->compatibleApiRoute($midName) . '/index';
            $createType    = Table::where('table', '=', $tableName)->value('create_type');
            $tableIsTree = ($createType == 1) ? 0 : 1;//取到数据表是否为无限极分类模型
            if ($tableIsTree) {
                $data['sourceTree'] = "true";
                $data['paging']     = "false";
            } else {
                $data['sourceTree'] = "false";
                $data['paging']     = "true";
            }
            $data['textField']    = $info['addition']['title_field'] ? "\n\t\t\t\t\t\t\t" . 'data-textField="' . $info['addition']['title_field'] . '"' : '';
            $data['subTextField'] = $info['addition']['sub_title_field'] ? "\n\t\t\t\t\t\t\t" . 'data-subTextField="' . $info['addition']['sub_title_field'] . '"' : '';
            $data['iconField']    = $info['addition']['icon_field'] ? "\n\t\t\t\t\t\t\t" . 'data-iconField="' . $info['addition']['icon_field'] . '"' : '';
            $data['valueField']   = "\n\t\t\t\tdata-valueField=\"id\"";
        }
        $data['layVerify'] = ($info['is_empty'] == 1) ? "" : "\n\t\t\t\t\t\t\t" . 'data-layVerify="required"';
        $data['comment']   = $info['comment'] ? "\n\t\t\t\t\t\t\t" . 'data-placeholder="请选择' . $info['comment'] . '"' : '';
        $data['direction'] = $info['addition']['direction'] ? "\n\t\t\t\t\t\t\t" . 'data-direction="' . $info['addition']['direction'] . '"' : '';
        $data['radio']     = ($info['addition']['single_multi_type'] === 'single') ? "true" : "false";
        $data['max']       = $info['addition']['max'] ? 'data-max="' . $info['addition']['max'] . '"' : '';
        return $this->getReplacedTpl($name, $data);
    }

    public function getSearchLinkageSelectHtml($info)
    {
        $name                    = 'html' . DS . 'search' . DS . 'linkage_select';
        $data['field']           = $info['field'];
        $data['comment']         = $info['comment'];
        $midName                 = str_replace('/', '.', $this->getMidName($info['addition']['table_id']));
        $data['url']             = $this->compatibleApiRoute($midName) . '/index';
        $data['leftField']       = $info['addition']['left_field'];
        $data['rightField']      = $info['addition']['right_field'];
        $data['showField']       = $info['addition']['show_field'];
        $data['searchField']     = $info['addition']['search_field'];
        $data['searchCondition'] = $info['addition']['search_condition'];
        $data['searchVal']       = $info['addition']['search_val'];
        return $this->getReplacedTpl($name, $data);
    }

    public function getLinkageSelectHtml($info, $type)
    {
        $name                    = 'html' . DS . $type . DS . 'linkage_select';
        $data['field']           = $info['field'];
        $data['verify']          = ($info['is_empty'] == 1) ? "" : "required";
        $data['comment']         = $info['comment'];
        $midName                 = str_replace('/', '.', $this->getMidName($info['addition']['table_id']));
        $data['url']             = $this->compatibleApiRoute($midName) . '/index';
        $data['leftField']       = $info['addition']['left_field'];
        $data['rightField']      = $info['addition']['right_field'];
        $data['showField']       = $info['addition']['show_field'];
        $data['searchField']     = $info['addition']['search_field'];
        $data['searchCondition'] = $info['addition']['search_condition'];
        $data['searchVal']       = $info['addition']['search_val'];
        return $this->getReplacedTpl($name, $data);
    }

    public function getSearchLaydateHtml($info)
    {
        $name                = 'html' . DS . 'search' . DS . 'laydate';
        $data['field']       = $info['field'];
        $data['comment']     = $info['comment'];
        $data['laydateType'] = $info['addition']['date_type'];
        if ($info['data_type'] == 'integer') {
            if (in_array($info['addition']['date_type'], ['datetime', 'month', 'date'])) {
                $data['searchType'] = 'BETWEEN_STRTOTIME';
            } else {
                $data['searchType'] = 'BETWEEN';
            }
        } else {
            $data['searchType'] = 'BETWEEN';
        }
        return $this->getReplacedTpl($name, $data);
    }

    public function getLaydateHtml($info, $type)
    {
        $name                = 'html' . DS . $type . DS . 'laydate';
        $data['field']       = $info['field'];
        $data['comment']     = $info['comment'];
        $data['laydateType'] = $info['addition']['date_type'];
        $data['verify']      = ($info['is_empty'] == 1) ? "" : "required";
        return $this->getReplacedTpl($name, $data);
    }

    public function getSearchColorPickerHtml()
    {
        return "";
    }

    public function getColorPickerHtml($info, $type)
    {
        $name              = 'html' . DS . $type . DS . 'color_picker';
        $data['field']     = $info['field'];
        $data['comment']   = $info['comment'];
        $data['color']     = $info['addition']['color'];
        $data['predefine'] = isset($info['addition']['predefine']) ? "true" : "false";
        $data['colors']    = isset($info['addition']['colors']) ?
            json_encode($info['addition']['colors'], JSON_UNESCAPED_UNICODE) : '';
        $data['alpha']     = (isset($info['addition']['alpha']) && $info['addition']['alpha']) ? "true" : "false";
        $data['format']    = $info['addition']['format'];
        return $this->getReplacedTpl($name, $data);
    }

    public function getSearchUploadHtml()
    {
        return "";
    }

    public function getUploadHtml($info, $type)
    {
        $name            = 'html' . DS . $type . DS . 'upload';
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        $data['accept']  = $info['addition']['accept'];
        $data['width']   = ($info['addition']['accept'] == 'image' && $info['addition']['width']) ? "\n\t\t\t\t\t\t\t\tdata-width=\"" . $info['addition']['width'] . "\"" : "";
        $data['height']  = ($info['addition']['accept'] == 'image' && $info['addition']['height']) ? "\n\t\t\t\t\t\t\t\tdata-height=\"" . $info['addition']['height'] . "\"" : "";
        $data['multi']   = ($info['addition']['multi'] == 'single') ? "false" : "true";
        $data['max']     = intval($info['addition']['max']) ? "\n\t\t\t\t\t\t\t\tdata-max=\"" . intval($info['addition']['max']) . "\"" : "";
        $data['dir']     = $info['addition']['dir'] ? "\n\t\t\t\t\t\t\t\tdata-dir=\"" . $info['addition']['dir'] . "\"" : "";
        $data['url']     = $info['addition']['url'] ? "\n\t\t\t\t\t\t\t\tdata-url=\"" . $info['addition']['url'] . "\"" : "";
        $data['mime']    = $info['addition']['mime'] ? "\n\t\t\t\t\t\t\t\tdata-mime=\"" . $info['addition']['mime'] . "\"" : "";
        $data['size']    = $info['addition']['size'] ? "\n\t\t\t\t\t\t\t\tdata-size=\"" . $info['addition']['size'] . "\"" : "";
        $data['verify']  = ($info['is_empty'] == 1) ? "" : "\n\t\t\t\t\t\t\t\tdata-layVerify=\"required\"";
        $data['type']    = ($info['addition']['upload_type'] !== "local") ? "\n\t\t\t\t\t\t\tdata-type=\"" . $info['addition']['upload_type'] . "\"" : "";
        $data['viaServer'] = ($info['addition']['upload_type'] !== "local" && $info['addition']['via_server'] === "unVia") ? "\n\t\t\t\t\t\t\tdata-viaServer=\"unVia\"" : "";
        if($info['addition']['multi'] == 'single'){
            $data['uploadedInfo'] = "data-uploaded=\"{{# if(d.".$info['field']."_file){ }}{{=d.".$info['field']."_file.path}}{{# } }}\"
                             data-uploadedId=\"{{=d.".$info['field']."}}\"
                             data-uploadedFilename=\"{{# if(d.".$info['field']."_file){ }}{{=d.".$info['field']."_file.name}}{{# } }}\"";
        }else{
            $data['uploadedInfo'] = "data-uploaded=\"{{=d.".$info['field']."_file.path}}\"
                             data-uploadedId=\"{{=d.".$info['field']."}}\"
                             data-uploadedFilename=\"{{=d.".$info['field']."_file.filename}}\"";
        }
        return $this->getReplacedTpl($name, $data);
    }

    public function getSearchEditorHtml($info)
    {
        $name            = 'html' . DS . 'search' . DS . 'editor' . DS . $info['addition']['type'];
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    public function getEditorHtml($info, $type)
    {
        $name            = 'html' . DS . $type . DS . 'editor' . DS . $info['addition']['type'];
        $referer = request()->header('referer');
        $refDomain = '';
        if($referer){
            $refPath = parse_url($referer);
            $refDomain = $refPath['scheme'] . '://' . $refPath['host'];
        }
        $domain = request()->domain();
        if($refDomain && $domain && $refDomain != $domain){
            $data['iframeSrc'] = '/'.$info['addition']['type'].'.html';
        }else{
            $data['iframeSrc'] = '/admin/'.$info['addition']['type'].'.html';
        }
        if($info['addition']['upload_type'] != 'local'){
            if($type == 'add'){
                $data['iframeSrc'] .= '?upload_type='.$info['addition']['upload_type'];
            }else{
                $data['iframeSrc'] .= '?id='.$info['field'].'&upload_type='.$info['addition']['upload_type'];
            }
        }else{
            if($type == 'edit'){
                $data['iframeSrc'] .= '?id='.$info['field'];
            }
        }
        $data['field']   = $info['field'];
        $data['comment'] = $info['comment'];
        return $this->getReplacedTpl($name, $data);
    }

    /**
     * 写入到文件
     * @param string $name 模板文件名
     * @param array $data key=>value形式的替换数组
     * @param string $pathname 生成的绝对文件名
     * @return mixed
     */
    protected function writeToFile($name, $data, $pathname)
    {
        foreach ($data as $index => &$datum) {
            $datum = is_array($datum) ? '' : $datum;
        }
        unset($datum);
        $content = $this->getReplacedTpl($name, $data);

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0777, true);
        }
        return file_put_contents($pathname, $content);
    }

    /**
     * 获取替换后的数据
     * @param string $name 模板文件名
     * @param array $data key=>value形式的替换数组
     * @return string
     */
    protected function getReplacedTpl($name, $data = [])
    {
        foreach ($data as $index => &$datum) {
            $datum = is_array($datum) ? '' : $datum;
        }
        unset($datum);
        $search = $replace = [];
        foreach ($data as $k => $v) {
            $search[]  = "{%{$k}%}";
            $replace[] = $v;
        }
        $tplTrueName = $this->getTplTrueName($name);
        $tpl         = file_get_contents($tplTrueName);
        $content     = str_replace($search, $replace, $tpl);
        return $content;
    }

    /**
     * 获取模板文件真实路径
     * @param string $name 举例： $name = 'controller' . DS . 'edit';
     * @return string
     */
    protected function getTplTrueName($name)
    {
        return app()->getRootPath() . DS . 'plugin' . DS . 'curd' . DS . 'library' . DS . 'curd_template' . DS . $name . '.lt';
    }

    /**
     * 下划线转驼峰
     * @param $str
     * @return string|string[]|null
     */
    protected function underlineToCamel($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}