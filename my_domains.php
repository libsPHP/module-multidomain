<?php
use Magento\Framework\App\Bootstrap;
$debug=true;
$stop=false;


function getStoreCode() {
    global $backup_view;
    $storecode="";
    include("my_domains_views.php");
    //print_r($domain2store);

    $_SERVER['HTTP_HOST_ORIG']=$_SERVER['HTTP_HOST'];
    $_SERVER['REQUEST_URI_ORIG']=$_SERVER['REQUEST_URI'];

    $host = $_SERVER['HTTP_HOST']; // Получаем имя хоста
    $uri = $_SERVER['REQUEST_URI']; // Получаем путь URL
    $parts = explode('/', $uri);
    $firstFolder = isset($parts[1]) ? $parts[1] : ''; // Если папки нет, то будет пустая строка
    array_shift($parts);
    array_shift($parts);
    $withoutFirstFolder = '/' . implode('/', $parts);

    //echo "Имя хоста: $host<br>";
    //echo "Первая папка: $firstFolder";

    $host2=$host."/".$firstFolder;

    //Сверяем, системный ли домен
    if(isset($domains2view[$host]))
    {
        return $domains2view[$host];
    }


    return $backup_view;
}


function headersDebug() {
    global $storecode;
    header('X-Magento-Storecode: '.$storecode);
    header('X-Magento-Host: '.gethostname());
}

function showDebug() {
    global $storecode;
    global $backup;
    global $backup_view;
    $headers=headers_list();
    $containsContentTypeTextHtml = false;

    foreach ($headers as $header) {
        if (strpos($header, "Content-type: text/html") !== false) {
            $containsContentTypeTextHtml = true;
            break;
        }
    }

    if ($containsContentTypeTextHtml) {
                echo("\n<!--storecode:".$storecode."-->");
                echo("\n<!--_SERVER['HTTP_HOST']:".$_SERVER['HTTP_HOST']."-->");
                echo("\n<!--_SERVER['HTTP_HOST_ORIG']:".$_SERVER['HTTP_HOST_ORIG']."-->");
                echo("\n<!--_SERVER['REQUEST_URI']:".$_SERVER['REQUEST_URI']."-->");
                echo("\n<!--_SERVER['REQUEST_URI_ORIG']:".$_SERVER['REQUEST_URI_ORIG']."-->");
                echo("\n<!--host:".gethostname()."-->");
                echo("\n<!--backup:".$backup."-->");
                echo("\n<!--backup_view:".$backup_view."-->");

    }
}

$backup_view="default";

$storecode=getStoreCode();

if(in_array($storecode,array("","end","end:domains2spec","end:folders2spec")))
{
    showDebug();
    die();
}

$backup=false;


if($debug) headersDebug();

if($stop) 
{
    showDebug();
    echo("ok");
    die();
}

try {
    $params=$_SERVER;
    $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = isset($storecode) ? $storecode : '';
    $params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
    //$params[StoreManager::PARAM_RUN_CODE] = $storeCode ?: '';
    //$params[StoreManager::PARAM_RUN_TYPE] = 'store';

    $bootstrap = Bootstrap::create(BP, $params);
    /** @var \Magento\Framework\App\Http $app */
    $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);

    $bootstrap->run($app);
} catch (Exception $e) {
    if(strstr($e->getMessage(), "The store that was requested wasn't found")){
        $backup=true;

        $params=$_SERVER;
        $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = isset($backup_view) ? $backup_view : '';
        $bootstrap = Bootstrap::create(BP, $params);
        /** @var \Magento\Framework\App\Http $app */
        $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);

        $bootstrap->run($app);
    }
}



if($debug) showDebug();



//OLD:
//print_r($_SERVER);
//echo($params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE]);
//$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
//$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'website';
//echo("<!--storecode:".$storecode."-->");
//echo("<!--_SERVER['REQUEST_URI']:".$_SERVER['REQUEST_URI']."-->");