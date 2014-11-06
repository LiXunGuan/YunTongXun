<?php
namespace Lixunguan\Yuntongxun;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Sdk{

	protected $config = array(
		'dataType' => 'json', // 数据类型 json 或xml
		'url'      => 'https://sandboxapp.cloopen.com:8883/2013-12-26', // 请求地址
		'appid'    => '', // 应用ID
		'sid'      => '', // 账户id
		'token'    => '', // 账户授权令牌
		'timesmap' => '', //时间戳
	);

	public function __construct(array $config){
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * 云通讯配置信息
	 * @param  array  $config
	 * @return object
	 */
	public static function init(array $config){
		if(!isset($config['timesmap'])) $config['timesmap'] = date("YmdHis");
		return new static($config);
	}



	public function sendSMS(){

	}

	/**
	 * 发送模板短信
	 * @param  integer  $templateId 模板ID
	 * @return xml/object
	 */
	public function sendTemplateSMS($templateId){
		# 请求包体
		$param = array(
			'to'         => $this->to,    // 短信接收端手机号码集合，用英文逗号分开，每批发送的手机号数量不得超过100个
			'appId'      => $this->appid, // 应用Id
			'templateId' => $templateId,      // 模板Id
			'datas'      => $this->datas  // 内容数据，用于替换模板中{序号}
		);
		if($this->dataType == 'json'){
			$body = json_encode($param);
		}else{
			$data = '';
			foreach ($this->datas as $k) {
				$data .= "<data>{$k}</data>";
			}
			$body = "
				<TemplateSMS>
					<to>{$this->to}</to>
					<appId>{$this->appid}</appId>
					<templateId>{$templateId}</templateId>
					<datas>".$data."</datas>
				</TemplateSMS>";
		}
		// 请求url
		$url    = "{$this->url}/Accounts/{$this->sid}/SMS/TemplateSMS?sig=" . $this->sign();
		// 请求头信息
		$header = array(
			'Accept'        => 'application/' . $this->dataType,
			'Content-Type'  => 'application/' . $this->dataType,
			'charset'       => 'utf-8',
			'Authorization' => $this->authen()
		);

		$client = new Client();

		try {

			$response = $client->post($url, array(
				'headers' => $header,
				'body'    => $body
			));

			return ($this->dataType == 'json') ? $response->json(array('object' =>true)) : $response->xml();

		} catch (RequestException $e) {
			throw $e;
		}


	}

	/**
	 * 号码
	 * 1、发送短信时：多个号码用逗号隔开（每批发送的手机号数量不得超过100个）
	 * 2、双向呼叫时：被叫为座机时需要添加区号，如：01052823298；被叫为分机时分机号由‘-’隔开，如：01052823298-3627
	 * @param  string $to
	 * @return object
	 */
	public function to($to){
		$this->to = $to;
		return $this;
	}


	public function with($key){
		$this->datas = $key;
		return $this;
	}

	/**
	 * url 请求签名
	 * @return string
	 */
	private function sign(){
		return strtoupper(md5($this->sid . $this->token . $this->timesmap));
	}

	/**
	 * 请求头部 验证
	 * @return string
	 */
	private function authen(){
		return base64_encode( $this->sid . ':' . $this->timesmap );
	}

	public function __get($key){
		if(array_key_exists($key, $this->config)){
			return $this->config[$key];
		}
	}

	public function __set($key, $value){
		$this->config[$key] = $value;
	}
}