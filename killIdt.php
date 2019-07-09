<?php
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONAL

include"../functions.php";

if($conn){

    $idtnum = cleanString($_POST['idtnum']);
    
    $sql = "SELECT idt_rec_id FROM idt_rec WHERE idt_id in (SELECT idt_id FROM idt WHERE idt_id = '" . $idtnum . "');";
    
    $res = sqlsrv_query($conn, $sql);
    
    if($res){
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        
        
            $delrecsql = "DELETE FROM idt_rec WHERE idt_rec_id = '" . $row['idt_rec_id'] . "';";
        
            $delsql = "DELETE FROM idt WHERE idt_id = '" . $idtnum . "';";
            
            $recres = sqlsrv_query($conn, $delrecsql);
            $res = sqlsrv_query($conn, $delsql);
            
            if($res && $recres){
                echo "success";
            }
        }else{
            //we have other records, back out
            
                echo "fail";
        }
    
    
}else{
    //no connection
}
//CLOSE CONNECTION
sqlsrv_close($conn);
?>