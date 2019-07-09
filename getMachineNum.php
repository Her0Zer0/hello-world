
<?php
include"../connect.php";


$dataToSendBack = array();

if($conn){
    $sql = "SELECT DISTINCT mach_code FROM mach WHERE mach_code NOT like 'M%' AND mach_code NOT LIKE 'C%';";
    
    $res = sqlsrv_query($conn, $sql);
    
    if($res){
        //$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        
        while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
            
            array_push($dataToSendBack, $row['mach_code']);
        }
            
            echo json_encode($dataToSendBack);
    }else{
        $dataToSendBack = array("error" => "error-list");
        echo json_encode($dataToSendBack);
        exit;
    }
}else{
    $dataToSendBack = array("error" => "error-db");
    echo json_encode($dataToSendBack);
    exit;
}

sqlsrv_close($conn);
?>