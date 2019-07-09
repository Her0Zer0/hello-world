<?php
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONS
include"../functions.php";

if($conn){
    
    $idtrec = $_POST['idtrecord'];
    $empnum = $_POST['user'];
    $idtRecArray = array();
    $errors = "";
    
    //CHECK TO SEE IF THE PROCESS HAS BEEN COMPLETED (DONE_FLG = "Y")
    
    $res = checkForCompletedProc($conn, $idtrec);

    if($res){
        //CANT DELETE COMPLETED PROCESSES
        //SEND ERROR
        $dataToSendBack = array("error" => "proc-completed");
        echo json_encode($dataToSendBack);
        exit;
    }
    
    //GET THE IDTNUM FOR STORAGE
        $sql = "SELECT  usr_id,idt_id, done_flg, sort_seq FROM idt_rec WHERE idt_rec_id = '" . $idtrec . "' AND done_flg = 'N';";
    
    $res = sqlsrv_query($conn, $sql);
    //IF NUM OF ROWS GREATER THAN 0 PROCESS IS COMPLETE
    $num_rows = sqlsrv_has_rows($res);
    
    if($num_rows == 0){//SOMETHING HAPPENED IF WE CAN'T PULL BACK A RECORD FOR DELETE
        $dataToSendBack = array("error" => "no-record");
        echo json_encode($dataToSendBack);
        exit;        
    }
    
    //ELSE STORE IDT NUMBER TO CHECK FOR OTHER RECORDS THAT ARE NOT COMPLETE
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    
    $idtnum = $row['idt_id'];
    //CHECK TO SEE IF WE HAVE OTHER RECORDS THAT ARE NOT COMPLETED BESIDES THE ONE WE HAVE TO DELETE
    $sql = "SELECT * FROM idt_rec 
                            WHERE idt_id = '" . $idtnum. "' 
                            AND idt_rec_id != '" . $idtrec . "'
                            AND done_flg = 'N';";
    $res = sqlsrv_query($conn, $sql);
    //IF NUM OF ROWS GREATER THAN ZERO THEN WE DONT UPDATE PREVIOUS RECORDS TO DONE FLG (N)
    $num_rows = sqlsrv_has_rows($res);
    if($num_rows > 0){//HAS MORE THAN ONE RECORD
        
           //READY FOR DELETE
                $sqlDeleterecord = "DELETE FROM idt_rec WHERE idt_rec_id = '" . $idtrec . "';";

                $res = sqlsrv_query($conn, $sqlDeleterecord);
                if(!$res){
                     $dataToSendBack = array("error" => "error-delete");
                    echo json_encode($dataToSendBack);
                    exit;
                }        
        //UPDATE FUTURE DAILY TRANSACTION TABLE HERE AND ROLLBACK EXIT IF FAIL
        
        $dataToSendBack = array("msg" => "success");
        echo json_encode($dataToSendBack);
        //CLOSE CONNECTION
        sqlsrv_close($conn);
        exit;
        
    }else{//ELSE WE LOOK FOR THE PREVIOUS RECORD
        
        //CHECK FOR THE LAST PROCESS THAT HAS BEEN COMPLETED (DONE FLG ="Y")
        $sql = "SELECT TOP 1 idt_rec.mcode AS mcode
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
        
        $res = sqlsrv_query($conn, $sql);
        $num_rows = sqlsrv_has_rows($res);
        
        //IF TRUE GET LAST KNOWN PROCESS AND CONTINUE
        //ELSE THIS IS THE ONLY RECORD SO DELETE IT AND EXIT
    
        if($num_rows > 0){//WE HAVE OTHER RECORDS
            
            
            
            /* Begin the transaction. */
//            if ( sqlsrv_begin_transaction( $conn ) === false ) {
//                 die( print_r( sqlsrv_errors(), true ));
//            }

            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            //STORE LAST KNOWN COMPLETED PROCESS
            $lastKnownCompletedProcess = $row['mcode'];
            
            $sql = "SELECT idt_rec.idt_rec_id, idt_rec.mcode AS mcode
                            FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                            WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.mcode = '" . $lastKnownCompletedProcess . "'
                            ORDER by idt_rec.sort_seq desc;";
            
            $res = sqlsrv_query($conn, $sql);

            if($res){//PULLED RECORDS SUCCESSFULLY

                
                //STORE IDT RECORD ID'S IN AN ARRAY
                while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                    array_push($idtRecArray, $row['idt_rec_id']);
                }
                
                //LOOP THRU ARRAY AND UPDATE PREVIOUS PROCESS TO DONE FLG ="N"
                for($i = 0; $i < sizeof($idtRecArray); $i++){
                $sql = "UPDATE idt_rec SET done_flg = 'N' WHERE idt_rec_id = '" . $idtRecArray[$i] . "';";
                
                    $res = sqlsrv_query($conn, $sql);
                    
                    if(!$res){//IF UPDATE FAILS ROLLBACK
                        sqlsrv_rollback($conn); 
                        //UPDATE FAILED DO TO LACK OF RECORDS
                        $dataToSendBack = array("error" => "no-update-previous");
                        echo json_encode($dataToSendBack);
                        exit;
                    }
                }//END FOR LOOP
                
                //READY FOR DELETE
                $sqlDeleterecord = "DELETE FROM idt_rec WHERE idt_rec_id = '" . $idtrec . "';";

                $res = sqlsrv_query($conn, $sqlDeleterecord);
                
                if(!$res){//DELETE FAILED
                    //ROLLBACK
                    sqlsrv_rollback($conn);
                    //SEND ERROR
                    $dataToSendBack = array("error" => "error-delete");
                    echo json_encode($dataToSendBack);
                    exit;
                }
                //UPDATE THE DAILY TRANSACTION TABLE HERE ELSE FAIL AND ROLLBACK
                
                //COMMIT
                    sqlsrv_commit($conn);
                    //SEND SUCCESS
                    $dataToSendBack = array("msg" => "success");
                    echo json_encode($dataToSendBack);
                    exit; 
                
                
            }else{//QUERY FAILED TRYING TO GET OTHER RECORDS 
                //ROLLBACK AND EXIT
                sqlsrv_rollback($conn);
                $dataToSendBack = array("error" => "error-on-records");
                echo json_encode($dataToSendBack);
                exit;
            }
            
            
            
            
        }else{//no rows with done flag set to Y
            //IF WE ARE HERE THIS MEANS THAT THIS IS THE ONLY RECORD SO DELETE IT AND THE IDT RECORD BECAUSE NOTHING ELSE IS ON IT
            
            $dataToSendBack = array("error" => "kill-the-ticket");
            echo json_encode($dataToSendBack);
        }
    }
    
}else{
    $dataToSendBack = array("error" => "error-db");
    echo json_encode($dataToSendBack);
    exit;
}
//CLOSE CONNECTION
sqlsrv_close($conn);
?>