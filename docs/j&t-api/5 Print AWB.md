极兔API简介

- 订单服务

  - 创建订单
  - 查询订单
  - 取消订单
- 面单服务

  - 打印面单
- 物流轨迹

  - 轨迹查询
  - 轨迹回传

在线文档

接口说明

通过运单号获取电子面单的模板信息。

获取到的base64面单文件，可以通过第三方地址来解码获取。参考地址： [https://base64.guru/converter/decode/pdf](https://base64.guru/converter/decode/pdf)

接口信息

测试地址：https://demoopenapi.jtexpress.my/webopenplatformapi/api/order/printOrder

正式地址：https://ylopenapi.jtexpress.my/webopenplatformapi/api/order/printOrder

测试地址apiAccount：640826271705595946

测试privateKey：8e88c8477d4e4939859c560192fcafbc

请求方法：POST

数据类型：X-WWW-FORM-URLENCODED

响应类型：JSON

参数描述

### Headers

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| apiAccount | Number | Y |  | 接入方可在控制台的应用内查看apiAccount |
| digest | String | Y |  | 签名字符串,请在签名工具内获取 |
| timestamp | Number | Y | (UTC+8) | 时间戳，毫秒 |

### 请求参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| bizContent | String | Y | Business Parameter | 业务参数模块内json格式的string类型 |

### 业务参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| customerCode | String(30) | Y | J0086474299 | 客户编码（联系出货网点提供） |
| password | String(100) | Y |  | 请在签名工具内获取接口的password |
| txlogisticId | String(50) | Y |  | 客户订单号 |
| billCode | String(30) | N |  | 运单号 |
| templateName | String(20) | N |  | 指定打印特殊的面单模板 |
| enableNewPrint | String(20) | N | mdzt | mdzt:表示使用面单中台,返回新面单样式<br>0：传0或者为空，代表使用历史面单 |

报文描述

### 响应参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| code | String(10) | Y |  | 1为成功 其他为失败,此时关注msg中返回的失败原因 |
| msg | String(100) | Y | success | 返回信息 |
| data | Object | Y |  | 业务数据 |
| requestId | String(30) | Y |  | 唯一请求ID;有问题时,可以提供这个信息 |

### data参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| txlogisticId | String(50) | Y |  | 返回客户订单号 |
| billCode | String(30) | Y |  | 返回运单号 |
| base64EncodeContent | String | N |  | base64的面单文件流，无长度限制，一票多件订单，该字段不返回。 |
| urlContent | String(200) | N |  | 一票多件主子单面单图片链接，PDF格式。 |

请求示例

复制

{
"customerCode":"ITTEST0001"

"password":"9C75439FB1FD01EB01861670DD1B949C"

"txlogisticId":"YLTEST202404101519"

"billCode":"630002864925"
}

返回示例

复制

"{

"code": 1,

"msg": "success",

"data": {

"txlogisticId": "KEXMY1000000239996",

"billCode": "670300032350",

"base64EncodeContent": "",

"urlContent": "https://ylopenapi.jtexpress.my/webopenplatformapi/api/pic/file?url=osa1del/yl-web-jmsmy-openplatform-api/openplatform\_express\_sheet\_print/20250424/8add1a25cd664dce89fc447d38ef6ac7.pdf",

},

"requestId": "7a98e6290485419b841b69f974443d54"

}"

状态码

| code | msg | 描述 |  |
| --- | --- | --- | --- |

|     |     |     |
| --- | --- | --- |
| 1 | success | 成功 |
| 0 | fail | 失败 |
| 145003052 | digest is empty! | Please get "Signature string" from the Signature Tools |
| 145003051 | apiAccount is empty! | The apiAccount can be found inside the Console. |
| 145003053 | timestamp is empty! | The timestamp field is mandatory |
| 145003010 | API account does not exist | API account does not exist |
| 145003012 | API account has no interface permissions | Please make sure the API have already request for launching and is in complete launching list. |
| 145003030 | headers signature verification failed | Headers signature verification failed |
| 145003050 | Illegal parameters | The parameters is invalid |
| 999001010 | customerCode is required | The customerCode field under Business Parameter is mandatory |
| 999001010 | password is required | The password field under Business Parameter is mandatory |
| 999001010 | txlogisticId is required | The txlogisticId field under Business Parameter is mandatory |

接口说明

URL

发送

### Headers

|     |     |
| --- | --- |
| apiAccount |  |
| digest |  |
| timestamp |  |

Body

|     |     |
| --- | --- |
| bizContent |  |

Response

""

错误代码

| code | msg | 描述 |  |
| --- | --- | --- | --- |

|     |     |     |
| --- | --- | --- |
| 1 | success | 成功 |
| 0 | fail | 失败 |
| 145003052 | digest is empty! | Please get "Signature string" from the Signature Tools |
| 145003051 | apiAccount is empty! | The apiAccount can be found inside the Console. |
| 145003053 | timestamp is empty! | The timestamp field is mandatory |
| 145003010 | API account does not exist | API account does not exist |
| 145003012 | API account has no interface permissions | Please make sure the API have already request for launching and is in complete launching list. |
| 145003030 | headers signature verification failed | Headers signature verification failed |
| 145003050 | Illegal parameters | The parameters is invalid |

![](<Base64-Image-Removed>)![](<Base64-Image-Removed>)

安全验证

拖动下方拼图完成验证

![](<Base64-Image-Removed>)![](<Base64-Image-Removed>)

![loading](<Base64-Image-Removed>)

![success](<Base64-Image-Removed>)

您的速度已超过 99% 的用户

验证错误,请重试

![load error](<Base64-Image-Removed>)

确定

![slider](<Base64-Image-Removed>)![slider](<Base64-Image-Removed>)![slider](<Base64-Image-Removed>)

安全验证

刷新验证码

![refresh](<Base64-Image-Removed>)

切换无障碍验证

![listen](<Base64-Image-Removed>)

切换常规验证

![switch](<Base64-Image-Removed>)

反馈

![tip](<Base64-Image-Removed>)

![close](<Base64-Image-Removed>)