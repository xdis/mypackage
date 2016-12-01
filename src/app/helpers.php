<?php
/**
 *  helpers.php
 *
 * @author gengzhiguo@xiongmaojinfu.com
 * $Id: helpers.php 2016-06-03 下午5:56 $
 */

/**
 * 生成订单号
 *
 * @author gengzhiguo@xiongmaojinfu.com
 * @return string
 */
function makeRequestNo()
{
    return date('ymdHis') . mt_rand(100000, 999999);
}

/**
 * 计算平台费用
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param int $amount
 * @param float $rate
 * @param int $period
 * @param int $interestType
 *
 * @return mixed
 */
function getPlatformFee($amount, $rate, $period, $interestType = 2)
{
    $fee = 0;
    switch($interestType) {
        case 1:
        case 4:
            $fee = numberFormat($amount * $rate * $period / 365);
            break;
        case 2:
        case 3:
            $fee =  numberFormat($amount * ($rate / 12) * $period);
            break;
        default:
    }

    return $fee;
}


/**
 * 获取利息信息
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param $interestType
 * @param $amount
 * @param $rate
 * @param $period
 *
 * @return boolean | array
 */
function getInterestInfo($interestType, $amount, $rate, $period)
{
    switch($interestType) {
        case 1:
            $interestInfo = dayInterestPrincipal($amount, $rate, $period);
            break;
        case 2:
            $interestInfo = monthInterestPrincipal($amount, $rate, $period);
            break;
        case 3:
            $interestInfo = equalPrincipalInterest($amount, $rate, $period);
            break;
        case 4:
            $interestInfo = dayOnceInterestPrincipal($amount, $rate, $period);
            break;
        default:
            return false;
    }

    return $interestInfo;
}

/**
 * 格式化数字
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param $num
 *
 * @return float
 */
function numberFormat($num)
{
    return floatval(number_format(floatval($num), 2, '.', ''));
}


/**
 * 等额本金
 *
 * @param float $total  总金额
 * @param float $rate   月利率
 * @param int   $period 借款时间
 * @param bool  $isShow 是否显示详细
 *
 * @return array
 */

function equalPrincipalInterest($total, $rate, $period, $isShow = false)
{

    //每月还款
    $monthRate      = floatval(number_format(($rate / 12), 7));
    $monthRepayment = $total * $monthRate * pow((1 + $monthRate), $period) / (pow((1 + $monthRate), $period) - 1);

    //累计还款总额

    $repayment = $monthRepayment * $period;

    //累计支付利息
    $interest = $repayment - $total;

    $result = [];

    $result['interest'] = numberFormat($interest);

    $result['repayment'] = numberFormat($repayment);

    $result['month_repayment'] = numberFormat($monthRepayment);

    if ($isShow) {

        $balance = $total; //贷款余额

        $item = [];

        for ($i = 1; $i <= $period; $i++) {

            $monthInterest = $balance * $monthRate;

            $monthPrincipal = $monthRepayment - $monthInterest;

            $balance -= $monthPrincipal;

            $item[$i] = [];

            $item[$i]['month_interest']  = numberFormat($monthInterest); //月利息
            $item[$i]['month_principal'] = numberFormat($monthPrincipal); //月本金
            $item[$i]['month_repayment'] = numberFormat($monthPrincipal + $monthInterest);//月还款
            $item[$i]['balance']         = numberFormat($balance); //余额
        }

        $result['detail'] = $item;
    }

    return $result;
}

/**
 * 按月还息
 *
 * @desc   每月利息=本金＊年化收益／12
 *         http://admin.liduoduo.com/api/interest/equal?key=6fb47d2a5b02eb9312a0ee3f16c152e7&price=10000&rate=0.054&month=24
 *
 * @param float $total  投资本金
 * @param float $rate   收益率(年收益绿)
 * @param int   $period 还款月数
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @return array
 */
function monthInterestPrincipal($total, $rate, $period)
{
    //验证参数
    if (!$rate or !$total or !$period or $total < 1 or $total > 999999999999 or $period < 1 or $period > 999
        or $rate
           < 0.00001
        or $rate > 1
    ) {
        return false;
    }

//    //月利息
//    $monthRate = round($rate / 12, 10);
//    $monthRate = substr(sprintf("%.8f", $monthRate), 0, -1);
//
//    //每月利息
//    $monthInterest = $total * $monthRate;

    //总利息
    $earnings = ($total * $rate * $period) / 12;

    $monthInterests = [];
    $alreadyInterest = 0;
    for ($i = 1; $i < $period; $i++) {
        $monthInterests[$i] = numberFormat($earnings / $period);
        $alreadyInterest += $monthInterests[$i];
    }

    $monthInterests[$period] = $earnings - $alreadyInterest;

    //总金额
    $priceTotal = $earnings + $total;

    $earnings      = substr(sprintf("%.5f", $earnings), 0, -1);
    $priceTotal    = substr(sprintf("%.5f", $priceTotal), 0, -1);

    $info = [
        'principal'     => $total,
        'rate'           => $rate,
        'period'          => $period,
        'month_interests' => $monthInterests,
        'interest'       => numberFormat($earnings),
        'total'      => numberFormat($priceTotal),
    ];

    return $info;
}
/**
 * 按日计息 按月还息最后付本
 *
 * @desc   每月利息=本金＊年化收益／12
 *
 * @param float $total  投资本金
 * @param float $rate   收益率(年收益绿)
 * @param int   $period 标的周期时长
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @return array
 */
function dayInterestPrincipal($total, $rate, $period)
{
    $daysOfYear = 365;
    $daysOfMonth = 30;
    //验证参数
    if (!$rate
        || !$total
        || !$period
        || $total < 1
        || $total > 999999999999
        || $period < 1
        || $rate < 0.00001
        || $rate > 1
    ) {
        return false;
    }

    // 还款次数
    $repaymentTimes = ceil($period / $daysOfMonth);

    // 总利息
    $totalInterest = numberFormat(($total * $rate * $period) / $daysOfYear);

    // 总共要还的金额
    $totalRepaymentAmount = $total + $totalInterest;

    $everyInterests = [];
    $alreadyInterest = 0;
    for ($i=1; $i < $repaymentTimes; $i++) {
        $everyInterests[$i] = numberFormat($totalInterest / $repaymentTimes);
        $alreadyInterest += $everyInterests[$i];
    }
    $everyInterests[$repaymentTimes] = $totalInterest - $alreadyInterest;

    return [
        'principal' => $total,
        'rate' => $rate,
        'interest' => $totalInterest,
        'total' => $totalRepaymentAmount,
        'period' => $repaymentTimes, // 还款次数
        'month_interests' => $everyInterests,
    ];
}

/**
 * 按日计息 一次性还本付息
 *
 * @desc   每月利息=本金＊年化收益／12
 *
 * @param float $total  投资本金
 * @param float $rate   收益率(年收益绿)
 * @param int   $period 标的周期时长
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @return array
 */
function dayOnceInterestPrincipal($total, $rate, $period)
{
    $daysOfYear = 365;
    //验证参数
    if (!$rate
        || !$total
        || !$period
        || $total < 1
        || $total > 999999999999
        || $period < 1
        || $rate < 0.00001
        || $rate > 1
    ) {
        return false;
    }

    // 总利息
    $totalInterest = numberFormat(($total * $rate * $period) / $daysOfYear);

    // 总共要还的金额
    $totalRepaymentAmount = $total + $totalInterest;

    return [
        'principal' => $total,
        'rate' => $rate,
        'interest' => $totalInterest,
        'total' => $totalRepaymentAmount,
        'period' => 1,
    ];
}

function getReqTime()
{
    return floor(microtime(true) * 1000);
}

function getReqTimeByDate($date)
{
    return floor(strtotime($date)*1000);
}


/**
 * 计算每天的罚息
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param $investmentAmount
 * @param $days
 *
 * @return float|int
 */
function getOverdueInterest($investmentAmount, $days)
{
    if ($days <= 0) {
        return 0;
    }

    if ($days <= 30) { // 0.05%
        $rate = 0.0005;
    } elseif ($days > 30 && $days <= 60) { // 0.1%
        $rate = 0.001;
    } else { // 0.2%
        $rate = 0.002;
    }

    return numberFormat($investmentAmount * $rate * $days);
}

/**
 * 滞纳金
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param $amount
 * @param $days
 *
 * @return float|int
 */
function getOverdueFine($amount, $days)
{
    if ($days <= 0) {
        return 0;
    }

    if ($days <= 30) { // 0.1%
        $rate = 0.001;
    } elseif ($days > 30 && $days <= 60) { // 0.2%
        $rate = 0.002;
    } else { // 0.3%
        $rate = 0.003;
    }

    return numberFormat($amount * $rate * $days);
}

/**
 * 获取逾期天数
 *
 * @author gengzhiguo@xiongmaojinfu.com
 *
 * @param $repaymentDate
 *
 * @return int
 */
function getOverdueDays($repaymentDate)
{
    // 当前日期
    $today         = date('Y-m-d');
    $currentDate   = new DateTime($today);
    $repaymentDate = new DateTime($repaymentDate);

    $days = (int) $repaymentDate->diff($currentDate)->format('%R%a');

    return $days;
}

// 获取当前登录用户
if (!function_exists('authUser')) {
    /**
     * Get the authUser.
     *
     * @return mixed
     */
    function authUser()
    {
        return app('Dingo\Api\Auth\Auth')->user();
    }
}

if (!function_exists('dingoRoute')) {
    /**
     * 根据别名获得url.
     *
     * @param string $version
     * @param string $name
     * @param array  $params
     *
     * @return string
     */
    function dingoRoute($version, $name, $params = [])
    {
        return app('Dingo\Api\Routing\UrlGenerator')
            ->version($version)
            ->route($name, $params);
    }
}


/**
 * 文本编码(就是urlencode的功能)
 *
 * @param string $text 文本
 *
 * @return string
 * @author Me
 **/
function amazonEncode($text)
{
    /* $encodedText = ""; */
    /* $j = strlen($text); */
    /* for ($i=0;$i<$j;$i++) { */
    /*   $c = substr($text,$i,1); */
    /*   if (!preg_match("/[A-Za-z0-9-_.~]/",$c)) { */
    /*     $encodedText .= sprintf("%%%02X",ord($c)); */
    /*   } else { */
    /*     $encodedText .= $c; */
    /*   } */
    /* } */
    /* return $encodedText; */
    return urlencode($text);
}

/**
 * 请求参数签名
 *
 * @param string $method
 * @param string $queryVars       请求参数
 * @param string $secretAccessKey 私钥
 *
 * @return string
 * @author Me
 **/
function amazonSign($method, $queryVars, $secretAccessKey)
{
    $queryVars = paraFilter($queryVars);
    // 0. Append Timestamp parameter
    /* $url .= "&timestamp=".gmdate("Y-m-dTH:i:sZ"); */
    // 1a. Sort the UTF-8 query string components by parameter name
    /* $urlParts = parse_url($url); */
    /* parse_str($urlParts["query"],$queryVars); */
    ksort($queryVars);
    reset($queryVars);
    // 1b. URL encode the parameter name and values
    $encodedVars = [];
    foreach ($queryVars as $key => $value) {
        $encodedVars[amazonEncode($key)] = amazonEncode($value);
    }
    // 1c. 1d. Reconstruct encoded query
    $encodedQueryVars = [];
    foreach ($encodedVars as $key => $value) {
        $encodedQueryVars[] = $key . "=" . $value;
    }
    $encodedQuery = implode("&", $encodedQueryVars);
    // 2. Create the string to sign
    $stringToSign = $method;
    /* $stringToSign .= "n".strtolower($urlParts["host"]); */
    /* $stringToSign .= "n".$urlParts["path"]; */
    $stringToSign .= "n" . $encodedQuery;
    // 3. Calculate an RFC 2104-compliant HMAC with the string you just created,
    //    your Secret Access Key as the key, and SHA256 as the hash algorithm.
    if (function_exists("hash_hmac")) {
        // 二进制
        // $hmac = hash_hmac("sha256",$stringToSign,$secretAccessKey,TRUE);
        // 十六进制
        $hmac = hash_hmac("sha256", $stringToSign, $secretAccessKey);
    } elseif (function_exists("mhash")) {
        $hmac = mhash(MHASH_SHA256, $stringToSign, $secretAccessKey);
    } else {
        die("No hash function available!");
    }
    // 4. Convert the resulting value to base64
    $hmacBase64 = base64_encode($hmac);
    // 5. Use the resulting value as the value of the Signature request parameter
    // (URL encoded as per step 1b)
    /* $url .= "&signature=".amazonEncode($hmacBase64); */

    return amazonEncode($hmacBase64);
}

/**
 * 请求参数签名
 *
 * @param string $method
 * @param string $url             请求地址
 * @param string $secretAccessKey 私钥
 *
 * @return string
 * @author Me
 **/
function amazonSignForUrl($method, $url, $secretAccessKey)
{
    // 0. Append Timestamp parameter
    $url .= "&timestamp=" . time();
    /* $url .= "&timestamp=1397185781"; */
    // 1a. Sort the UTF-8 query string components by parameter name
    $urlParts = parse_url($url);
    parse_str($urlParts["query"], $queryVars);

    $queryVars = paraFilter($queryVars);

    ksort($queryVars);
    reset($queryVars);
    // 1b. URL encode the parameter name and values
    $encodedVars = [];
    foreach ($queryVars as $key => $value) {
        $encodedVars[amazonEncode($key)] = amazonEncode($value);
    }
    // 1c. 1d. Reconstruct encoded query
    $encodedQueryVars = [];
    foreach ($encodedVars as $key => $value) {
        $encodedQueryVars[] = $key . "=" . $value;
    }
    $encodedQuery = implode("&", $encodedQueryVars);
    // 2. Create the string to sign
    $stringToSign = $method;
    /* $stringToSign .= "n".strtolower($urlParts["host"]); */
    /* $stringToSign .= "n".$urlParts["path"]; */
    $stringToSign .= "n" . $encodedQuery;
    // 3. Calculate an RFC 2104-compliant HMAC with the string you just created,
    //    your Secret Access Key as the key, and SHA256 as the hash algorithm.
    if (function_exists("hash_hmac")) {
        // 二进制
        // $hmac = hash_hmac("sha256",$stringToSign,$secretAccessKey,TRUE);
        // 十六进制
        $hmac = hash_hmac("sha256", $stringToSign, $secretAccessKey);
    } elseif (function_exists("mhash")) {
        $hmac = mhash(MHASH_SHA256, $stringToSign, $secretAccessKey);
    } else {
        die("No hash function available!");
    }
    // 4. Convert the resulting value to base64
    $hmacBase64 = base64_encode($hmac);
    // 5. Use the resulting value as the value of the Signature request parameter
    // (URL encoded as per step 1b)
    $url .= "&sign=" . amazonEncode($hmacBase64);

    return $url;
}

/**
 * 除去数组中的空值和签名参数
 *
 * @param array $para 签名参数组
 *
 * @return array 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para)
{
    $paraFilter = [];
    foreach ($para as $key => $val) {
        if ($key == "signature" || $key == "sign_type" || $val === "") {
            continue;
        } else {
            $paraFilter[$key] = $para[$key];
        }
    }
    return $paraFilter;
}

/**
 * DES加密
 *
 * @param string $string 要加密的字符串
 * @param string $key    加密使用的key值
 *
 * @return string
 */
function desEncode($string, $key)
{
    $key  = substr($key, 0, 8);
    $size = mcrypt_get_block_size('des', 'ecb');
    $pad  = $size - (strlen($string) % $size);
    $string .= str_repeat(chr($pad), $pad);
    $td = mcrypt_module_open('des', '', 'ecb', '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    @mcrypt_generic_init($td, $key, $iv);
    $data = mcrypt_generic($td, $string);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return bin2hex($data);
}

if (!function_exists('getBaoFooUid')) {
    function getBaoFooUid($uid)
    {
        return (((0x0000FFFF & $uid) << 16) + ((0xFFFF0000 & $uid) >> 16));
    }
}

if (!function_exists('getPlatformUid')) {
    function getPlatformUid($uid)
    {
        return (((0x0000FFFF & $uid) << 16) + ((0xFFFF0000 & $uid) >> 16));
    }
}

if (!function_exists('getRepaymentDate')) {
    function getRepaymentDate($queryDate, $x = 1)
    {
        $date = new \DateTime($queryDate);
        // T+1计息
        $interval = 'P0M1D';
        $date->add(new \DateInterval($interval));
        $currentDate = $date->format('Y-m-d');


        // 日期所在月份的第一天
        $firstDate = getFirstDay($currentDate);
        // 日期所在月份的最后一天
        $lastDate = getLastDay($currentDate);

        if ($currentDate === $firstDate) {
            $repaymentDate = getXFirstDay($currentDate, $x);
        } elseif ($currentDate === $lastDate) {
            $repaymentDate= getXLastDay($currentDate, $x);
        } else {
            $xLastDate = getXLastDay($currentDate, $x);
            $xLastDay = explode('-', $xLastDate);
            $currentDay = explode('-', $currentDate)[2];
            if ($currentDay < $xLastDay[2]) {
                $xLastDay[2] = $currentDay;
            }

            $repaymentDate = implode('-', $xLastDay);
        }

        return $repaymentDate;

    }
}

if (!function_exists('getLastDay')) {
    function getLastDay($queryDate)
    {
        $date = new DateTime($queryDate);
        //Last day of month
        $date->modify('last day of this month');
        $lastday= $date->format('Y-m-d');

        return $lastday;
    }
}

if (!function_exists('getFirstDay')) {
    function getFirstDay($queryDate)
    {
        $date = new \DateTime($queryDate);
        //First day of month
        $date->modify('first day of this month');
        $firstday= $date->format('Y-m-d');

        return $firstday;
    }
}

if (!function_exists('getXLastDay')) {
    function getXLastDay($queryDate, $x=1)
    {
        $date = new \DateTime($queryDate);
        //Last day of month
        $date->modify('last day of '.$x.' month');
        $lastday= $date->format('Y-m-d');

        return $lastday;
    }
}

if (!function_exists('getXFirstDay')) {
    function getXFirstDay($queryDate, $x=1)
    {
        $date = new \DateTime($queryDate);
        //First day of month
        $date->modify('first day of '.$x.' month');
        $firstday= $date->format('Y-m-d');

        return $firstday;
    }
}
