<?php
#include("app/serveradm/xhprof.php");
define('ROOT_DIR',realpath(dirname(__FILE__)));
require(ROOT_DIR.'/app/base/kernel.php');
kernel::boot();
if(defined("STRESS_TESTING")){
    b2c_forStressTest::logSqlAmount();
}
