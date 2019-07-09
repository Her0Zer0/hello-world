<?php
//CHECK TO SEE IF THE PROCESS IS COMPLETED (DONE_FLG = "Y")
//IF SO
//EXIT WITH ERROR => CANT UPDATE PROCESS THAT HAS BEEN COMPLETED
//ELSE
//UPDATE RECORD


//GET CONNECTED 
include"../connect.php";
//GET FUNCTIONAL
include"../functions.php";
//CHECK CONNECTION
if($conn){
    
    $idtrec = $_POST['idtrecord'];
    $idtnum = $_POST['idtnum'];
    $prtnum = $_POST['prtnum'];
    $empnum = $_POST['user'];
    $empShift = $_POST['empShift'];
    $mach = $_POST['mach'];
    $mcode = getMcode($_POST['currentOperation']);
    $sort_seq = getProcessSeq($_POST['currentOperation']);
    $qtyToSet = cleanString($_POST['qty']);
    $checkChanged = 0;
    
    //CHECK TO SEE IF THE PROCESS IS COMPLETED (DONE_FLG = "Y")
    
    $sql = "SELECT  mcode, 
                    usr_id,
                    usr_shift,
                    qty, 
                    machine, 
                    sort_seq, 
                    created_date, 
                    done_flg 
            FROM idt_rec WHERE idt_rec_id = '" . $idtrec . "' AND idt_id = '" . $idtnum . "';";
    
    $res = sqlsrv_query($conn, $sql);
    if($res){
        
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        //IF SO
            //EXIT WITH ERROR => CANT UPDATE PROCESS THAT HAS BEEN COMPLETED
        if($row['done_flg'] == "Y"){
            $dataToSendBack = array("error" => "process-completed");
            echo json_encode($dataToSendBack);
            exit;
        }
        
        //CHECK TO SEE IF THERE IS ANYTHING THAT HAS CHANGED
        if($row['mcode'] == $mcode){
            $checkChanged = $checkChanged + 1;
        }
        
        if($row['qty'] == $qtyToSet){
            $checkChanged = $checkChanged + 1;
        }
        
        if($row['machine'] == $mach){
            $checkChanged = $checkChanged + 1;
        }
        
        if($checkChanged == 3){
            $dataToSendBack = array("error" => "nothing-to-change");
            echo json_encode($dataToSendBack);
            exit;
        }
       //CHECK TO SEE IF THE MCODE MATCHES ANY OF THE COMPLETED MCODES
        
        $sqlMcodeCheck = "SELECT DISTINCT idt_rec.mcode AS mcode,        
                                        idt_rec.done_flg AS dflg, 
                                        idt_rec.sort_seq
                                FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                                WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                                ORDER by idt_rec.sort_seq desc;";
        
        $resMcode = sqlsrv_query($conn, $sqlMcodeCheck);
        if($resMcode){
           
            while($mrow = sqlsrv_fetch_array($resMcode, SQLSRV_FETCH_ASSOC)){
                
                if($mcode == $mrow['mcode']){
                    $dataToSendBack = array("error" => "process-completed");
                    echo json_encode($dataToSendBack);
                    exit;
                }
           } 
        }
        
        //GET THE LAST KNOWN GOOD QTY FROM COMPLETED PROCESSES
        
            $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                SUM(idt_rec.qty) AS qty,
                                idt_rec.done_flg AS dflg
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
        $res = sqlsrv_query($conn, $checkqtysql);
        
        $num_of_rows = sqlsrv_has_rows($res);
        //var_dump($num_of_rows == 0);
        if($num_of_rows){
            //IF RESULTS ARE RETURNED CHECK THEM AGAINST OUR QTY TO STORE
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            $storedCompQty = $row['qty'];          
            
            
            if($qtyToSet > $storedCompQty){//IF GREATER THAN STORED QTY EXIT
                
                $dataToSendBack = array("error" => "qty-to-great");
                echo json_encode($dataToSendBack);
                exit;
            }
        }
        
        //CHECK TO SEE IF THE MCODE WE ARE TRYING TO EDIT IS THE SAME AS THE MCODE THAT IS NON COMPLETED. OTHERWISE WE NEED TO COMPLETE THE PREVIOUS PROCESS. 
        
           $sql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                SUM(idt_rec.qty) AS qty,
                                idt_rec.done_flg AS dflg
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'N'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
                $res = sqlsrv_query($conn, $sql);
        
        if($res){
            //CHECK  TO SEE IF MCODE MATCHES
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            
            if($mcode != $row['mcode']){
                $doneflg = 'Y';
                //UPDATE LAST OPERATION TO DONE FLG = "Y"
                //GET ALL PRIMARY IDS FROM THE IDT RECORD TABLE THAT HAVE A DONE_FLG SET TO "N"
                    
                    $idrSetToNo = "SELECT idt_rec.idt_rec_id as idtrecord,
                                                            idt_rec.sort_seq
                                                            FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                                                            WHERE idt.idt_id = '" . $idtnum . "' AND done_flg = 'N' AND idt_rec.idt_rec_id != '" . $idtrec . "'
                                                            ORDER by idt_rec.sort_seq desc;";
                
                $res = sqlsrv_query($conn, $idrSetToNo);
                
                if($res){
                    
                    
                    $idrRecArray = array();
                        
                            while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                              
                                array_push($idrRecArray, $row['idtrecord']);                                
                            }
                    
                    foreach($idrRecArray as $record){
                            
                            $updateIdtRec = "UPDATE idt_rec SET done_flg = '" . $doneflg . "' WHERE idt_rec_id = '" . $record . "';";
                            
                            $res = sqlsrv_query($conn, $updateIdtRec);
                            
                           if(!$res){
                               sqlsrv_rollback();
                               
                               $dataToSendBack = array("error" => "rollback-y-update");
                                echo json_encode($dataToSendBack);
                               exit;
                           }
                            
                        } 
                }
            }
        }
        //
        
        
        //ELSE
            //WE ARENT EDITING A PROCESS THAT HAS BEEN COMPLETED
            //WE ARENT STORING A QTY GREATER THAN THE LAST KNOWN GOOD QTY
        $doneflg = 'N';
            //UPDATE RECORD
        $updateRecord = "UPDATE idt_rec SET mcode = '" . $mcode . "', 
                                            usr_id = '" . $empnum . "',
                                            usr_shift = '" . $empShift . "', 
                                            qty = '" . $qtyToSet . "', 
                                            machine = '" . $mach . "', 
                                            sort_seq = '" . $sort_seq . "',
                                            done_flg = '" . $doneflg . "',
                                            created_date = CURRENT_TIMESTAMP 
                                            WHERE idt_rec_id = '" . $idtrec . "' AND idt_id = '" . $idtnum . "';";
            $res = sqlsrv_query($conn, $updateRecord);
        
        if($res){//SEND BACK SUCCESS
            $dataToSendBack = array("msg" => "success");
            echo json_encode($dataToSendBack);
        }
    }
}else{
    //EXIT WITH ERROR 
    $dataToSendBack = array("error" => "error-db");
    echo json_encode($dataToSendBack);
}
//CLOSE CONNECTIONS
sqlsrv_close($conn);
?>