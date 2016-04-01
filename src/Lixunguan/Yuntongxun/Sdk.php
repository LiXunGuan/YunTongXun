<?php
namespace Lixunguan\Yuntongxun;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

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

			return ($dataType == 'json') ? json_decode($response->getBody()) : simplexml_load_string(trim($response->getBody()," \t\n\r"));
			
		} catch (Exception $e) {
			throw $e;
		}
	}
	
	/**
	 * 话单下载
	 * @param  date   $date     day：前一天的数据（从00:00 – 23:59）
	 * @param  string $keywords 客户的查询条件，由客户自行定义并提供给云通讯平台。默认不填忽略此参数
	 * @param  string $dataType 数据类型 json 或xml
	 * @return xml/object
	 */
	public function billRecords($date, $keywords = '', $dataType = 'json')
	{
		# 请求包体
		if ($dataType == 'json') {
			$param = array(
				'appId'    => $this->appId,
				'date'     => $date,
				'keywords' => $keywords
			);
			$body = json_encode($param);
		}else{

			$body = "
				<BillRecords>
					<appId>{$this->appId}</appId>
					<date>{$date}</date>
					<keywords>{$keywords}</keywords>
				</BillRecords>";
		}

		# 请求url
		$url = self::BASE_URL . "/Accounts/{$this->sid}/SMS/BillRecords?sig=" . $this->sign();

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

			return ($dataType == 'json') ? json_decode($response->getBody()) : simplexml_load_string(trim($response->getBody()," \t\n\r"));
						
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * 双向回拨
	 * @see   http://docs.yuntongxun.com/index.php/%E5%8F%8C%E5%90%91%E5%9B%9E%E6%8B%A8
	 * @param string $from           主叫电话号码(必选)
	 * @param string $to             被叫电话号码(必选)
	 * @param array  $param          其他参数
		 * @param string $customerSerNum 被叫侧显示的号码
		 * @param string $fromSerNum     主叫侧显示的号码
		 * @param string $promptTone     提示音(wav格式文件)，语音文件需官网审核后才可使用
		 * @param string $alwaysPlay     是否重复播放提示音
		 * @param string terminalDtmf    用于终止播放promptTone参数定义的提示音
		 * @param string userData        第三方私有数据
		 * @param string maxCallTime     最大通话时长
		 * @param string hangupCdrUrl    实时话单通知地址
		 * @param string needBothCdr     是否给主被叫发送话单
		 * @param string needRecord      是否录音
		 * @param string countDownTime   设置倒计时时间
		 * @param string countDownPrompt 倒计时时间到后播放的提示音
	 * @param  string $dataType 数据类型 json 或xml
	 */
	public function Callback($from, $to, $param = array(), $dataType = 'json')
	{
		$params = array('customerSerNum', 'fromSerNum', 'promptTone', 'alwaysPlay', 'terminalDtmf', 'userData', 'maxCallTime', 'hangupCdrUrl', 'needBothCdr', 'needRecord', 'countDownTime', 'countDownPrompt');
		$param  = array_map(function($key) use($param){
			return isset($param[$key]) ? $param[$key] : '';
		}, $params);

		if ($dataType == 'json') {
			$body = json_encode($param);
		}else{
			$body = "
				<CallBack>
					<from>{$param['from']}</from>
					<to>{$param['to']}</to>
					<customerSerNum>{$param['customerSerNum']}</customerSerNum>
					<fromSerNum>{$param['fromSerNum']}</fromSerNum>
					<promptTone>{$param['promptTone']}</promptTone>
					<userData>{$param['userData']}</userData>
					<maxCallTime>{$param['maxCallTime']}</maxCallTime>
					<hangupCdrUrl>{$param['hangupCdrUrl']}</hangupCdrUrl>
					<alwaysPlay>{$param['alwaysPlay']}</alwaysPlay>
					<terminalDtmf>{$param['terminalDtmf']}</terminalDtmf>
					<needBothCdr>{$param['needBothCdr']}</needBothCdr>
					<needRecord>{$param['needRecord']}</needRecord>
					<countDownTime>{$param['countDownTime']}</countDownTime>
					<countDownPrompt>{$param['countDownPrompt']}</countDownPrompt>
				</CallBack>
			";
		}

		$url = self::BASE_URL . "/SubAccounts/{$this->sid}/Calls/Callback?sig=" . $this->sign();

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

			return ($dataType == 'json') ? json_decode($response->getBody()) : simplexml_load_string(trim($response->getBody()," \t\n\r"));

		} catch (Exception $e) {
			throw $e;
		}
	}
}