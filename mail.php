<html >
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
</html>
<?php

    require("smtp.php");


    //判断输入内容是否为空
    if($_POST[mailfrom]==NULL)
    {
        echo "<script>alert('缺少发件人');parent.document.ADDUser.cheheh.click();</script>";
        echo "<script>self.location='index.html';</script>";
    }
    if($_POST[mailto]==NULL)
    {
        echo "<script>alert('缺少收件人');parent.document.ADDUser.cheheh.click();</script>";
        echo "<script>self.location='index.html';</script>";
    }
    if(($_POST[mailsubject]==NULL) && ($_POST[mailbody]==NULL))
    {
        echo "<script>alert('请输入邮件内容');parent.document.ADDUser.cheheh.click();</script>";
        echo "<script>self.location='index.html';</script>";
    }
    if($_POST[password]==NULL)
    {
         echo "<script>alert('发件人邮箱密码');parent.document.ADDUser.cheheh.click();</script>"
         echo "<script>self.location='index.html';</script>";
    }
    else{
        echo "<script>alert('请确保密码正确');"
    }

    $smtpemailto = $_POST[mailto];  //发送给谁
    $mailbody = $_POST[mailbody];   //邮件内容
    $mailsubject = $_POST[mailsubject]; //邮件主题
    $smtpusermail = $_POST[mailfrom];     //SMTP服务器的用户邮箱

    $smtpserver = "smtp.163.com";   //SMTP服务器,限制了邮箱为163邮箱
    $smtpserverport = "25";     //SMTP服务器端口
    $smtpuser = $_POST[mailfrom];     //SMTP服务器的用户帐号
    $smtppass = $_POST[password];     //SMTP服务器的用户密码
    $mailtype = "HTML";     //邮件格式（HTML/TXT）,TXT为文本邮件

    $client_ip = $_SERVER['REMOTE_ADDR'];  //客户端IP
    $client_port = $_SERVER['REMOTE_PORT'];  //客户端端口号
    $server_ip = $_SERVER['SERVER_ADDR'];   //服务器IP
    $server_port = $_SERVER['SERVER_PORT']; //服务器端口

    $smtp = new smtp($smtpserver, $smtpserverport,true,$smtpuser,$smtppass, $client_ip, $client_port, $server_ip, $server_port);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
    $smtp->debug = FALSE;//是否显示发送的调试信息
    $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype, $client_ip, $client_port, $server_ip, $server_port);
    echo "<script>alert('邮件发送成功');parent.document.ADDUser.cheheh.click();</script>";
    echo "<script>self.location='index.html';</script>";
    exit;
?>