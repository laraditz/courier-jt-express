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

查询新增订单接口创建的订单

接口信息

测试地址：https://demoopenapi.jtexpress.my/webopenplatformapi/api/order/getOrders

正式地址：https://ylopenapi.jtexpress.my/webopenplatformapi/api/order/getOrders

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
| timestamp | Number | Y |  | 时间戳，毫秒 |

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

报文描述

### 响应参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| code | String(10) | Y |  | 为成功 其他为失败,此时关注msg中返回的失败原因 |
| msg | String(100) | Y | success | 返回信息 |
| data | Object | Y |  | 业务数据 |
| requestId | String(30) | Y |  | 唯一请求ID;有问题时,可以提供这个信息 |

### data类型说明

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| txlogisticId | String(50) | Y |  | 客户订单号 |
| billCode | String(30) | Y |  | 运单号 |
| serviceType | String(10) | Y | 1 | 服务类型<br>1 上门取件<br>6 上门寄件 |
| serviceType | String(10) | Y | 1 | 支付方式<br>PP\_PM 寄付月结<br>PP\_CASH 寄付现结<br>CC\_CASH 到付现结 |
| serviceType | String(10) | Y | 1 | 快件类型<br>EX 次日达<br>EZ 标准快递<br>FD 鲜运 |
| items | Object | N |  | 物品信息 |
| packageInfo | Object | Y |  | 包裹信息 |
| sendStartTime | String(30) | N | 2024-05-09 08:30:09 | 物流公司上门取货开始时间 |
| sendEndTime | String(30) | N | 2024-05-09 10:30:09 | 客户物流公司上门取货结束时间 |
| offerFeeInfo | Object | N |  | 保价，单位：“MYR” |
| codInfo | Object | N |  | 代收货款，单位为”MYR“ |
| customsInfo | Object | N |  | 海关信息 |
| multipleVotes | Array | N |  | 一票多件信息，包含主子单的实际重量、长、宽、高，运单号 |

### packageInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| packageQuantity | String(1-999) | Y |  | 包裹总数量 |
| weight | String(0.01-999.99) | Y | 50.12 | 包裹总重量，支持2位小数，单位是KG |
| packageValue | String(3) | Y |  | 商品总价值，单位是（MYR） |
| goodsType | String(4) | Y | ITN2 | ITN2：⽂件 ITN8：包裹 |
| length | String(0.01-999.99) | N |  | 长，单位是（cm） |
| width | String(0.01-999.99) | N |  | 宽，单位是（cm） |
| height | String(0.01-999.99) | N |  | 高，单位是（cm） |

### offerFeeInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| offerValue | String(0.01-999999.99) | N |  | 保价金额，单位是（MYR） |

### codInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| codValue | String(0.01-999999.99) | N |  | 代收货款，单位是（MYR） |

### items参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| itemName | String(100) | Y |  | 物品名称 |
| englishName | String(100) | N |  | 物品的英文名称 English |
| number | String(1-9999999) | Y |  | 物品数量 |
| itemValue | String(0.01-9999999.99) | Y |  | 物品单个价值 |
| itemCurrency | String(10) | N | MYR | 价值的币种, 默认为MYR |
| itemDesc | String(200) | N |  | 物品描述 |

### customsInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| customsCode | String(100) | N |  | 清关code 协调系统code |
| nationalInspectionNo | String(30) | N |  | 商品海关备案号 跨境件报关需要填写 |
| number | String(1-999999) | Y |  | 总数量 |
| weight | String(0.01-999.99) | Y |  | 总重量 |
| totalValue | String(0.01-9999999) | N |  | 总价值 |
| unitPrice | String(0.01-9999999) | Y |  | 单个价值 |
| currency | String(10) | Y | MYR | 价值的币种 |
| originPlace | String(200) | N |  | 始发地 |
| brandName | String(100) | N |  | 品牌名称 |
| oldItem | String(1) | N |  | 是否是贸易件 0:不是 1：是 |

### multipleVotes参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| subBillCode | String(30) | Y |  | 主运单号和子运单号 |
| actualWeight | String(0.01-999.99) | Y |  | 包裹重量，支持2位小数，单位是KG |
| length | String(0.01-999.99) | N |  | 长，单位是（cm） |
| width | String(0.01-999.99) | N |  | 宽，单位是（cm） |
| height | String(0.01-999.99) | N |  | 高，单位是（cm） |

请求示例

复制

{
"customerCode":"ITTEST0001"

"password":"9C75439FB1FD01EB01861670DD1B949C"

"txlogisticId":"YLTEST202404101519"
}

返回示例

复制

{
"code":1

"msg":"success"

"data":{
"customerCode":"ITTEST0001"

"txlogisticId":"YLTEST202404101519"

"billCode":"630002864925"

"expressType":"EZ"

"serviceType":"1"

"sender":{
"name":"J&T sender "

"postCode":"81930"

"mobile":"\*\*\*\*3456"

"phone":"\*\*\*\*3456"

"countryCode":"MYS"

"prov":"JOHOR"

"city":"KOTA TINGGI"

"area":"BANDAR PENAWAR"
}

"receiver":{
"name":"J&T receiver"

"postCode":"31000"

"mobile":"\*\*\*\*7654"

"phone":"\*\*\*\*7654"

"countryCode":"MYS"

"prov":"PERAK"

"city":"KINTA"

"area":"BATU GAJAH"

"address":"4678, Laluan Sentang 35"
}

"createOrderTime":"2024-06-19 06:13:57"

"sendStartTime":"2024-06-19 13:45:00"

"sendEndTime":"2024-06-25 16:23:00"

"payType":"PP\_PM"

"packageInfo":{
"packageQuantity":10

"goodsType":"ITN2"

"length":10

"width":10

"packageValue":880
}

"offerFeeInfo":{
"offerValue":880
}

"codInfo":{
"codValue":100
}

"multipleVotes":\[\
"0":{\
"subBillCode":"670002864925"\
\
"actualWeight":"21"\
\
"length":"12"\
\
"width":"12"\
\
"height":"12"\
}\
\
"1":{\
"subBillCode":"670002864925-01"\
\
"actualWeight":"21"\
\
"length":"12"\
\
"width":"12"\
\
"height":"12"\
}\
\]

"requestId":"00027ea9b253402facfe872d1915ec64"
}
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
| 145003031 | 业务参数签名失败 |  |
| 145003097 | 时间范围非法 |  |
| 145003102 | 页码非法 |  |
| 145003098 | 超过数据限制 |  |

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