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

取消通过新增订单接口生成的订单

接口信息

测试地址：https://demoopenapi.jtexpress.my/webopenplatformapi/api/order/cancelOrder

正式地址：https://ylopenapi.jtexpress.my/webopenplatformapi/api/order/cancelOrder

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
| password | String(100) | Y | Plain text password: H5CD3zE6 | 请在签名工具内获取接口的password |
| txlogisticId | String(50) | Y |  | 客户订单号 |
| billCode | String(30) | N |  | 运单号 |
| reason | String(300) | Y |  | 取消原因 |

报文描述

### 响应参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| code | String(10) | Y | 1 | 1为成功 其他为失败,此时关注msg中返回的失败原因 |
| msg | String(100) | Y | success | 返回信息 |
| data | Object | Y |  | 业务数据 |
| requestId | String(30) | Y |  | 唯一请求ID;有问题时,可以提供这个信息 |

### data参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| billCode | String(50) | Y |  | 返回客户订单号 |
| txlogisticId | String(30) | Y |  | 返回运单号 |

请求示例

复制

{
"customerCode":"ITTEST0001"

"password":"9C75439FB1FD01EB01861670DD1B949C"

"txlogisticId":"YLTEST202404101520"

"billCode":"630002563505"

"reason":"The customer cancelled the order"
}

返回示例

复制

{
"code":1

"msg":"success"

"data":{
"txlogisticId":"YLTEST202404101520"

"billCode":"630002563505"
}

"requestId":"97512378ad844b3a9668c07a2628da88"
}

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
| 999002000 | 数据未找到 | No order data found |
| 999002010 | order status can not be cancel | The order status is not allowed to cancel |
| 999001010 | customerCode is required | The customerCode field under Business Parameter is mandatory |
| 999001010 | password is required | The password field under Business Parameter is mandatory |
| 999001010 | txlogisticId is required | The txlogisticId field under Business Parameter is mandatory |
| 999001010 | billCode is required | The billCode field under Business Parameter is mandatory |
| 999001010 | reason is required | The cancellation reason is mandatory |
| 999001010 | reason is not allow more than 300 character | The cancellation reason max character length is 300 |

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
| 145003031 | 业务参数签名失败 |  |
| 145003082 | 客户订单号不能为空或过长 |  |
| 145003089 | 取消原因不能为空 |  |

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