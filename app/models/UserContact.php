<?php

class UserContact extends Eloquent {

    protected $table = 'xyk_usercontact';
    public $timestamps = false;

    /**
     * 校验身份证
     * @AuthorHTL
     * @DateTime  2017-11-29T20:21:09+0800
     * Typecho Blog Platform
     * @param     string                   $idNo   身份证号码
     * @param     string                   $idName 真实姓名
     * @return    boolean                  true|false
     */
    public function checkIdCard($idNo, $idName)
    {
        $key = '4d5da73518ef402b93c074ad62b918e3';
        $value = '35c166c283ed47a09453bd0b51f7454b';
        $rcaRequestType = 1;
        
        $data = compact("key", "value", "rcaRequestType", "idNo", "idName");
        $url = 'https://authentic.yunhetong.com/authentic/authenticationPersonal';
        $result = json_decode($this->curlPost($url, $data));

        if (!$result || $result->code != 200) {
            return array('code' => 1106, 'msg' => '请求失败');
        }
        switch ($result->data->status) {
            case 1:
                return array('code' => 200, 'msg' => '认证一致');
                break;
            case 2:
                return array('code' => 1107, 'msg' => '认证不一致');
                break;
            case 3:
                return array('code' => 1108, 'msg' => '认证无结果');
                break;
            default:
                return array('code' => 1106, 'msg' => '请求失败');
                break;
        }
    }

    /**
     * post请求
     * @AuthorHTL
     * @DateTime  2017-11-29T20:23:14+0800
     * Typecho Blog Platform
     * @param     string                   $url   请求路由
     * @param     array                    $param 请求数据
     * @return    [type]                          [description]
     */
    public function curlPost($url, $param)
    {
        $ch = curl_init();//初始化curl
        curl_setopt($ch,CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;//输出结果
    }

}