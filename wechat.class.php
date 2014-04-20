<?php

/**
 * 微信公众平台PHP-SDK开发
 */
class Wechat {

    const MSGTYPE_TEXT     = 'text';
    const MSGTYPE_IMAGE    = 'image';
    const MSGTYPE_LOCATION = 'location';
    const MSGTYPE_LINK     = 'link';
    const MSGTYPE_EVENT    = 'event';
    const MSGTYPE_MUSIC    = 'music';
    const MSGTYPE_NEWS     = 'news';

    private $token;
    private $_msg;
    private $_funcflag  = false;
    private $_receive;
    public $debug       = false;
    private $_logcallback;
    //定义欢迎信息
    public $welcomeInfo = "感谢您关注我的微信账号！
如果要用一种东西来记录我的生命历程，我会用「朋友」！
1：查看我的新浪微博；
2：查看我的腾讯微博；
3：查看我的qq号码；
4：查看我的个人博客；
5：查看最近天气信息；
6：查看附近的人信息；
7：查看地理位置信息；";

    public function __construct($options) {
        $this->token        = isset($options['token']) ? $options['token'] : '';
        $this->debug        = isset($options['debug']) ? $options['debug'] : false;
        $this->_logcallback = isset($options['logcallback']) ? $options['logcallback'] : false;
    }

    /**
     * For weixin server validation 
     */
    private function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce     = $_GET["nonce"];

        $token  = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * For weixin server validation 
     * @param bool $return 是否返回
     */
    public function valid($return = false) {
        $echoStr = isset($_GET["echostr"]) ? $_GET["echostr"] : '';
        if ($return) {
            if ($echoStr) {
                if ($this->checkSignature())
                    return $echoStr;
                else
                    return false;
            } else
                return $this->checkSignature();
        } else {
            if ($echoStr) {
                if ($this->checkSignature())
                    die($echoStr);
                else
                    die('no access');
            } else {
                if ($this->checkSignature())
                    return true;
                else
                    die('no access');
            }
        }
        return false;
    }

    /**
     * 设置发送消息
     * @param array $msg 消息数组
     * @param bool $append 是否在原消息数组追加
     */
    public function Message($msg = '', $append = false) {
        if (is_null($msg)) {
            $this->_msg = array();
        } elseif (is_array($msg)) {
            if ($append)
                $this->_msg = array_merge($this->_msg, $msg);
            else
                $this->_msg = $msg;
            return $this->_msg;
        } else {
            return $this->_msg;
        }
    }

    public function setFuncFlag($flag) {
        $this->_funcflag = $flag;
        return $this;
    }

    private function log($log) {
        if ($this->debug) {
            if (function_exists($this->_logcallback)) {
                if (is_array($log))
                    $log = print_r($log, true);
                return call_user_func($this->_logcallback, $log);
            }elseif (class_exists('Log')) {
                Log::write('wechat：' . $log, Log::DEBUG);
            }
        }
        return false;
    }

    /**
     * 获取微信服务器发来的信息
     */
    public function getRev() {
        $postStr = file_get_contents("php://input");
        $this->log($postStr);
        if (!empty($postStr)) {
            $this->_receive = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this;
    }

    /**
     * 获取消息发送者
     */
    public function getRevFrom() {
        if ($this->_receive)
            return $this->_receive['FromUserName'];
        else
            return false;
    }

    /**
     * 获取消息接受者
     */
    public function getRevTo() {
        if ($this->_receive)
            return $this->_receive['ToUserName'];
        else
            return false;
    }

    /**
     * 获取接收消息的类型
     */
    public function getRevType() {
        if (isset($this->_receive['MsgType']))
            return $this->_receive['MsgType'];
        else
            return false;
    }

    /**
     * 获取消息ID
     */
    public function getRevID() {
        if (isset($this->_receive['MsgId']))
            return $this->_receive['MsgId'];
        else
            return false;
    }

    /**
     * 获取消息发送时间
     */
    public function getRevCtime() {
        if (isset($this->_receive['CreateTime']))
            return $this->_receive['CreateTime'];
        else
            return false;
    }

    /**
     * 获取接收消息内容正文
     */
    public function getRevContent() {
        if (isset($this->_receive['Content']))
            return $this->_receive['Content'];
        else
            return false;
    }

    /**
     * 获取接收消息图片
     */
    public function getRevPic() {
        if (isset($this->_receive['PicUrl']))
            return $this->_receive['PicUrl'];
        else
            return false;
    }

    /**
     * 获取接收消息链接
     */
    public function getRevLink() {
        if (isset($this->_receive['Url'])) {
            return array(
                'url'         => $this->_receive['Url'],
                'title'       => $this->_receive['Title'],
                'description' => $this->_receive['Description']
            );
        } else
            return false;
    }

    /**
     * 获取接收地理位置
     */
    public function getRevGeo() {
        if (isset($this->_receive['Location_X'])) {
            return array(
                'x'     => $this->_receive['Location_X'],
                'y'     => $this->_receive['Location_Y'],
                'scale' => $this->_receive['Scale'],
                'label' => $this->_receive['Label']
            );
        } else
            return false;
    }

    /**
     * 获取接收事件推送
     */
    public function getRevEvent() {
        if (isset($this->_receive['Event'])) {
            return array(
                'event' => $this->_receive['Event'],
                'key'   => $this->_receive['EventKey'],
            );
        } else
            return false;
    }

    public static function xmlSafeStr($str) {
        return '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $str) . ']]>';
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    public static function data_to_xml($data) {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= ( is_array($val) || is_object($val)) ? self::data_to_xml($val) : self::xmlSafeStr($val);
            list($key, ) = explode(' ', $key);
            $xml .= "</$key>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public function xml_encode($data, $root = 'xml', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
        if (is_array($attr)) {
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml .= "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * 设置回复消息
     * Examle: $obj->text('hello')->reply();
     * @param string $text
     */
    public function text($text = '') {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg      = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_TEXT,
            'Content'      => $text,
            'CreateTime'   => time(),
            'FuncFlag'     => $FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复音乐
     * @param string $title
     * @param string $desc
     * @param string $musicurl
     * @param string $hgmusicurl
     */
    public function music($title, $desc, $musicurl, $hgmusicurl = '') {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg      = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'CreateTime'   => time(),
            'MsgType'      => self::MSGTYPE_MUSIC,
            'Music'        => array(
                'Title'       => $title,
                'Description' => $desc,
                'MusicUrl'    => $musicurl,
                'HQMusicUrl'  => $hgmusicurl
            ),
            'FuncFlag'     => $FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复图文
     * @param array $newsData 
     * 数组结构:
     *  array(
     *  	[0]=>array(
     *  		'Title'=>'msg title',
     *  		'Description'=>'summary text',
     *  		'PicUrl'=>'http://www.domain.com/1.jpg',
     *  		'Url'=>'http://www.domain.com/1.html'
     *  	),
     *  	[1]=>....
     *  )
     */
    public function news($newsData = array()) {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $count    = count($newsData);

        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_NEWS,
            'CreateTime'   => time(),
            'ArticleCount' => $count,
            'Articles'     => $newsData,
            'FuncFlag'     => $FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 
     * 回复微信服务器, 此函数支持链式操作
     * Example: $this->text('msg tips')->reply();
     * @param string $msg 要发送的信息, 默认取$this->_msg
     * @param bool $return 是否返回信息而不抛出到浏览器 默认:否
     */
    public function reply($msg = array(), $return = false) {
        if (empty($msg))
            $msg     = $this->_msg;
        $xmldata = $this->xml_encode($msg);
        $this->log($xmldata);
        if ($return)
            return $xmldata;
        else
            echo $xmldata;
    }

    /**
     * 根据接收到的内容，提取城市，获取天气信息。
     * 如果城市不存在或者信息有误，都返回北京的天气
     * @return string
     */
    public function weather($content) {
        if (!empty($content)) {
            $msgType             = "text";
            $post_data           = array();
            $post_data['city']   = $content;
            $post_data['submit'] = "submit";
            $url                 = 'http://search.weather.com.cn/wap/search.php';
            $o                   = "";
            foreach ($post_data as $k => $v) {
                $o.= "$k=" . urlencode($v) . "&";
            }
            $post_data    = substr($o, 0, -1);
            $ch           = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $result       = curl_exec($ch);
            curl_close($ch);
            $result       = explode('/', $result);
            $result       = explode('.', $result['5']);
            $citynum      = $result['0'];
            $weatherurl   = "http://m.weather.com.cn/data/" . $citynum . ".html";
            $weatherjson  = file_get_contents($weatherurl);
            $weatherarray = json_decode($weatherjson, true);
            $weatherinfo  = $weatherarray['weatherinfo'];
            $contentTpl   = "#这里是%s#(%s)
%s%s
%s时发布的天气预报：
今天天气：%s
%s，%s
穿衣指数：%s
紫外线指数：%s
洗车指数：%s
明天天气：%s
%s，%s
后天天气：%s
%s，%s";
            return sprintf($contentTpl, $weatherinfo['city'], $weatherinfo['city_en'], $weatherinfo['date_y'], $weatherinfo['week'], $weatherinfo['fchh'], $weatherinfo['temp1'], $weatherinfo['weather1'], $weatherinfo['wind1'], $weatherinfo['index_d'], $weatherinfo['index_uv'], $weatherinfo['index_xc'], $weatherinfo['temp2'], $weatherinfo['weather2'], $weatherinfo['wind2'], $weatherinfo['temp3'], $weatherinfo['weather3'], $weatherinfo['wind3']);
        } else {
            return "Input something...";
        }
    }

    /**
     * 输出首页菜单选项
     */
    public function menu($content) {
        switch ($content) {
            case '1':
                $this->text('http://www.weibo.com/yangbai1988')->reply();
                break;
            case '2':
                $this->text('http://t.qq.com/letianpai50')->reply();
                break;
            case '3':
                $this->text('563508762')->reply();
                break;
            case '4':
                $this->text('http://www.yangbai6644.com')->reply();
                break;
            case '5':
                $this->text('请输入城市信息（城市不存在的话默认就返回北京的天气,"9"返回主菜单）：')->reply();
                break;
            default:
                $this->text('亲，没有这个选项哦。')->reply();
                break;
        }
    }

    public function help($content, $isSub = false) {
        switch (true) {
            case $content == '5':
                $this->menu($content);
                break;
            case $isSub == '1':
                $this->text($this->weather($content))->reply();
                break;
            case $content == '9':
                $this->text($this->welcomeInfo)->reply();
                break;
            default:
                $this->menu($content);
                break;
        }
    }

}
