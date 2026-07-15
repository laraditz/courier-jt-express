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

API简介

为了更好的服务广大客户，极兔开放平台提供标准统一的接入API，为客户与平台直接实现数据的交互，提高双方的便利。本平台将接入的流程和步骤进行详细的描述，方便客户更快速准确的接入API平台。

对接流程

1.注册用户，注册成为极兔开放平台的一员。

2.申请成为开发者，完善开发者信息，成为极兔开放平台普通开发者。

3.开发者认证，完善开发者信息，获取全部接口权限，可使用订单服务、电子面单服务等。

4.联调测试，调试测试环境接口服务，与具体的接口服务人员沟通解决问题。

5.发布上线，联调测试通过后，联系具体的接口服务人员，确认上线细节。

签名方式

1\. 使用http协议表单提交的方式进行信息交互，字符编码默认统一采用UTF-8，数据格式：application/x-www-form-urlencoded;

2\. apiAccount和privateKey可在控制台内查看； digest=base64（md5（业务参数的Json+privateKey）），注：先md5得到字节数组，再base64加密；

3\. 例如：MD5({"password":"12345678"+"jadada369t3"}) 如签名错误，可以对url进行编码然后发送请求。市场对接人会在开户后，给予正确的password；

4\. 字段类型约定：需要严格依据字段表格中给出的参数格式和大小进行开发。

5\. 字段解析约定：参数字段中的必选字段是每次调用接口时都要求必须传入的；

6\. 部分接口示例代码请参考示例代码。

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