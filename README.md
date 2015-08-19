YunTongXun
==========

#容联·云通讯常用API

# 引导
-[发送模板短信](#发送模板短信)

## 发送模板短信

```php
	require 'vendor/autoload.php';

	use Lixunguan\Yuntongxun\Sdk as Yuntongxun;

	$sdk = new Yuntongxun('应用ID', '账户ID', 'token');
	$sms = $sdk->sendTemplateSMS('15900000000', array('1252'), 1484);
	print_r($sms);
```

执行成功[`返回信息`](http://docs.yuntongxun.com/index.php/%E6%A8%A1%E6%9D%BF%E7%9F%AD%E4%BF%A1)

