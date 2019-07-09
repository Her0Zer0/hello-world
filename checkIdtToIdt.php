<?php
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONS
include"../functions.php";

if($conn){
    $idtnum = cleanString($_POST['idtnum']);
    
    //CHECK TO SEE IF HAVE ALREADY MERGED THIS IDT
    
    $sql = "SELECT * FROM idt WHERE idt_id = '" . $idtnum . "' AND to_idt IS NOT NULL;";
    
    $res = sqlsrv_query($conn, $sql);
    $num_of_rows = sqlsrv_has_rows($res);
    if($num_of_rows > 0){
        echo "false";
    }else{
        
        $sql = "SELECT * FROM idt WHERE idt_id = '" . $idtnum . "';";
        
        $res = sqlsrv_query($conn, $sql);
        $num_of_rows = sqlsrv_has_rows($res);
        if($num_of_rows == 0 || !$num_of_rows){
            echo "false";
            exit;
            
        }
        
    echo "true";
        
    }
}else{
    
}

sqlsrv_close($conn);
?>