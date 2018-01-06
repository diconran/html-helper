<?php

/**
 * @copyright @diconran
 */

class HtmlHelper
{
    private $model;
    private $modelHelper;

    public function getModel()
    {
        return $this->model;
    }

    public function getModelHelper()
    {
        return $this->modelHelper;
    }


    function __construct($model = null)
    {
        if(! is_object($model) )
        {
            $model = new stdClass;
        }

        $this->model = $model;
        $this->modelHelper = new ModelHelper($model);
    }


    private static function setName( $name, &$attr )
    {
        if( !isset( $attr['name'] ) ) $attr['name'] = $name;
        if( !isset( $attr['id'] ) ) $attr['id'] = $name;
    }


    public function checkBox($name, $checked = null, $attr = array())
    {
        if( !is_bool($checked) )
        {
            $checked = $this->modelHelper->hasValue($name);
        }

        $addAttr = array();

        // nameが配列である場合を想定する
        if( $checked )
        {
            $addAttr['checked'] = 'checked';
            $addAttr['value'] = 'on';
        }

        $attributes = self::toAttrString($name, $attr, $addAttr);

        return "<input type=\"checkbox\" {$attributes}/>";
    }


    public function edit($name, $attr = array())
    {
        $text = $this->modelHelper->toString($name);
        $addAttr = array('value' => $text);
        $attributes = self::toAttrString($name, $attr, $addAttr);

        return "<input type=\"text\" {$attributes} />";
    }


    /**
     * 
     */
    private function selectItem($name, $list, $callback)
    {
        $values = $this->modelHelper->toArray($name);
        $tag = '';
        foreach($list as $item)
        {
            $item->selected = in_array($item->value, $values);
            $tag .= $callback($item);
        }
        return $tag;
    }

    /**
     * @param IEnumerable<SelectListItem> $list
     */
    public function select($name, $list, $label = null, $isMulti = false, $attr = array())
    {
        $val = $this->modelHelper->getValue($name);
        $addAttr = array();
        $labelTag = '';

        /**
         * 
         * バグ：is_array($val)は、要素が0の時は配列で取得出来ない。
         * 
         */
        if( $isMulti || is_array($val) )
        {
            $addAttr = array(
                'name' => self::arrayName($name),
                'id' => $name,
                'multiple' => 'multiple'
            );
        }
        else
        {
            if( isset($label) )
            {
                $label = self::escHtml($label);
                $labelTag = "<option>{$label}</option>";
            }
        }


        $attributes = self::toAttrString($name, $attr, $addAttr);

        $tag = "<select {$attributes}>";
        
        $tag .= $labelTag;

        $tag .= $this->selectItem(
            $name,
            $list,
            function($item){
                $v = self::escAttr($item->value);
                $t = self::escAttr($item->text);
                $s = $item->selected ? ' selected="selected"' : '';
                return "<option value=\"{$v}\" {$s}>{$t}</option>";
            });
        $tag .= '</select>';

        return $tag;
    }


    public function radioButton($name, $value, $attr = array())
    {
        $val = $this->modelHelper->toString($name);
        $addAttr = array();

        if( $val )
        {
            if( $val === $value )
            {
                $addAttr = array( 'checked' => 'checked' );
            }
        }
        
        $addAttr['value'] = $value;

        $attributes = self::toAttrString($name, $attr, $addAttr);
        return "<input type=\"radio\" {$attributes} />";
    }




    /**
     * @param string $name 値は文字列である必要があります。
     * @param IEnumerable<SelectListItem> $list
     * @param Func<SelectListItem,string,string> $callback
     */
    public function radioButtonList($name, $list, $callback = null, $attr = array())
    {
        $addAttr = array();
        $callback = isset($callback) ? $callback : function($item, $tag){
            $text = self::escHtml($item->text);
            return "<div class=\"radio-button-list-for\"><label>{$tag} {$text} </label></div>";
        };

        $attributes = self::toAttrString($name, $attr, $addAttr);

        $tag = '';

        $tag .= $this->selectItem(
            $name,
            $list,
            function($item) use($name, $callback)
            {
                $rTag = $this->radioButton($name, $item->value);
                if( isset($callback) ) $rTag = $callback($item, $rTag);
                return $rTag;
            }
        );

        return $tag;


    }

    public function checkBoxList($name, $list, $callback = null, $attr = array())
    {
        $addAttr = array();

        $callback = isset($callback) ? $callback : function($item, $tag){
            $text = self::escHtml($item->text);
            return "<div class=\"check-box-list-for\"><label>{$tag} {$text}</label></div>";
        };

        $attributes = self::toAttrString($name, $attr, $addAttr);

        $tag = '';

        $tag .= $this->selectItem(
            $name,
            $list,
            function($item) use($name, $callback)
            {
                $attr = array();
                $attr['name'] = self::arrayName($name);
                $attr['value'] = $item->value;
                $rTag = $this->checkBox($name, $item->selected, $attr);
                if( isset($callback) ) $rTag = $callback($item, $rTag);
                return $rTag;
            }
        );

        return $tag;


    }



    public static function isPostBack()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function escHtml($text)
    {
        return htmlspecialchars($text, ENT_NOQUOTES);
    }

    public static function escAttr($text)
    {
        return htmlspecialchars($text);
    }

    public static function arrayName($name)
    {
        return "{$name}[]";
    }

    public static function inArray($value, $array)
    {
        //
        // バグ対応する
        //
        return is_array($array) && in_array($value, $array);
    }


    /**
     * ここはゴミ。
     * 後で必要なら変更する
     */
    private static function toAttrString($name, $attr, $addAttr = array())
    {
        $str = '';

        if(is_array($attr))
        {

            if(is_array($addAttr))
            {
                $attr = array_merge($addAttr, $attr);
            }

            self::setName( $name, $attr );

            foreach($attr as $p => $v)
            {
                $v = self::escAttr($v);
                $str .= " {$p}=\"{$v}\"";
            } 
        }
        return $str;
    }

}

class SelectListItem
{
    public $selected;
    public $value;
    public $text;

    function __construct($value = '', $text = '', $selected = false)
    {
        $this->selected = $selected;
        $this->value = $value;
        $this->text = isset($text) ? $text : $value;
    }
}



/**
 * $_POSTの要素名をオブジェクトのプロパティのように振る舞います。
 * POSTが配列で送られて来た場合は優先的に配列型になります。
 */
class PostObject
{

    /**
     * @var IEnumerable<IPropertyGetter>
     */
    private $getters;
    
    function __construct($names = array())
    {
        $this->getters = array();
        $this->initGetters($names);
    }

    /**
     * input.name が例えば「name[]」のような配列を表す名前の場合、
     * また、コンストラクタの$namesのリストに含まれる場合に配列として取得します。
     */
    protected function initGetters($names)
    {
        $this->getters[] = new NameGetter();
        $this->getters[] = new ListGetter($names);
    }

    private function getGettersValue($name)
    {
        
        foreach( $this->getters as $getter )
        {
            if( $getter->hasValue($name) )
            {
                return $getter->getValue($name);
            }
        }

        return self::getPost($name);
    }

    function __get($name)
    {
        /**
         * getterからPostObject#__get()が呼び出されないように対策する必要あり。
         */
        return $this->getGettersValue($name);
    }

    function __isset($name)
    {
        return isset($_POST[$name]);
    }
    
    public static function getPost($name)
    {
        return self::hasPost($name) ? $_POST[$name] : null;
    }

    public static function hasPost($name)
    {
        return isset($_POST[$name]);
    }
}

interface IPropertyGetter
{
    public function hasValue($name);
    public function getValue($name);
}

final class NameGetter implements IPropertyGetter
{
    public function hasValue($name)
    {
        return is_array( PostObject::getPost($name) );
    }

    public function getValue($name)
    {
        return PostObject::getPost($name);
    }

}

final class ListGetter implements IPropertyGetter
{

    private $names;

    public function &getNames()
    {
        return $this->names;
    }
    function __construct(&$names)
    {
        $this->names = &$names;
    }

    public function hasValue($name)
    {
        return in_array($name, $this->names);
    }

    public function getValue($name)
    {
        $value = PostObject::hasPost($name) ? PostObject::getPost($name) : null;
        return TypeConverter::getCurrent()->convertArray($value);
    }

}


/**
 * オブジェクト(PostObject含む)をラップします。
 * モデルへのアクセスを安全に行います。
 */
class ModelHelper
{

    private $model;
    private $typeConverter;

    function __construct($model, $typeConverter = null)
    {
        if( !is_object($model) ) throw new Exception('$model is not object');
        $this->model = $model;
        if(!($typeConverter instanceof TypeConverter))
        {
            $this->typeConverter = new TypeConverter();
        }
    }

    public function isString($name)
    {
        return is_string($this->getValue($name));
    }

    public function isArray($name)
    {
        return is_array($this->getValue($name));
    }

    public function isBool($name)
    {
        return is_bool($this->getValue($name));
    }

    

    /**
     * TypeConverterにより配列に変換して取得します。
     * 必ず0以上の配列で取得出来るようにする必要があります。
     */
    final public function toArray($name)
    {
        $value = $this->hasValue($name) ? $this->getValue($name) : null;
        return $this->typeConverter->convertArray($value);
    }

    /**
     * TypeConverterにより文字列に変換して取得します。
     * TypeConverterの変換リストに無い型はnullを返します。
     */
    final public function toString($name)
    {
        $value = $this->hasValue($name) ? $this->getValue($name) : null;
        return $this->typeConverter->convertString($value);
    }

    /**
     * Modelのプロパティ値を取得できるか調べます。
     */
    public function hasValue($name)
    {
        return isset($this->model->$name);
    }

    /**
     * モデルの生のデータを取得します。
     * プロパティが無い場合の動作は保証されません。
     */
    public function getValue($name)
    {
        return $this->model->$name;
    }

}





/**
 * HtmlHelperでは簡素化のため、文字列、文字列の配列、nullのいづれかの値を取るようになってます。
 * TypeConverterはそれら以外の型を変換するためのデフォルトの機能です。
 * ModelHelperによって使用されます。
 */
class TypeConverter
{

    private static $current;
    private $converters = [];

    public function &getConverters()
    {
        return $this->converters;
    }

    public static function getCurrent()
    {
        if( !isset(self::$current) )
        {
            self::$current = new TypeConverter();
        }
        return self::$current;
    }

    public function __construct()
    {
        $this->initConverters();
    }

    /**
     * コンバーターの初期化設定。
     * StringTypeConverter, BoolTypeConverter, NumericTypeConverter, DefaultTypeConverterの順で追加されます。
     */
    protected function initConverters()
    {
        $this->converters[] = new StringTypeConverter();
        $this->converters[] = new BoolTypeConverter();
        $this->converters[] = new NumericTypeConverter();
        $this->converters[] = new DefaultTypeConverter();
    }

    /**
     * 必ず配列で返します。
     * nullは返しません。空の配列を返します。
     */
    public function convertArray($value)
    {
        if( is_array($value) )
        {
            return $this->cnvElements( $value );
        }

        if( !isset($value) )
        {
            return [];
        }

        return [$this->typeToString($value)];
    }

    private function cnvElements( $arr )
    {
        $r = [];

        foreach($arr as $key => $value)
        {
            $c = $this->typeToString($value);
            if( is_int($key) && isset($c) )
            {
                $r[] = $c;
            }
        }

        return $r;
    }

    public function convertString($value)
    {
        return $this->typeToString($value);
    }


    private function typeToString($value)
    {
        return self::convert($this->converters, $value);
    }

    /**
     * 文字列に変換します。変換できない場合はnullを返します。
     */
    public static function convert($converters, $value)
    {
        foreach($converters as $converter)
        {
            if( $converter->canConvert($value) )
            {
                return $converter->convert($value);
            }
        }

        // 例外を返すか悩むところ。
        return null;
    }

}

interface ITypeConverter
{
    /**
     * @return bool
     */
    public function canConvert($value);

    /**
     * @return string
     */
    public function convert($value);
}

final class BoolTypeConverter implements ITypeConverter
{
    public function canConvert($value)
    {
        return is_bool($value);
    }

    public function convert($value)
    {
        return $value ? 'on' : '';
    }
}

final class NumericTypeConverter implements ITypeConverter
{
    public function canConvert($value)
    {
        return is_numeric($value);
    }

    public function convert($value)
    {
        return (string)$value;
    }

}

final class StringTypeConverter implements ITypeConverter
{
    public function canConvert($value)
    {
        return is_string($value);
    }

    public function convert($value)
    {
        return (string)$value;
    }

}

final class DefaultTypeConverter implements ITypeConverter
{
    public function canConvert($value)
    {
        return true;
    }

    public function convert($value)
    {
        return null;
    }
}




