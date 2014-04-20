<?php

/**
 * 微信接口测试
 */
class WechatTest {

    private $signature = '';
    private $timestamp = '';
    private $nonce     = '';

    const TOKEN = 'happy';

    function __construct() {
        $this->signature = $_GET['signature'];
        $this->timestamp = $_GET['timestamp'];
        $this->nonce     = $_GET['nonce'];
    }

    /**
     * 检测接口信息是否通过
     */
    public function validate() {
        $testArr = array(self::TOKEN, $this->timestamp, $this->nonce);
        sort($testArr);
        $testStr = join($testArr);
        $testStr = sha1($testStr);
        if ($this->signature === $testStr) {
            echo $_GET['echostr'];
        }
    }

    /**
     * 收到粉丝消息后，返回相关信息
     */
    public function responseData() {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)) {
            $postObj      = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername   = $postObj->ToUserName;
            $keyword      = trim($postObj->Content);
            $time         = time();
            $textTpl      = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0<FuncFlag>
            </xml>";
            if (!empty($keyword)) {
                $msgType   = "text";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $keyword);
                echo $resultStr;
            } else {
                echo "Input something...";
            }
        } else {
            echo "";
            exit;
        }
    }

}

/**
 * 快速格式化输出数组
 */
function p($arr) {
    echo '<pre>',print_r($arr, true),'</pre>';
}

//引入微信类
include 'wechat.class.php';

//初始化为微信公众平台的Token
$options = array(
    'token' => 'happy'
);

//实例化微信类
$wx  = new Wechat($options);

//接口校验
$wx->valid();

//获取消息类型和、内容和消息事件类型
$event = $wx->getRev()->getRevEvent();
$type = $wx->getRev()->getRevType();
$content = $wx->getRev()->getRevContent();

//是关注事件则发送相关提示信息
if ($event['event'] == 'subscribe') {
    $wx->text($this->welcomeInfo)->reply();
    exit;
}

//保存用户发送的信息
$data = array();
$data = array(
    'content'   =>  $content,
    'send_date' =>  time()
);

//初始化PDO数据库类
include 'PDO.class.php';
$pdo = PDOAction::getInstance();

//记录用户发送的信息
$pdo->insert($data);

//判断是否进入子菜单
if ($content == '5') {
    $pdo->setField('is_sub', '1', 'wx_sub');
}

//判断是否返回主菜单
if ($content == '9') {
    $pdo->setField('is_sub', '0', 'wx_sub');
}

//判断子菜单模式是否开启
$subs = $pdo->select('select * from wx_sub where id = 5');
$isSub = $subs[0]['is_sub'];
 
//按照指定消息类型回复给用户
switch ($type) {
    case Wechat::MSGTYPE_TEXT:
        $wx->help($content, $isSub);
        exit;
    case Wechat::MSGTYPE_EVENT:
        exit;
    case Wechat::MSGTYPE_IMAGE:
        $wx->text('图片消息啊！')->reply();
        exit;
    default:
        $wx->text("help info")->reply();
}
    