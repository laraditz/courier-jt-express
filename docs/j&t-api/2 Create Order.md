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

与平台签约的客户，可通过此接口创建/修改订单。

接口信息

测试地址：https://demoopenapi.jtexpress.my/webopenplatformapi/api/order/addOrder

正式地址：https://ylopenapi.jtexpress.my/webopenplatformapi/api/order/addOrder

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
| txlogisticId | String(50) | Y |  | 客户订单号（传客户自己系统的订单号） |
| actionType | String(30) | Y | add | 操作类型:<br>add 新增 |
| serviceType | String(30) | Y | 1 | 服务类型:<br>1 上门取件<br>6 上门寄件 |
| payType | String(30) | Y | PP\_PM | 支付方式:<br>PP\_PM 寄付月结<br>PP\_CASH 寄付现结<br>CC\_CASH 到付现结 |
| expressType | String(30) | Y | add | 快件类型:<br>EX 次日达<br>EZ 标准快递<br>FD 鲜运 |
| sender | Object | Y |  | 寄件人信息 |
| receiver | Object | Y |  | 收件人信息 |
| returnInfo | Object | N |  | 退件人信息 |
| items | List | Y |  | 物品信息 |
| packageInfo | Object | Y |  | 包裹信息 |
| sendStartTime | String(30) | N | 2024-05-09 08:30:09 | 物流公司上门取货开始时间 |
| sendEndTime | String(30) | N | 2024-05-09 10:30:09 | 客户物流公司上门取货结束时间 |
| offerFeeInfo | Object | N |  | 只要有传offerValue就代表默认开启保价，不允许传保价金额为0.0，若是不需要保价，请勿传这个入参。 |
| codInfo | Object | N |  | COD |
| remark | String(200) | N |  | 备注 |
| customsInfo | Object | N |  | 海关信息 |
| multipleVotes | Array | N |  | 一票多件信息，包含主子单的实际重量、长、宽、高 |

### sender参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| name | String(100) | Y | Mr.Blue | 寄件人姓名 |
| phone | String(30) | Y |  | 寄件人电话 |
| countryCode | String(3) | Y |  | 国际件需根据寄件， 收件的国家填写；三字码可看 [国家/地区code](https://ylopen.jtexpress.my/apiDoc/orderserve/create#country-code-list);本地件默认 MYS |
| address | String(200) | Y |  | 寄件详细地址 |
| postCode | String(10) | Y | 56000 | 寄件邮编 |

### receiver参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| name | String(100) | Y | Mr.Blue | 收件人姓名 |
| phone | String(30) | Y |  | 收件人电话 |
| email | string(150) | N | abc@abc.com | 若是国际件，收件国家是泰国，则该字段必填 |
| idCard | String(50) | N | xxxxxx | 若是国际件，且收件国家是中国/越南，则该字段必填 |
| countryCode | String(3) | Y |  | 国际件需根据寄件， 收件的国家填写；三字码可看 [国家/地区code](https://ylopen.jtexpress.my/apiDoc/orderserve/create#country-code-list);本地件默认 MYS |
| prov | String(60) | N |  | 收件省，若是国家为”MYS“，则会用邮编覆盖省;若是国际件则为必填 |
| city | String(60) | N |  | 收件市，若是国家为”MYS“，则会用邮编覆盖市;若是国际件则为必填 |
| area | String(60) | N |  | 收件区，若是国家为”MYS“，则会用邮编覆盖区;若是国际件则为必填 |
| address | String(200) | Y |  | 收件详细地址 |
| postCode | String(10) | Y | 56000 | 收件邮编 |

### returnInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| name | String(100) | Y | Mr.Blue | 退件人姓名 |
| phone | String(30) | Y |  | 退件人电话 |
| countryCode | String(3) | Y |  | 国际件需根据寄件， 收件的国家填写；三字码可看 [国家/地区code](https://ylopen.jtexpress.my/apiDoc/orderserve/create#country-code-list);本地件默认 MYS |
| address | String(200) | Y |  | 退件详细地址 |
| postCode | String(10) | Y | 56000 | 退件邮编 |

### packageInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| packageQuantity | String(1-999) | Y |  | 包裹总数量 |
| weight | String(0.01-999.99) | Y |  | 包裹总重量，支持2位小数，单位是KG |
| packageValue | String(3) | Y |  | 商品总价值，单位是（MYR） |
| goodsType | String(4) | Y | ITN2 | ITN2：⽂件<br>ITN8：包裹 |
| length | String(0.01-999.99) | N |  | 长，单位是（cm） |
| width | String(0.01-999.99) | N |  | 宽，单位是（cm） |
| height | String(0.01-999.99) | N |  | 高，单位是（cm） |

### offerFeeInfo参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| offerValue | String(0.01-999999.99) | N |  | 只要有传offerValue就代表默认开启保价，不允许传保价金额为0.0，若是不需要保价，请勿传这个入参。 |

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
| weight | String(1-999999) | Y |  | 物品重量 |
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
| actualWeight | String(0.01-999.99) | Y |  | 包裹重量，支持2位小数，单位是KG |
| length | String(0.01-999.99) | N |  | 长，单位是（cm） |
| width | String(0.01-999.99) | N |  | 宽，单位是（cm） |
| height | String(0.01-999.99) | N |  | 高，单位是（cm） |

报文描述

### 响应参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| code | String(10) | Y | 1 | 1为成功 其他为失败,此时关注msg中返回的失败原因 |
| msg | String(100) | Y |  | 返回信息 |
| data | Object | Y |  | 业务数据 |
| requestId | String(30) | Y |  | 唯一请求ID;有问题时,可以提供这个信息 |

### data参数

| 属性名 | 属性类型 | 是否必填 | 示例 | 描述 |  |
| --- | --- | --- | --- | --- | --- |

|     |     |     |     |     |
| --- | --- | --- | --- | --- |
| txlogisticId | String(50) | Y |  | 返回客户订单号 |
| billCode | String(30) | Y |  | 返回运单号 |
| sortingCode | String(20) | Y |  | 返回三段码 |
| thirdSortingCode | String(10) | Y |  | 返回第三段码 |
| boxStandardCode | String(128) | N |  | 第四段码 |
| multipleVoteBillCodes | Array | N |  | 返回的一票多件信息运单号信息，包含主子单号。 |
| packageChargeWeight | String(0.01-999.99) | N |  | 包裹总计费重量，支持2位小数，单位是KG |

请求示例

复制

{
"customerCode":"ITTEST0001"

"actionType":"add"

"password":"9C75439FB1FD01EB01861670DD1B949C"

"txlogisticId":"YLTEST202404101519"

"expressType":"EZ"

"serviceType":"1"

"sender":{
"name":"J&T sender "

"postCode":"81930"

"phone":"60123456"

"address":"No 32, Jalan Kempas 4"

"countryCode":"MYS"

"prov":"Johor"

"city":"Bandar Penawar"

"area":"Taman Desaru Utama"
}

"receiver":{
"name":"J&T receiver"

"postCode":"31000"

"phone":"60987654"

"address":"4678, Laluan Sentang 35"

"countryCode":"MYS"

"prov":"Perak "

"city":"Batu Gajah "

"area":"Kampung Seri Mariah"
}

"payType":"PP\_PM"

"goodsType":"PARCEL"

"weight":10

"items":\[\
"0":{\
"itemName":"basketball"\
\
"englishName ":"basketball"\
\
"itemDesc":"This is a basketball"\
\
"number":2\
\
"itemValue":"50"\
\
"weight":"10"\
\
"itemCurrency":"USD"\
}\
\
"1":{\
"itemName":"phone"\
\
"englishName ":"phone"\
\
"itemDesc":"This is a phone"\
\
"number":1\
\
"itemValue":"4000"\
\
"weight":"100"\
\
"itemCurrency":"USD"\
}\
\]

"packageInfo":{
"packageQuantity":10

"goodsType":"ITN2"

"weight":10

"length":10

"width":10

"packageValue":"880"
}

"sendStartTime":"2024-06-19 13:45:00"

"sendEndTime":"2024-06-25 16:23:00"

"remark":""

"returnInfo":{
"name":"J&T return"

"postCode":"31000"

"phone":"60987654"

"address":"4678, Laluan Sentang 35"
}

"offerFeeInfo":{
"offerValue":"12"
}

"customsInfo":{
"customsCode":"2000001"

"nationalInspectionNo":"456DEF"

"originPlace":"China"

"brandName":"Brand X"

"oldItem":0

"number":"10"

"weight":"25.5"

"unitWeight":"2.5"

"totalValue":"100"

"unitPrice":"10"

"currency":"USD"
}

"codInfo":{
"codValue":100
}

"multipleVotes":\[\
"0":{\
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
"actualWeight":"21"\
\
"length":"12"\
\
"width":"12"\
\
"height":"12"\
}\
\]
}

返回示例

复制

{
"code":1

"msg":"success"

"data":{
"txlogisticId":"OPEN2026289267907"

"billCode":"630000491494"

"sortingCode":"93-C24-NS610"

"boxStandardCode":"SETIA INTERNATIONAL CENTRE"

"thirdSortingCode":"EC2"

"basePriceFreight":2052

"basePriceDiscount":2052

"totalFreight":2052
}

"requestId":"0dabc88afac622f5"
}

国家/地区code

| 国家/地区code | 描述 |  |
| --- | --- | --- |

|     |     |
| --- | --- |
| SIN | SINGAPORE |
| VNM | VIETNAM |
| BWM | BRUNEI |
| HKG | HONG KONG |
| CHN | CHINA |
| THA | THAILAND |
| PHL | PHILIPPINES |
| IDN | INDONESIA |

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
| 999001030 | sender postcode invalid | The sender postcode does not match the format or regional structure required for the sender's country. |
| 999001010 | sender postCode is required | The sender parameters postCode field is mandatory |
| 999001030 | sender countryCode invalid | For domestic parcel, kindly put MYS, for other country kindly refer to the 国家/地区 code attachment |
| 999001010 | receiver postCode is required | The receiver parameters postCode field is mandatory |
| 999001030 | receiver postcode invalid | The receiver postcode does not match the format or regional structure required for the receiver's country. |
| 999001010 | receiver.prov is required | If the receiver countryCode is other than MYS, the receiver parameters receiver.prov field is mandatory |
| 999001010 | receiver.city is required | If the receiver countryCode is other than MYS, the receiver parameters receiver.city field is mandatory |
| 999001010 | receiver.area is required | If the receiver countryCode is other than MYS, the receiver parameters receiver.area field is mandatory |
| 999001030 | receiver country invalid | For domestic parcel, kindly put MYS, for other country kindly refer to the 国家/地区 code attachment |
| 999001030 | receiver country/province/city/area invalid | For international parcel, which the country code is not MYS, please make sure the receiver province/city/area is match to the respective country |
| 999001030 | customsInfo is required | For international parcel, kindly make sure the customsInfo parameters mandatory field is not empty |
| 0 | The application does not set the order source and is prohibited from placing orders. | Kindly contact the marketing PIC to inform J&T IT to setup the order source or the application |
| 999001030 | returnInfo postCode invalid | The returnInfo parameters postCode field does not match the format or regional structure required for the return country. |
| 0 | 获取运单号失败 | Failed to get the billCode |
| 999001010 | actionType is required | The actionType field under Business Parameter is mandatory |
| 999001010 | actionType only support add | The actionType field is mandatory and should be value "add" |
| 999001010 | customerCode is required | The customerCode field under Business Parameter is mandatory |
| 999001010 | customerCode is not allow more than 30 character | The max character length for customerCode field is 30 |
| 999001010 | password is required | The password field under Business Parameter is mandatory |
| 999001010 | txlogisticId is required | The txlogisticId field under Business Parameter is mandatory |
| 999001010 | txlogisticId number is not allow more than 50 character | The max character length for txlogisticId field is 50 |
| 999001010 | expressType is required | The expressType field under Business Parameter is mandatory |
| 999001010 | expressType only support EZ or FD or EX or DO or JS | The expressType field value can only be EZ or FD or EX or DO or JS |
| 999001010 | serviceType is required | The serviceType field under Business Parameter is mandatory |
| 999001010 | serviceType only support 1 or 6 | The serviceType field value can only be 1 or 6 |
| 999001010 | sender is required | The sender field under Business Parameter is mandatory |
| 999001010 | receiver is required | The receiver field under Business Parameter is mandatory |
| 999001010 | createOrderTime format not match yyyy-MM-dd HH:mm:ss | createOrderTime format does not match yyyy-MM-dd HH:mm:ss |
| 999001010 | sendStartTime format not match yyyy-MM-dd HH:mm:ss | sendStartTime format does not match yyyy-MM-dd HH:mm:ss |
| 999001010 | sendEndTime format not match yyyy-MM-dd HH:mm:ss | sendEndTime format not match yyyy-MM-dd HH:mm:ss |
| 999001010 | payType only support PP\_CASH or CC\_CASH or PP\_PM | The payType field value can only be PP\_PM or PP\_CASH or CC\_CASH |
| 999001010 | payType is required | The payType field under Business Parameter is mandatory |
| 999001010 | remark is not allow more than 200 character | The max character length for remark field is 200 |
| 999001010 | items is required | The item field under Business Parameter is mandatory |
| 999001010 | packageInfo is required | The packageInfo field under Business Parameter is mandatory |
| 999001010 | name is required | The name field under Sender and Receiver Parameter is mandatory |
| 999001010 | name is not allow more than 100 character | The max character length for name field is 100 |
| 999001010 | company is not allow more than 100 character |  |
| 999001010 | mailBox is not allow more than 150 character | The max character length for receiver email is 150 |
| 999001010 | mobile is not allow more than 30 character |  |
| 999001010 | phone is not allow more than 30 character |  |
| 999001010 | phone is required | The phone field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | countryCode is required | The countryCode field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | countryCode is not allow more than 3 character | The max character length for countryCode field is 3 |
| 999001010 | prov is not allow more than 60 character | The max character length for prov field is 60 |
| 999001010 | city is not allow more than 60 character | The max character length for city field is 60 |
| 999001010 | area is not allow more than 60 character | The max character length for area field is 60 |
| 999001010 | address is required | The address field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | address is not allow more than 200 character | The max character length for address field is 200 |
| 999001010 | items.itemName is required | The itemName field under items Parameter is mandatory |
| 999001010 | itemName is not allow more than 100 character | The max character length for itemName field is 100 |
| 999001010 | items.chineseName is not allow more than 100 character |  |
| 999001010 | items.englishName is not allow more than 100 character | The max character length for englishName field is 100 |
| 999001010 | items.number is required | The number field under items Parameter is mandatory |
| 999001010 | items.number min 1 | The min value for number field under item Parameters is 1 |
| 999001010 | items.number max 9999999 | The max value for number field under item Parameters is 99999999 |
| 999001010 | items.itemValue is required | The itemValue field under items Parameter is mandatory |
| 999001010 | items.itemValue min 0.01 | The min value for itemValue field under item Parameters is 0.01 |
| 999001010 | items.itemValue max 9999999 | The max value for itemValue field under item Parameters is 99999999 |
| 999001010 | items.itemCurrency is not allow more than 10 character | The max character length for itemCurrency field is 10 |
| 999001010 | items.itemDesc is not allow more than 200 character | The max character length for itemDesc field is 200 |
| 999001010 | items.itemUrl is not allow more than 200 character |  |
| 999001010 | items.weight is required | The weight field under items Parameter is mandatory |
| 999001010 | items.weight min 0.01 | The min value for weight field under item Parameters is 0.01 |
| 999001010 | items.weight max 999 | The max value for weight field under item Parameters is 999 |
| 999001010 | packageInfo.packageQuantity is required | The packageQuantity field under packageInfo Parameter is mandatory |
| 999001010 | packageInfo.packageQuantity min is 1 | The min value for packageQuantity field under packageInfo Parameters is 1 |
| 999001010 | packageInfo.packageQuantity max is 999 | The max value for packageQuantity field under packageInfo Parameters is 999 |
| 999001010 | packageInfo.goodsType is required | The goodsType field under packageInfo Parameter is mandatory |
| 999001010 | packageInfo.goodsType only support ITN2 or ITN8 | The goodsType field value can only be ITN2 or ITN8 |
| 999001010 | packageInfo.weight is required | The weight field under packageInfo Parameter is mandatory |
| 999001010 | packageInfo.weight information is not legal | The weight information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.weight information is not legal, min weight is 0.01 | The min value for weight field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.weight information is not legal, max weight is 99.999 | The max value for weight field under packageInfo Parameters is 99.999 |
| 999001010 | packageInfo.length information is not legal | The length information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.length information is not legal, min length is 0.01 | The min value for length field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.length information is not legal, max length is 999.99 | The max value for length field under packageInfo Parameters is 999.99 |
| 999001010 | packageInfo.width information is not legal | The width information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.width information is not legal, min width is 0.01 | The min value for width field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.width information is not legal, max width is 999.99 | The max value for width field under packageInfo Parameters is 999.99 |
| 999001010 | packageInfo.height information is not legal | The height information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.height information is not legal, min height is 0.01 | The min value for height field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.height information is not legal, max height is 999.99 | The max value for height field under packageInfo Parameters is 999.99 |
| 999001010 | packageInfo.volume information is not legal | The volume information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.volume information is not legal, min volume is 0.01 | The min value for volume field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.volume information is not legal, max volume is 999.99 | The max value for volume field under packageInfo Parameters is 999.99 |
| 999001010 | packageInfo.packageValue is required | The packageValue field under packageInfo Parameter is mandatory |
| 999001010 | packageInfo.packageValue information is not legal | The packageValue information under packageInfo Parameter is not valid |
| 999001010 | packageInfo.packageValue information is not legal, min packageValue is 0.01 | The min value for packageValue field under packageInfo Parameters is 0.01 |
| 999001010 | packageInfo.packageValue information is not legal, max packageValue is 999999.99 | The max value for packageValue field under packageInfo Parameters is 999999.99 |
| 999001010 | offerFeeInfo.offerValue information is not legal | The offerValue information under offerFeeInfo Parameter is not valid |
| 999001010 | offerFeeInfo.offerValue information is not legal, min offerValue is 0.01 | The min value for offerValue field under offerFeeInfo Parameters is 0.01 |
| 999001010 | offerFeeInfo.offerValue information is not legal, max offerValue is 999999.99 | The max value for offerValue field under offerFeeInfo Parameters is 999999.99 |
| 999001010 | offerFeeInfo.codValue information is not legal | The codValue information under codInfo Parameter is not valid |
| 999001010 | offerFeeInfo.codValue information is not legal, min weight is0.01 | The min value for codValue field under codInfo Parameters is 0.01 |
| 999001010 | offerFeeInfo.codValue information is not legal, max weight is999999.99 | The max value for codValue field under codInfo Parameters is 999999.99 |
| 999001010 | customsInfo.customsCode is not allow more than 100 character | The max character length for customsCode field is 100 |
| 999001010 | customsInfo.originPlace is not allow more than 200 character | The max character length for originPlace field is 200 |
| 999001010 | customsInfo.brandName is not allow more than 100 character | The max character length for brandName field is 100 |
| 999001010 | customsInfo.oldItem only 0 or 1 | The oldItem field value can only be 0 or 1 |
| 999001010 | customsInfo.number min 1 | The min value for number field under customsInfo Parameters is 1 |
| 999001010 | customsInfo.number max 999999 | The max value for number field under customsInfo Parameters is 999999 |
| 999001010 | customsInfo.number is required | The number field under customsInfo Parameter is mandatory |
| 999001010 | customsInfo.weight is not legal | The weight under customsInfo Parameter is not valid |
| 999001010 | customsInfo.weight information is not legal, min weight is 0.01 | The min value for weight field under customsInfo Parameters is 0.01 |
| 999001010 | customsInfo.weight information is not legal, max weight is 999.99 | The max value for weight field under customsInfo Parameters is 999.99 |
| 999001010 | customsInfo.totalValue is not legal | The totalValue under customsInfo Parameter is not valid |
| 999001010 | customsInfo.totalValue information is not legal, min totalValue is 0.01 | The min value for totalValue field under customsInfo Parameters is 0.01 |
| 999001010 | customsInfo.totalValue information is not legal, max totalValue is 9999999.99 | The max value for totalValue field under customsInfo Parameters is 9999999.99 |
| 999001010 | customsInfo.unitPrice is not legal | The unitPrice under customsInfo Parameter is not valid |
| 999001010 | customsInfo.unitPrice information is not legal, min unitPrice is 0.01 | The min value for unitPrice field under customsInfo Parameters is 0.01 |
| 999001010 | customsInfo.unitPrice information is not legal, max unitPrice is 9999999.99 | The max value for unitPrice field under customsInfo Parameters is 9999999.99 |
| 999001010 | customsInfo.unitPrice is required | The unitPrice field under customsInfo Parameter is mandatory |
| 999001010 | customsInfo.customsInfo.currency is not allow more than 10 character | The max character length for currency field under customsInfo is 10 |
| 999001010 | customsInfo.customsInfo.currency is required | The currency field under customsInfo Parameter is mandatory |
| 999001010 | returnInfo.name is not allow more than 100 character | The max character length for name field under returnInfo is 100 |
| 999001010 | returnInfo.name is required | The name field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | returnInfo.phone is not allow more than 50 character | The max character length for phone field under returnInfo is 50 |
| 999001010 | returnInfo.phone is required | The phone field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | returnInfo.postCode is required | The address field under Sender, returnInfo and Receiver Parameter is mandatory |
| 999001010 | returnInfo.address is not allow more than 300 character | The max character length for address field under returnInfo is 300 |
| 999001010 | returnInfo.address is required | The address field under returnInfo Parameter is mandatory |

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