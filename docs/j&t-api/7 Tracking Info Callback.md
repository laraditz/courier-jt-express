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

接入方在提供了快递轨迹推送相关信息并完成了下订单操作后，当订单相关的运单轨迹变化时，系统会根据接入方配置的推送账户主动推送物流信息。

接口信息

地址：使用接入方提供的URL

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
| txlogisticId | String(50) | N |  | 客户订单号 |
| billCode | String(30) | Y |  | 运单号 |
| details | Array | Y |  | 业务数据 |

### details类型说明

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| scanTime | String | Y |  | 扫描时间 |
| desc | String | Y |  | 轨迹描述 |
| scanTypeCode | String | Y | 10 | 700<br>701<br>702<br>703<br>704<br>400<br>401<br>402<br>403<br>404<br>405<br>10<br>20<br>30<br>94<br>100<br>110<br>172<br>173<br>200<br>300<br>301<br>302<br>303<br>304<br>305<br>306 |
| scanTypeName | String | Y | 快件揽收 | 驿站入库<br>取件通知<br>驿站出库<br>异常出库<br>取件交接<br>清关提货<br>清关中<br>清关放行<br>清关交付<br>大包入库<br>中心入库<br>快件揽收<br>发件扫描<br>到件扫描<br>出仓扫描<br>快件签收<br>问题件扫描<br>退件扫描<br>退件签收<br>Damage Parcel（异常终结：破损包裹）<br>Lost Parcel（异常终结：丢失包裹）<br>Dispose Parcel（异常终结：销毁包裹）<br>Reject Parcel（异常终结：拒收包裹）<br>Customs Confiscated Parcel（异常终结：海关扣押包裹）<br>Exceed Life Cycle Parcel（异常终结：超生命周期包裹）<br>Crossborder Dispose Parcel（异常终结：跨境销毁包裹） |
| scanType | String | Y | Pick Up | pop\_arrived<br>pop\_reminder\_sent<br>signed\_pop<br>unreachable\_returning<br>unreachable\_return\_deliver\_station\_out<br>Picked Up from Cargo Station<br>Customs Clearance in Process<br>Customs Clearance<br>Package Inbound<br>Package Inbound<br>快件揽收<br>发件扫描<br>到件扫描<br>派件扫描<br>快件签收<br>问题件扫描<br>退件扫描<br>退件签收<br>寄件入库<br>Damage Parcel<br>Lost Parcel<br>Dispose Parcel<br>Reject Parcel<br>Customs Confiscated Parcel<br>Exceed Life Cycle Parcel<br>Crossborder Dispose Parcel |
| realWeight | String | Y | 10.22 | 单位KG |
| scanNetworkTypeName | String | Y | 2 | 扫描网点类型： 1、中心 2、网点 |
| scanNetworkName | String | Y |  | 扫描网点名称 |
| staffName | String | Y |  | 业务员姓名 |
| staffContact | String | N |  | 业务员联系方式 |
| scanNetworkContact | String | N |  | 扫描网点联系方式 |
| scanNetworkProvince | String | Y |  | 扫描网点省份 |
| scanNetworkCity | String | Y |  | 扫描网点城市 |
| scanNetworkArea | String | Y |  | 扫描网点区/县 |
| sigPicUrl | String | N |  | 问题件/签收/退件签收图片链接，多个用“,”区分。 |
| longitude | Number | N |  | 经度（在问题件扫描时回传） |
| latitude | String | N |  | 纬度（在问题件扫描时回传） |
| timeZone | String(5) | N |  | 时区（ UTC+8） |
| siteId | String | Y | MYSB000002 | 驿站ID |
| siteName | String | Y | Yoyi Numbak | 驿站名称 |
| siteType | String | Y | 3 | 枚举值：3：代表驿站 |
| siteCollectCode | String | Y | 5-4-321 | X货架号 - X行 - X货物 |

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

请求示例

复制

{
"bizContent":\[\
"0":{\
"billCode":"JMX100099499533"\
\
"txlogisticId":"JMX100099499533"\
\
"details":\[\
"0":{\
"scanNetworkCountray":""\
\
"longitude":""\
\
"latitude":""\
\
"otp":""\
\
"secondLevelTypeCode":""\
\
"wcTraceFlag":""\
\
"timeZone":"GMT+08:00"\
\
"postCode":""\
\
"paymentStatus":""\
\
"paymentMethod":""\
\
"scanTime":"2024-03-11 14:00:45"\
\
"desc":"【Jalpan de Serra】【JLP-Sierra.pdv】El mensajero de J&T Express Imelda Pintor-Jalpan.pas está en camino a tu domicilio. Si requieres mayor información, contáctanos al 5571001047"\
\
"scanType":"On Delivery"\
\
"scanNetworkTypeName":"网点"\
\
"scanNetworkName":"JLP-Sierra.pdv"\
\
"scanNetworkId":1610\
\
"staffName":null\
\
"staffContact":null\
\
"scanNetworkContact":null\
\
"scanNetworkProvince":"Querétaro"\
\
"scanNetworkCity":"Jalpan de Serra"\
\
"scanNetworkArea":"Centro"\
\
"nextStopName":null\
\
"remark":null\
\
"nextNetworkProvinceName":"Querétaro"\
\
"nextNetworkCityName":"Jalpan de Serra"\
\
"nextNetworkAreaName":"Centro"\
\
"problemType":null\
\
"signUrl":null\
\
"sigPicUrl":null\
\
"electronicSignaturePicUrl":null\
\
"scanTypeCode":"94"\
}\
\
"1":{\
"scanNetworkCountray":""\
\
"longitude":""\
\
"latitude":""\
\
"otp":""\
\
"secondLevelTypeCode":""\
\
"wcTraceFlag":""\
\
"timeZone":"GMT+08:00"\
\
"postCode":""\
\
"paymentStatus":""\
\
"paymentMethod":""\
\
"scanTime":"2024-03-11 14:00:45"\
\
"desc":"【Jalpan de Serra】【JLP-Sierra.pdv】El mensajero de J&T Express Imelda Pintor-Jalpan.pas está en camino a tu domicilio. Si requieres mayor información, contáctanos al 5571001047"\
\
"scanType":"On Delivery"\
\
"scanNetworkTypeName":"网点"\
\
"scanNetworkName":"JLP-Sierra.pdv"\
\
"scanNetworkId":1610\
\
"staffName":null\
\
"staffContact":null\
\
"scanNetworkContact":null\
\
"scanNetworkProvince":"Querétaro"\
\
"scanNetworkCity":"Jalpan de Serra"\
\
"scanNetworkArea":"Centro"\
\
"nextStopName":null\
\
"remark":null\
\
"nextNetworkProvinceName":null\
\
"nextNetworkCityName":null\
\
"nextNetworkAreaName":null\
\
"problemType":null\
\
"signUrl":null\
\
"sigPicUrl":null\
\
"electronicSignaturePicUrl":null\
\
"scanTypeCode":"94"\
}\
\]\
}\
\]
}

返回示例

复制

{
"code":"1"

"msg":"success"

"data":"SUCCESS"

"requestID":"211212121212"
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