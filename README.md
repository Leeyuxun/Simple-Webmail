# simple-webmail

## 目的

1. 提供一个浏览器界面，可以实现基本的邮件头（发件人、收件人、主题等）输入和邮件内容编辑功 能；
2. 作为Web服务器，接收来自浏览器的TCP连接请求，接收邮件数据，并保存在文件中；
3. 作为SMTP客户端，建立到实际邮件服务器的TCP连接，发送SMTP命令，将保存的邮件发送给实际邮件服务器；
4. 支持一封邮件多个接收者，要求接收者属于不同的域，如bupt.edu.cn, 163.com, qq.com,…；
5. 提供发件人和收件人Email地址格式检查功能，例如下列邮件地址是错误的：chengli、 chengli@、bupt.edu.cn, ….；
6. 根据实际邮件服务器的响应码，显示错误信息，如用户名不存在等；

## 环境

1. 编程语言：PHP+HTML+Javascript
2. 服务器环境：PhpStudy v8.1(Apache2.4.39+php5.6.9+mysql5.7.26)

## 程序设计

### 数据结构

数据结构是整个程序的要点之一，程序维护者充分了解数据结构就可以对主要算法和处理流程有个基本 的理解。下面描述程序中定义的全局变量和函数中的变量的变量名和变量所起的作用以及程序定义的标 识符含义；

```php
/* Public Variables */ 
var $smtpemailto; //收件人邮箱 
var $mailbody; //邮件内容 
var $mailsubject; //邮件主题 
var $smtpusermail; //SMTP服务器的用户邮箱 
var $smtpserver; //SMTP服务器 
var $smtpserverport;//SMTP服务器端口 
var $smtpuser; //SMTP服务器的用户帐号 
var $smtppass; //SMTP服务器的用户密码 
var $mailtype; //邮件格式（HTML/TXT）,TXT为文本邮件 
var $client_ip; //客户端IP 
var $client_port; //客户端端口号 
var $server_ip; //服务器IP
var $server_port; //服务器端口 
var $smtp_port; //SMTP端口 
var $header; //邮件头 
var $message; //邮件内容 
var $time_out; //过期时间 
var $host_name; //三次握手需要用到 
var $relay_host; //SMTP服务器 
var $debug; //是否debug 
var $auth; //身份验证，需要为true 
/* Private Variables */ 
var $sock; //sock连接
```

### 模块结构

#### 函数功能

1. function smtp() ——构造结构 

   功能：构造邮件传输需要的变量 

2. function sendmail() ——主函数 

   - 功能：构造邮件头，调用子函数实现消息发送；
   - 参数
     - to：收件人 
     - from：发件人 
     - subject：邮件主题 
     - body：邮件内容 
     - mailtype：邮件格式 
     - client_ip：浏览器IP 
     - client_port：浏览器端口 
     - server_ip：服务器IP 
     - server_port：服务器端口

3. function smtp_send() 

   - 功能：与收件人邮箱服务器进行交互，发送邮件 
   - 参数： 
     - helo：连接请求 
     - from：发件人 
     - to：收件人 
     - header：邮件头 
     - body：邮件内容 
     - filename：日志文件

4. function smtp_sockopen() ——sock函数 

   - 功能：搭建的邮箱服务器与收件人邮箱服务器建立sock连接 
   - 参数： 
     - address：收件人邮箱地址 
     - filename：日志文件

5. function smtp_message() 

   - 功能：将邮件主题和内容组合在一起 
   - 参数： 
     - header：邮件头 
     - body：邮件内容

6. function smtp_eom()

   - 功能：将邮件主题和内容组合在一起 
   - 参数： 
     - filename：日志文件；

7. function smtp_ok() 

   - 功能：查看返回信息判断是否成功连接到smtp； 
   - 参数： 
     - filename：日志文件；

8. function smtp_putcmd() 

   - 功能：构造需要发送的命令； 
   - 参数： 
     - cmd：指令； 
     - filename：日志文件；

9. function strip_comment() 

   - 功能：邮箱地址过滤，有些时候会在邮箱后面写上一些评论，需要将评论删除； 
   - 参数：
     - address：IP地址； 

10. function get_address() 

    - 功能：邮箱地址过滤，有些邮箱地址会写成xxx@ 163.com,目的在于过滤掉 括号里面的内容、换行、缩进等； 
    - 参数： 
      - address：IP地址； 

11. function smtp_debug() 

    - 功能：调试程序时用到； 
    - 参数： 
      - message：与收件人邮箱服务器交换的信息；

#### 函数关系图

![](https://leeyuxun-1258157351.cos.ap-beijing.myqcloud.com/img/20200315191707.png)

#### 算法流程图

##### sendmail()函数NS盒图

![](https://leeyuxun-1258157351.cos.ap-beijing.myqcloud.com/img/20200315191817.png)

##### smtp_send()函数NS盒图

![](https://leeyuxun-1258157351.cos.ap-beijing.myqcloud.com/img/20200315191857.png)

##### smtp_socketopen()函数NS盒图

![](https://leeyuxun-1258157351.cos.ap-beijing.myqcloud.com/img/20200315191938.png)

#### 函数实现要点

1. php语言建立sock连接较为简单，只需要使用如下命令，通过smtp_ok判断连接是否建立即可；

   ```php
   $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno,
   $errstr, $this->time_out);
   if (!($this->sock && $this->smtp_ok($filename))) {
   $log = fopen($filename,"a");
   fwrite($log,"Error:无法连接SMTP服务器" . $this->relay_host . "\n");
   fwrite($log,"Error: " . $errstr . " (" . $errno . ")\n");
   fclose($log);
   echo "<script>alert('Error:无法连接SMTP服务器'. $this-
   >relay_host);parent.document.ADDUser.cheheh.click();</script>";
   echo "
   <script>alert('Error:'.$errstr.'('.f$errno.')');parent.document.ADDUser.cheh
   eh.click();</script>";
   return FALSE;
   }
   ```

2. 使用如下命令获取客户端和服务器的IP地址和端口号；

   ```php
   $client_ip = $_SERVER['REMOTE_ADDR']; //客户端IP
   $client_port = $_SERVER['REMOTE_PORT']; //客户端端口号
   $server_ip = $_SERVER['SERVER_ADDR']; //服务器IP
   $server_port = $_SERVER['SERVER_PORT']; //服务器端口
   ```

3. 使用如下正则表达式检测邮箱地址的合法性；

   ```python
   /\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/
   ```

4.  为防止攻击，选择在后端检查邮箱的合法性，而不是在前端调用JavaScript；

