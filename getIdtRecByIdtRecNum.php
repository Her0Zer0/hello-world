<?php
//GET CONNECTION
include"../connect.php";

if($conn){//WE HAVE CONNECTION
    
    //STORE VARS
    $idtrec = $_POST['idtrecord'];
    $user = $_POST['user'];
    $dataToSendBack = array();
    //GET IDT RECORD
    $sql = "SELECT  idt_rec_id AS record,
                            mcode AS mcode,
                            usr_id AS usr_id,
                            qty AS qty,
                            usr_shift AS shift,
                            machine AS mach,
                            sort_seq
                    FROM idt_rec WHERE idt_rec_id = '" . $idtrec . "';";
    //RUN QUERY 
        $res = sqlsrv_query($conn, $sql);
    //CHECK IF WE HAVE A RECORD
    if($res){//WE HAVE A RECORD
        //GET ASSOCIATIVE ARRAY
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        
        //CHECK IF USERS MATCH
        
        if($user == $row['usr_id']){
            //STORE ARRAY FOR LATER
        array_push($dataToSendBack, array("idtrecord" => $row['record'], 
                                          "mcode" => $row['mcode'], 
                                          "usr_id" => $row['usr_id'], 
                                          "qty" => $row['qty'], 
                                          "shift" => $row['shift'], 
                                          "mach" => $row['mach'], 
                                          "sort_seq" => $row['sort_seq'])
                                                                        );
            
            echo json_encode($dataToSendBack);
            
        }else{
            //ERROR USERS DONT MATCH
            $dataToSendBack = array("error" => "error-wrong-user");
            echo json_encode($dataToSendBack);
            exit;
        }
        

        
    }else{
        //ERROR GETTING THE IDT RECORD
        $dataToSendBack = array("error" => "error-record");
        echo json_encode($dataToSendBack);
        exit;
    }    
}else{//NO CONNECTION SHOW ERROR
    $dataToSendBack = array("error" => "error-db");
    echo json_encode($dataToSendBack);
    exit;
}
//CLOSE CONNECTIONS
sqlsrv_close($conn);
?>