<?php
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONAL
include"../functions.php";
//CHECK CONNECTIONS
if($conn){
//STORE VARIABLES    
$mach = $_POST['mach'];
  
    $res = getIdtFromLotByMachine($conn, $mach);

    if($res){
                                
        $dataToSendBack = array('lotnum'=> $res['lotnum'], 'prtnum'=> $res['prtnum'], 'mach'=> $res['mach'], 'customer'=> $res['customer']);
        
        echo json_encode($dataToSendBack);
        
    }else{
             //if error send back message to contact administrator with error number and specific message for IT department
            echo "error-list";
        }
        
    }else{
        //if error then send back error and produce message to user about connection
        echo "error-db";
    }

sqlsrv_close($conn);
?>