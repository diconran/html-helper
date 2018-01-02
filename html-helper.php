<?php

/**
 * @copyright @diconran
 */

class HtmlHelper
{
    private $model;

    public function getModel()
    {
        return $this->model;
    }

    function __construct($model = null)
    {
        if(! is_object($model) )
        {
            $model = new stdClass;
        }

        $this->model = &$model;
    }

    private function getString($name)
    {
        if( $this->hasValue($name) )
        {
            return (string)$this->getValue($name);
        }
        return '';
    }

    private function getArray($name)
    {
        if( $this->hasValue($name) )
        {
            $value = $this->getValue($name);

            if( is_array($value) )
            {
                return $value;
            }
            return [$value];
        }
        return [];
    }

    private function hasValue($name)
    {
        return isset($this->model->$name);
    }

    private function getValue($name)
    {
        return $this->model->$name;
    }


    private static function toAttrString($name, $attr, $addAttr = array())
    {
        $str = '';

        if(is_array($attr))
        {

            if(is_array($addAttr))
            {
                $attr = array_merge($attr, $addAttr);
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

    private static function setName( $name, &$attr )
    {
        if( !isset( $attr['name'] ) ) $attr['name'] = $name;
        if( !isset( $attr['id'] ) ) $attr['id'] = $name;
    }


    public function checkBox($name, $checked = null, $attr = array())
    {
        $addAttr = $checked ? array('checked' => 'checked') : null;
        $attributes = self::toAttrString($name, $attr, $addAttr);

        return "<input type=\"checkbox\" {$attributes}/>";
    }

    public function checkBoxFor($name, $attr = array())
    {
        $checked = $this->getString($name);
        return $this->checkBox($name, $checked, $attr);
    }

    public function edit($name, $text, $attr = array())
    {
        $addAttr = array('value' => $text);
        $attributes = self::toAttrString($name, $attr, $addAttr);

        return "<input type=\"text\" {$attributes} />";
    }

    public function editFor($name, $attr = array())
    {
        $text = $this->getString($name);
        return $this->edit($name, $text, $attr);
    }

    private function selectItemFor($name, $list, $callback)
    {
        $values = $this->getArray($name);
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
    public function selectFor($name, $list, $label = null, $isMulti = false, $attr = array())
    {
        $val = $this->getValue($name);
        $addAttr = array();
        $labelTag = '';

        if( $isMulti || is_array($val) )
        {
            $addAttr = array(
                'name' => "{$name}[]",
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

        $tag .= $this->selectItemFor(
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


    public function radioButtonFor($name, $value, $attr = array())
    {
        $val = $this->getString($name);
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
     * 
     * @param IEnumerable<SelectListItem> $list
     * @param Func<SelectListItem,string,string> $callback
     */
    public function radioButtonListFor($name, $list, $callback = null, $attr = array())
    {
        $value = $this->getString($name);
        $addAttr = array();
        $callback = isset($callback) ? $callback : function($item, $tag){
            $text = self::escHtml($item->text);
            return "{$tag} {$text} <br />";
        };

        $attributes = self::toAttrString($name, $attr, $addAttr);

        $tag = '';

        $tag .= $this->selectItemFor(
            $name,
            $list,
            function($item) use($name, $callback)
            {
                $rTag = $this->radioButtonFor($name, $item->value);
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
 * $_POSTの値をオブジェクトのプロパティのように振る舞います。
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
        if( !isset($_POST[$name]) ) return null;

        // 配列として値を取得
        foreach( $this->getters as $getter )
        {
            if( $getter->hasValue($name) )
            {
                return $getter->getValue($name);
            }
        }

        return $_POST[$name];
    }

    function __get($name)
    {
        return $this->getGettersValue($name);
    }

    function __isset($name)
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
        return is_array( $_POST[$name] );
    }

    public function getValue($name)
    {
        return $_POST[$name];
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
        $value = $_POST[$name];

        if( is_array($value) )
        {
            return $value;
        }

        return [$value];
    }

}


