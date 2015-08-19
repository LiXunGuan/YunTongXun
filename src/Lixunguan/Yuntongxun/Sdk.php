<?php
namespace Lixunguan\Yuntongxun;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Sdk
{
	/**
	 * 应用ID
	 * @var string
	 */
	protected $appId;

	/**
	 * 主帐号,对应开官网发者主账号下的 ACCOUNT SID
	 * @var string
	 */
	protected $sid;

	/**
	 * 主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
	 * @var string
	 */
	protected $token;

	/**
	 * 时间戳
	 * @var date
	 */
	protected $timesmap;

	/**
	 * 基础url
	 * 沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
	 * 生产环境（用户应用上线使用）：app.cloopen.com
	 */
	const BASE_URL = 'https://app.cloopen.com:8883/2013-12-26';
	
	public function __construct($appId, $sid, $token)
	{
		$this->appId    = $appId;
		$this->sid      = $sid;
		$this->token    = $token;
		$this->timesmap = date("YmdHis");
	}

	/**
	 * 验证参数，请求URL必须带有此参数
	 * @return string
	 */
	public function sign()
	{
		return strtoupper(md5($this->sid . $this->token . $this->timesmap));
	}

	/**
	 * 请求头部 验证
	 * @return string
	 */
	public function authen()
	{
		return base64_encode( $this->sid . ':' . $this->timesmap );
	}

	/**
	 * 短信模板发送短信
	 * @param  string  $to         短信接收端手机号码集合，用英文逗号分开，每批发送的手机号数量不得超过100个
	 * @param  array   $datas      模板替换内容，例如：array('Marry','Alon')，如不需替换请填 null
	 * @param  integer $templateID 模板id
	 * @param  string  $dataType   数据类型 json 或xml
	 * @return xml/object
	 */
	public function sendTemplateSMS($to, array $datas, $templateID, $dataType = 'json')
	{
		# 请求包体
		$param = array(
			'to'         => $to,
			'appId'      => $this->appId,
			'templateId' => $templateID,
			'datas'      => $datas
		);

		if ($dataType == 'json') {

			# 官方的json数字不加引号会出错，吐槽下....
			$param['datas'] = array_map(function($k){
				return is_string($k) ? $k : strval($k);
			}, $param['datas']);

			$body = json_encode($param);
		}else{

			$data = '';
			foreach ($this->datas as $k) {
				$data .= "<data>{$k}</data>";
			}

			$body = "
				<TemplateSMS>
					<to>{$to}</to>
					<appId>{$this->appId}</appId>
					<templateId>{$templateID}</templateId>
					<datas>".$data."</datas>
				</TemplateSMS>";
		}

		# 请求url
		$url = self::BASE_URL . "/Accounts/{$this->sid}/SMS/TemplateSMS?sig=" . $this->sign();

		# 请求头信息
		$header = array(
			'Accept'        => 'application/' . $dataType,
			'Content-Type'  => 'application/' . $dataType,
			'charset'       => 'utf-8',
			'Authorization' => $this->authen()
		);

		$client = new Client();

		try {

			$response = $client->post($url, array(
				'headers' => $header,
				'body'    => $body,
				'verify'  => false
			));

			if ($response->getStatusCode() != '200') {
				throw new Exception('第三方服务器出错');				
			}

			return ($dataType == 'json') ? json_decode($response->getBody(), true) : simplexml_load_string(trim($response->getBody()," \t\n\r"));
			
		} catch (Exception $e) {
			throw $e;
		}
	}
}