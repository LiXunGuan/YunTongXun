YunTongXun
==========

#容联·云通讯常用API

# 引导
-[发送模板短信](#发送模板短信)

## 发送模板短信

```php
	$sms = Yuntongxun::init(array(
		'appid'   => '应用ID',
		'sid'     => '账户ID',
		'token'   => 'token信息',
	))
	->to('18659802750')
	->with(array('2014-11-06','10000','10000','20000'))
	->sendTemplateSMS(6765);

	print_r($sms);
```

执行成功[`返回信息`](http://docs.yuntongxun.com/index.php/%E6%A8%A1%E6%9D%BF%E7%9F%AD%E4%BF%A1)

