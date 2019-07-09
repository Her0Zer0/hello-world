<?php
include"../connect.php";
if($conn){
    $prtnum = $_POST['part'];
    
    $sql = "SELECT DISTINCT [PART-NO] , CUSTOMER AS customer FROM PART WHERE CUSTOMER IS NOT NULL AND [PART-NO] = '".$prtnum."';";
    
    $res = sqlsrv_query($conn, $sql);
    
    if($res){
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        echo $row['customer'];
    }else{
        echo "No Customer Listed For This Part";
    }
}else{
    //show nothing because we aren't stopping anything because we don't have a customer
}

sqlsrv_close($conn);
?>