<html>
<head>
<title>html-helper exsample</title>
<meta charset="utf-8" />
<style type="text/css">
.put-style{
    color: red;
    border: 1px solid red;
}
</style>
</head>
<body>


<?php echo date('H:i:s'); ?>
<hr />

<form method="POST">

<?php
include '../html-helper/html-helper.php';


$obj = null; // GET/POSTで変わるオブジェクト。
$html = null; // HtmlHelper

if(HtmlHelper::isPostBack())
{
    // $_POST wrapper
    $obj = new PostObject(['lb']);
    $html = new HtmlHelper($obj);
}
else
{
    // default settings
    $obj = new stdClass;
    $obj->ck = true; // CheckBox
    $obj->ed = 'デフォルトのメッセージ'; // Edit
    $obj->rb = 'csharp'; //Radio
    $obj->dp = ""; // Select(single)
    $obj->lb = ['banana']; // Select(Multi)
    $obj->rbl = 'banana'; // RadioButtonList
    $obj->checks = ['apple', 'grape']; // CheckBoxList

    $html = new HtmlHelper($obj);
}

// data of list type controls
$fruits = [
    new SelectListItem('apple', 'りんご'),
    new SelectListItem('banana', 'バナナ'),
    new SelectListItem('grape', 'ぶどう')
];

function put($name)
{
    global $html;

    $r = '';
    $val = $html->getModelHelper()->getValue($name);

    if(is_array($val))
    {
        $r = HtmlHelper::escHtml(implode(',', $val));
    }
    else
    {
        $val = $html->getModelHelper()->toString($name);
        $r = HtmlHelper::escHtml($val);
    }
    echo "<div class=\"put-style\">{$name} : {$r}</div>";
}

?>

<p>CheckBox</p>
<?= $html->checkBox('ck'); ?>
<?php put('ck'); ?>
<hr />

<p>Edit</p>
<?= $html->edit('ed'); ?>
<?php put('ed'); ?>
<hr />

<p>Radio</p>
<?= $html->radioButton('rb', 'Ruby'); ?>Ruby, 
<?= $html->radioButton('rb', 'csharp'); ?>C#, 
<?= $html->radioButton('rb', 'java'); ?>Java, 
<?= $html->radioButton('rb', 'php'); ?>PHP, 
<br />
<?php put('rb'); ?>
<hr />

<p>Select(single)</p>
<?= $html->select('dp', $fruits, "選択してください。"); ?>
<?php put('dp'); ?>
<hr />

<p>Select(multi) ... new PostObject(['lb']) / $obj->lst = ['banana']</p>
<?= $html->select('lb', $fruits); ?>
<?php put('lb'); ?>
<hr />


<p>RadioButtonList</p>
<?= $html->radioButtonList('rbl', $fruits); ?>
<?php put('rbl'); ?>
<hr />

<p>CheckBoxList</p>
<?= $html->checkBoxList('checks', $fruits); ?>
<?php put('checks'); ?>
<hr />


<input type="submit" value="そうしんする" />
</form>

</body>
</html>
