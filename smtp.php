<?php

    date_default_timezone_set('PRC');//将时间设置为中国时区

class smtp
    {
        /* Public Variables */
        var $smtp_port;     //SMTP端口
        var $time_out;      //过期时间
        var $host_name;     //三次握手需要用到
        var $relay_host;    //SMTP服务器
        var $debug;         //是否debug
        var $auth;          //身份验证，需要为true
        var $user;          //SMTP服务器的用户帐号
        var $pass;          //SMTP服务器的用户密码

        /* Private Variables */
        var $sock;

        /* Constractor */
        function smtp($relay_host = "", $smtp_port = 25, $auth = false, $user, $pass, $client_ip, $client_port, $server_ip, $server_port)
        {
            $this->debug = FALSE;
            $this->smtp_port = $smtp_port;
            $this->relay_host = $relay_host;
            $this->time_out = 30; //is used in fsockopen()
            $this->auth = $auth;//auth
            $this->user = $user;
            $this->pass = $pass;
            $this->host_name = "localhost"; //is used in HELO command
            $this->log_file = "";
            $this->sock = FALSE;
        }

        /* Main Function */
        function sendmail($to, $from, $subject = "", $body = "", $mailtype, $client_ip, $client_port, $server_ip, $server_port)
        {
            //创建log日志
            $filename="log/Log-".date("Ymdhi").".txt";
            $log = fopen($filename,"w");
            fwrite($log,"START TIME: \n".date("Y-m-d h:i:s")."\n\n");
            fwrite($log,"浏览器IP:".$client_ip.":".$client_port."\n");
            fwrite($log,"服务器IP:".$server_ip.":".$server_port."\n\n");
            fclose($log);

            //判断发件人邮箱格式是否正确
            $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";    //定义正则表达式
            if(!preg_match($checkmail,$from)) {   //用正则表达式函数进行判断
                echo "<script>alert('发件人邮箱格式不正确');parent.document.ADDUser.cheheh.click();</script>";
                echo "<script>self.location='index.html';</script>";
            }

            //编写邮件头
            $mail_from = $this->get_address($this->strip_comment($from));       //获取发件人
            $body = @ereg_replace("(^|(\r\n))(\.)", "\1.\3", $body);    //获取邮件信息
            $header = "MIME-Version:1.0\r\n";   //将MIME版本号写进header(头文件)中
            if ($mailtype == "HTML") {
                $header .= "Content-Type:text/html\r\n";    //将Content-Type写进header中
            }
            $header .= "To: " . $to . "\r\n";   //将mailto写进header中
            $header .= "From: $from<" . $from . ">\r\n";    //将发件人写进header中
            $header .= "Subject: " . $subject . "\r\n";     //将邮件主题写进header中
            $header .= "Date: " . date("r") . "\r\n";   //将日期写进header中
            $header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";    //将php版本写进header中
            list($msec, $sec) = explode(" ", microtime());      //计算当前时间戳
            $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";   //将message-id写入header中

            $log = fopen($filename,"a");
            fwrite($log,"\nHeader:\n".$header."\n");      //将header写入log中
            fclose($log);

            $TO = explode(",", $this->strip_comment($to));      //获取收件人信息，之前输入的时候，不同的联系人之间用“,”隔开，现在需要把所有的收件人拆开，放在同一个数组中
            $sent = TRUE;
            foreach ($TO as $rcpt_to) {     //遍历给定的 数组语句 $to 数组。每次循环中，当前单元的值被赋给 $rcpt_to 并且数组内部的指针向前移一步（因此下一次循环中将会得到下一个单元）
                //判断收件人邮箱格式是否正确
                if(!preg_match($checkmail,$rcpt_to)) {                        //用正则表达式函数进行判断
                    echo "<script>alert('收件人邮箱格式不正确');parent.document.ADDUser.cheheh.click();</script>";
                    echo "<script>self.location='index.html';</script>";
                }
                $log = fopen($filename,"a");
                fwrite($log,"开始向" . $rcpt_to . "发送邮件:\n");
                fclose($log);
                $rcpt_to = $this->get_address($rcpt_to);    //获取每个收件人信息，并进行过滤
                if (!$this->smtp_sockopen($rcpt_to, $filename)) {
                    $log = fopen($filename,"a");
                    fwrite($log,"ERROR: 无法将邮件发送至" . $rcpt_to . "\n");
                    fclose($log);
                    echo "<script>alert(\"ERROR: 无法将邮件发送至\" . $rcpt_to);parent.document.ADDUser.cheheh.click();</script>";
                    $sent = FALSE;
                    continue;
                }

                if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body, $filename)) {
                    $log = fopen($filename,"a");
                    fwrite($log,"SUCCEED: 邮件成功发送至" . $rcpt_to . "\n");
                    fclose($log);
                    echo "<script>alert(\"SUCCEED: 邮件成功发送至\" . $rcpt_to);parent.document.ADDUser.cheheh.click();</script>";
                }
                else {
                    $log = fopen($filename,"a");
                    fwrite($log,"ERROR: 无法将邮件发送至" . $rcpt_to . "\n");
                    fclose($log);
                    echo "<script>alert(\"ERROR: 无法将邮件发送至\" . $rcpt_to);parent.document.ADDUser.cheheh.click();</script>";
                    $sent = FALSE;
                }
                fclose($this->sock);
                $log = fopen($filename,"a");
                fwrite($log,"与SMTP服务器断开连接\n\n");
                fclose($log);
            }

            $log = fopen($filename,"a");
            fwrite($log,"\nEND TIME: \n".date("Y-m-d h:i:s")."\n");
            fclose($log);
            return $sent;
        }

        /* Private Functions */
        function smtp_send($helo, $from, $to, $header, $body = "", $filename)
        {
            #
            $log = fopen($filename,"a");
            fwrite($log,"发送HELO命令\n");
            fclose($log);
            if (!$this->smtp_putcmd("HELO", $helo, $filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送HELO命令失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送HELO命令失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            #auth
            $log = fopen($filename,"a");
            fwrite($log,"发送AUTH LOGIN命令\n");
            fclose($log);
            if ($this->auth) {
                if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user),$filename)) {
                    $log = fopen($filename,"a");
                    fwrite($log,"ERROR: 发送AUTH LOGIN命令失败\n");
                    fclose($log);
                    echo "<script>alert('ERROR: 发送AUTH LOGIN命令失败');parent.document.ADDUser.cheheh.click();</script>";
                    return FALSE;
                }
                if (!$this->smtp_putcmd("", base64_encode($this->pass),$filename)) {
                    $log = fopen($filename,"a");
                    fwrite($log,"ERROR: 发送AUTH LOGIN命令失败\n");
                    fclose($log);
                    echo "<script>alert('ERROR: 发送AUTH LOGIN命令失败');parent.document.ADDUser.cheheh.click();</script>";
                    return FALSE;
                }
            }

            $log = fopen($filename,"a");
            fwrite($log,"Authentication Successful\n");
            fclose($log);

            #mail  from
            $log = fopen($filename,"a");
            fwrite($log,"发送MAIL FROM:<" .$from.">\n");
            fclose($log);
            if (!$this->smtp_putcmd("MAIL", "FROM:<" . $from . ">",$filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送MAIL FROM:<" .$from.">失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送MAIL FROM失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            #RCPT TO
            $log = fopen($filename,"a");
            fwrite($log,"发送RCPT TO:<" .$to.">\n");
            fclose($log);
            if (!$this->smtp_putcmd("RCPT", "TO:<" . $to . ">",$filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送RCPT TO:<" .$to.">失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送RCPT TO失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            #DATA
            $log = fopen($filename,"a");
            fwrite($log,"发送DATA\n");
            fclose($log);
            if (!$this->smtp_putcmd("DATA","",$filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送DATA失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送DATA失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            #message
            $log = fopen($filename,"a");
            fwrite($log,"发送message:\n-----------------------------------------------------------\n".$header."\r\n".$body."\n-----------------------------------------------------------\n");
            fwrite($log,"共".strlen(implode("\r\n",array($header,$body)))."字节\n");
            fclose($log);
            if (!$this->smtp_message($header, $body)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送message失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送message失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            # .
            $log = fopen($filename,"a");
            fwrite($log,"发送<CR><LF>.<CR><LF>\n");
            fclose($log);
            if (!$this->smtp_eom($filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送<CR><LF>.<CR><LF>失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送<CR><LF>.<CR><LF>失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }

            #QUIT
            $log = fopen($filename,"a");
            fwrite($log,"发送QUIT命令\n");
            fclose($log);
            if (!$this->smtp_putcmd("QUIT","",$filename)) {
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 发送QUIT命令失败\n");
                fclose($log);
                echo "<script>alert('ERROR: 发送QUIT命令失败');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }
            return TRUE;
        }

        function smtp_sockopen($address, $filename)
        {
            $log = fopen($filename,"a");
            fwrite($log,"开始连接SMTP服务器 ". $this->relay_host . ":" . $this->smtp_port . "\n");
            fclose($log);
            $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtp_ok($filename))) {
                $log = fopen($filename,"a");
                fwrite($log,"Error:无法连接SMTP服务器" . $this->relay_host . "\n");
                fwrite($log,"Error: " . $errstr . " (" . $errno . ")\n");
                fclose($log);
                echo "<script>alert('Error:无法连接SMTP服务器'. $this->relay_host);parent.document.ADDUser.cheheh.click();</script>";
                echo "<script>alert('Error:'.$errstr.'('.$errno.')');parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }
            $log = fopen($filename,"a");
            fwrite($log,"连接到SMTP服务器" . $this->relay_host . "\n");
            fclose($log);

            return TRUE;
        }

        //将邮件主题和内容组合在一起
        function smtp_message($header, $body)
        {
            fputs($this->sock, $header . "\r\n" . $body);
            $this->smtp_debug("> " . str_replace("\r\n", "\n" . "> ", $header . "\n> " . $body . "\n> "));  //过滤字符串
            return TRUE;
        }

        //构造<CR><LF>.<CR><LF>
        function smtp_eom($filename)
        {
            fputs($this->sock, "\r\n.\r\n");
            $this->smtp_debug(". [EOM]\n");
            return $this->smtp_ok($filename);
        }

        //查看返回信息判断是否成功连接到smtp
        function smtp_ok($filename)
        {
            $response = str_replace("\r\n", "", fgets($this->sock, 512));
            $this->smtp_debug($response . "\n");
            if (!@ereg("^[23]", $response)) {
                fputs($this->sock, "QUIT\r\n");
                fgets($this->sock, 512);
                $log = fopen($filename,"a");
                fwrite($log,"ERROR: 返回错误".$response."\n");
                fclose($log);
                echo "<script>alert('ERROR: 返回错误'.$response.);parent.document.ADDUser.cheheh.click();</script>";
                return FALSE;
            }
            return TRUE;
        }

        //构造需要发送的命令
        function smtp_putcmd($cmd, $arg = "",$filename)
        {
            if ($arg != "") {
                if ($cmd == "") {
                    $cmd = $arg;
                } else {
                    $cmd = $cmd . " " . $arg;
                }
            }
            fputs($this->sock, $cmd . "\r\n");
            $this->smtp_debug("> " . $cmd . "\n");
            return $this->smtp_ok($filename);
        }

        // 邮箱地址过滤，有些时候会在邮箱后面写上一些评论，需要将评论删除
        function strip_comment($address)
        {
            $comment = "\([^()]*\)";
            while (@ereg($comment, $address)) {
                $address = @ereg_replace($comment, "", $address);
            }
            return $address;
        }

        // 邮箱地址过滤，有些邮箱地址会写成xxx@163.com<xxx@163.com>,目的在于过滤掉括号里面的内容、换行、缩进等
        function get_address($address)
        {
            $address = @ereg_replace("([ \t\r\n])+", "", $address);
            $address = @ereg_replace("^.*<(.+)>.*$", "\1", $address);
            return $address;
        }

        //debug
        function smtp_debug($message)
        {
            if ($this->debug) {
            }
        }
    }
?>