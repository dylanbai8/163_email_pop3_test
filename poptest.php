<?php
error_reporting(0);

// 获取post信息
$host = $_POST["mail_host"];
$user = $_POST["mail_name"];
$pass = $_POST["mail_pass"];

?>

<html>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>会员盒子-添加邮箱</title>
<style type="text/css">
select,input{
    outline-style: none;
    border: 1px solid #ccc; 
    border-radius: 3px;
    padding: 13px 14px;
    width: 260px;
    font-size: 14px;
    font-weight: 700;
    font-family: "Microsoft soft";
}
input:focus{
    border-color: #66afe9;
    outline: 0;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);
}
div{
    width: 260px;
    margin: 0 auto;
}
</style>
</head>
<body>
<div>
<form action="" method="post">
<p><b>测试邮箱是否开通pop收信</b></p>
<p><select name="mail_host">
<option value='pop.163.com'>pop.163.com</option>
<option value='pop3.aliyun.com'>pop3.aliyun.com</option>
<option value='pop.21cn.com'>pop.21cn.com</option>
</select></p>
<p><input type="text" name="mail_name" autocomplete="off" placeholder="邮箱账号"></p>
<p><input type="text" name="mail_pass" autocomplete="off" placeholder="邮箱密码"></p>
<p><input type="submit" value="提交/刷新"></p>
</form>

<?php

// 判断值是否为空
if (empty($host) || empty($user) || empty($pass)) {die;}


$rec = new pop3($host, 110, 2);
if (!$rec->open()){
    echo "<p>POP3服务器错误！</p>";
    die;
}


if (!$rec->login($user, $pass)) {
    echo "<p>邮箱错误或 <b>未开通</b> POP3。</p>";
    die;
}


echo "<p>登录邮箱成功。<b>POP3正常！</b></p>";
$rec->close();


class Pop3 {
    var $hostname = ""; // POP主机名 
    var $port = 110; // 主机的POP3端口，一般是110号端口 
    var $timeout = 5; // 连接主机的最大超时时间 
    var $connection = 0; // 保存与主机的连接 
    var $state = "DISCONNECTED"; // 保存当前的状态  
    var $debug = 0; // 做为标识，是否在调试状态，是的话，输出调试信息 
    var $err_str = ''; // 如果出错，这里保存错误信息
    var $err_no; //如果出错，这里保存错误号码
    var $resp; // 临时保存服务器的响应信息
    var $apop; // 指示需要使用加密方式进行密码验证，一般服务器不需要
    var $messages; // 邮件数 
    var $size; //各邮件的总大小 
    var $mail_list; // 一个数组，保存各个邮件的大小及其在邮件服务器上序号
    var $head = array(); // 邮件头的内容，数组 
    var $body = array(); // 邮件体的内容，数组; 
 
    function __construct($server = "192.100.100.1", $port = 110, $time_out = 5) {
 
        $this->hostname = $server;
        $this->port = $port;
        $this->timeout = $time_out;
 
        return true;
    }
 
    function Open() {
        if ($this->hostname == "") {
            $this->err_str = "无效的主机名!!";
            return false;
        }
 
        if ($this->debug){
            echo "正在打开 $this->hostname,$this->port,$this->err_no, $this->err_str, $this->timeout<br>";
        }
 
        if (!$this->connection = fsockopen($this->hostname, $this->port, $this->err_no, $this->err_str, $this->timeout)) {
            $this->err_str = "连接到POP服务器失败，错误信息：" . $this->err_str . "错误号：" . $this->err_no;
            return false;
        } else {
            $this->getresp();
            if ($this->debug){
                $this->outdebug($this->resp);
            }
            if (substr($this->resp, 0, 3) != "+OK") {
                $this->err_str = "服务器返回无效的信息：" . $this->resp . "请检查POP服务器是否正确";
                return false;
            }
            $this->state = "AUTHORIZATION";
            return true;
        }
    }
 
    function getresp() {
 
        for ($this->resp = "";;) {
 
            if (feof($this->connection))
                return false;
 
            $this->resp.=fgets($this->connection, 100);
 
            $length = strlen($this->resp);
 
            if ($length >= 2 && substr($this->resp, $length - 2, 2) == "\r\n") {
 
                $this->resp = strtok($this->resp, "\r\n");
 
                return true;
            }
        }
    }
 
    //这个方法取得服务器端的返回信息并进行简单的处理：去掉最后的回车换行符，将返回信息保存在resp这个内部变量中。这个方法在后面的多个操作中都将用到。另外，还有个小方法也在后面的多个操作中用到： 
    function outdebug($message) {
        echo htmlspecialchars($message) . "<br>\n";
    }
 
    //它的作用就是把调试信息$message显示出来，并把一些特殊字符进行转换以及在行尾加上<br>标签，这样是为了使其输出的调试信息便于阅读和分析。
    //建立起与服务器的sock连接之后，就要给服务器发送相关的命令了（请参见上面的与服务器对话的过程）从上面对 POP对话的分析可以看到，每次都是发送一条命令，然后服务器给予一定的回应，如果命令的执行是对的，回应一般是以+OK开头，后面是一些描述信息，所以，我们可以做一个通过发送命令的方法: 
    function command($command, $return_lenth = 1, $return_code = '+') {
        if ($this->connection == 0) {
            $this->err_str = "没有连接到任何服务器，请检查网络连接";
            return false;
        }
        if ($this->debug){
            $this->outdebug(">>> $command");
        }
 
        if (!fputs($this->connection, "$command\r\n")) {
            $this->err_str = "无法发送命令" . $command;
            return false;
        } else {
            $this->getresp();
            if ($this->debug){
                $this->outdebug($this->resp);
            }
            if (substr($this->resp, 0, $return_lenth) != $return_code) {
                $this->err_str = $command . " 命令服务器返回无效:" . $this->resp;
                return false;
            } else
                return true;
        }
    }
 
    //这个方法可以接受三个参数: $command--> 发送给服务器的命令; $return_lenth,$return_code ，指定从服务器的返回中取多长的值做为命令返回的标识以及这个标识的正确值是什么。对于一般的pop操作来说，如果服务器的返回第一个字符为"+"，则可以认为命令是正确执行了。也可以用前面提到过的三个字符"+OK"做为判断的标识。 
    //下面介绍的几个方法则可以按照前述收取信件的对话去理解，因为有关的内容已经在前面做了说明，因此下面的方法不做详细的说明，请参考其中的注释： 
 
    function Login($user, $password) { //发送用户名及密码，登录到服务器 
        if ($this->state != "AUTHORIZATION") {
            $this->err_str = "还没有连接到服务器或状态不对";
            return false;
        }
 
        if (!$this->apop) { //服务器是否采用APOP用户认证 
            if (!$this->command("USER $user", 3, "+OK")){
                return false;
            }
            if (!$this->command("PASS $password", 3, "+OK")){
                return false;
            }
        }else {
            if (!$this->command("APOP $user " . md5($this->greeting . $password), 3, "+OK")){
                return false;
            }
        }
 
        $this->state = "TRANSACTION"; // 用户认证通过，进入传送模式 
        return true;
    }
 
    function stat() { // 对应着stat命令，取得总的邮件数与总的大小 
        if ($this->state != "TRANSACTION") {
            $this->err_str = "还没有连接到服务器或没有成功登录";
            return false;
        }
 
        if (!$this->command("STAT", 3, "+OK")) {
            return false;
        } else {
 
            $this->resp = strtok($this->resp, " ");
            $this->messages = strtok(" "); // 取得邮件总数 
            $this->size = strtok(" "); //取得总的字节大小 
            return true;
        }
    }
 
    function listmail($mess = null, $uni_id = null) { //对应的是LIST命令，取得每个邮件的大小及序号。一般来说用到的是List命令，如果指定了$uni_id ，则使用UIDL命令，返回的是每个邮件的标识符，事实上，这个标识符一般是没有什么用的。取得的各个邮件的大小返回到类的内部变量mail_list这个二维数组里。 
        if ($this->state != "TRANSACTION") {
            $this->err_str = "还没有连接到服务器或没有成功登录";
            return false;
        }
 
        if ($uni_id){
            $command = "UIDL ";
        } else {
            $command = "LIST ";
        }
 
        if ($mess){
            $command.=$mess;
        }
 
        if (!$this->command($command, 3, "+OK")) {
            return false;
        } else {
            $i = 0;
            $this->mail_list = array();
            $this->getresp();
            while ($this->resp != ".") {
                $i++;
                if ($this->debug) {
                    $this->outdebug($this->resp);
                }
 
                if ($uni_id) {
                    $this->mail_list[$i][num] = strtok($this->resp, " ");
                    $this->mail_list[$i][size] = strtok(" ");
                } else {
                    $this->mail_list[$i]["num"] = intval(strtok($this->resp, " "));
                    $this->mail_list[$i]["size"] = intval(strtok(" "));
                }
                $this->getresp();
            }
            return true;
        }
    }
 
    function getmail($num = 1, $line = -1) { // 取得邮件的内容，$num是邮件的序号，$line是指定共取得正文的多少行。有些时候，如邮件比较大而我们只想先查看邮件的主题时是必须指定行数的。默认值$line=-1，即取回所有的邮件内容，取得的内容存放到内部变量$head，$body两个数组里，数组里的每一个元素对应的是邮件源代码的一行。 
        if ($this->state != "TRANSACTION") {
            $this->err_str = "不能收取信件，还没有连接到服务器或没有成功登录";
            return false;
        }
 
        if ($line < 0){
            $command = "RETR $num";
        } else {
            $command = "TOP $num $line";
        }
 
        if (!$this->command("$command", 3, "+OK"))
        {
            return false;
        } else {
            $this->getresp();
            $is_head = true;
            while ($this->resp != ".") { // . 号是邮件结束的标识 
                if ($this->debug){
                    $this->outdebug($this->resp);
                }
                if (substr($this->resp, 0, 1) == "."){
                    $this->resp = substr($this->resp, 1, strlen($this->resp) - 1);
                }
                if (trim($this->resp) == ""){ // 邮件头与正文部分的是一个空行 
                    $is_head = false;
                }
                if ($is_head){
                    $this->head[] = $this->resp;
                } else {
                    $this->body[] = $this->resp;
                }
                $this->getresp();
            }
            return true;
        }
    }
 
// end function 
 
    function dele($num) { // 删除指定序号的邮件，$num 是服务器上的邮件序号 
        if ($this->state != "TRANSACTION") {
            $this->err_str = "不能删除远程信件，还没有连接到服务器或没有成功登录";
            return false;
        }
 
        if (!$num) {
            $this->err_str = "删除的参数不对";
            return false;
        }
 
        if ($this->command("DELE $num ", 3, "+OK")){
            return true;
        } else {
            return false;
        }
    }
 
    //通过以上几个方法，我们已经可以实现邮件的查看、收取、删除的操作，不过别忘了最后要退出，并关闭与服务器的连接，调用下面的这个方法： 
 
    function Close() {
        if ($this->connection != 0) {
            if ($this->state == "TRANSACTION"){
                $this->command("QUIT", 3, "+OK");
            }
            
            fclose($this->connection);
            $this->connection = 0;
            $this->state = "DISCONNECTED";
        }
    }

}

?>

</div>
</body>
</html>

