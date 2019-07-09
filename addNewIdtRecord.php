<?php
/*
*
*       FILE: ADD NEW IDT REOCRD.PHP
*       AUTHOR: ROBERT SMITH
*       LAST MODIFIED: 12/08/2017
*       
*
*       CHECKS INCORPERATED IN THIS FILE:
*
*           COMPLETED PROCESSES:
*
*           CHECKS TO SEE IF THERE ARE ANY COMPLETED PROCESSES TO PULL QTY'S FOR IDT LABEL
*               IF SO
*                   CHECKS TO SEE IF THE PROCESS THAT IS TRYING TO BE STORED IS THE SAME AS A COMPLETED PROCESS (DONE FLG SET TO "Y")
*                   CHECKS TO SEE IF THE QTY TO STORE IS GREATER THAN THE TOTAL LAST KNOWN GOOD QTY FOR THE IDT
*                   CHECKS TO SEE IF THERE HAS BEEN ANOTHER RECORD THAT HAS BEEN STORED FOR THE SAME PROCESS THAT IS NOT COMPLETED (DONE FLG SET TO "N")
*                      IF SO
*                           CHECKS TO SEE IF THE COMBINED QTY'S OF THE QTY TO BE STORED AND THE PROCESS THAT HAS BEEN STORED IS GREATER THAN THE TOTAL QTY OF THE IDT
*                       CHECKS TO SEE IF THE PROCESS IS DIFFERENT THAN THE LAST PROCESS THAT WAS STORED
*                           IF SO
*                               UPDATES THE LAST PROCESS TO A COMPLETED STATUS (DONE FLG SET TO "Y")
*
*           UNCOMPLETED PROCESSES:
*
*           CHECKS TO SEE IF THE LAST KNOWN PROCESS IS THE SAME AS THE PROCESS TO BE STORED
*               IF SO
*                   INSERTS THE PROCESS AND ECHO'S THE MSG
*           CHECKS TO SEE IF THE PROCESS TO STORE IS DIFFERENT THAN THE PROCESS THAT IS CURRENTLY STORED
*               IF SO
*                   CHECKS THE QTY TO STORE AGAINST THE PREVIOUSLY STORED QTY 
*                       IF QTY TO STORE GREATER EXIT
*                       ELSE
*                       UPDATES THE PREVIOUS RECORDS TO A COMPLETED STATUS (DONE FLG SET TO "Y")
*                       INSERTS THE DATA INTO THE IDTRECORD TABLE AND SHOW COMPLETED MESSAGE
*
*       
*
*/
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONAL
include"../functions.php";
//CHECK IF CONNECTED
if($conn){//WE ARE CONNECTED
    //STORE VARS
    $idtnum = $_POST['idtnum'];
    $mach = $_POST['mach'];
    $prtnum = $_POST['prtnum'];
    $qtyToAdd = cleanString($_POST['qty']);
    $mcode = getMcode($_POST['currentOperation']);
    $sort_seq = getProcessSeq($_POST['currentOperation']);
    $empshift = $_POST['empShift'];
    $user = $_POST['user'];
    $dataToSendBack = array();  
    //GET TOP 1 OF MAX QTY WITH DONE_FLG = 'Y'
    
    $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                SUM(idt_rec.qty) AS qty,
                                idt_rec.done_flg AS dflg
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
    
    $res = sqlsrv_query($conn, $checkqtysql);
    $num_rows = sqlsrv_has_rows($res);
    //CHECK TO SEE IF WE HAVE A LAST KNOWN GOOD PRODUCT VALUE
    if($num_rows){//IF YES
        
        //CHECK TO SEE IF VALUE IS GREATER THAN VALUE PULLED BACK
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        
        //STORE LAST KNOWN GOOD PROCESS AND QTY
            $lastKnownGoodProcess = $row['mcode'];
            $lastKnownGoodQty = $row['qty'];
            
        //IF ADD TO QTY IS LARGER THAN LAST KNOWN GOOD QTY EXIT
            if($qtyToAdd >= $lastKnownGoodQty){
                $dataToSendBack = array("error" => "error-qty-to-great");
                echo json_encode($dataToSendBack);
                exit;
            }
        
        /*
        *
        *   CHECK TO SEE IF THE USER IS TRYING TO ADD ANOTHER PROCESS OR THE SAME PROCESS THAT HAS BEEN COMPLETED
        *   IF SO EXIT WITH ERROR
        */
        //
        
        $checkmcodesql = "SELECT idt_rec.mcode AS mcode
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
        
        $res = sqlsrv_query($conn, $checkmcodesql);
        
            if($res){
                
                while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                    if($mcode == $row['mcode']){
                        //IF PROCESS EXISTS CHECK TO SEE IF DONE FLAG IS SET TO Y, IF SO EXIT BECAUSE WE CAN'T ADD MORE TO A DONE PROCESS     
                        $dataToSendBack = array("error" => "error-process-complete");
                        echo json_encode($dataToSendBack);
                        exit;
                    }
                }
            }
        /*
        *
        *       CHECK TO SEE IF ANY UNFINISHED PROCESSES EXIST
        *       AND IF SO, CHECK FOR CURRENT QTY TO LAST KNOWN GOOD QTY
        *       
        */
        
        //CHECK TO SEE IF THERE HAS BEEN A QTY STORED FOR THIS PROCESS ALREADY
            $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                SUM(idt_rec.qty) AS qty,
                                idt_rec.done_flg AS dflg
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'N'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
    
                $res = sqlsrv_query($conn, $checkqtysql);
                $num_rows = sqlsrv_has_rows($res);
        
                
            //CHECK TO SEE IF WE STORED THIS PROCESS ALREADY
            if($num_rows){//IF YES
                //CHECK TO SEE IF VALUE IS GREATER TOGETHER WITH QTY TO ADD THAN VALUE PULLED BACK
                    
                $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
                
                $lastKnownUnfinishedQty = $row['qty'];
                $lastKnownUnfinishedProcess = $row['mcode'];
                
                
                if($mcode == $lastKnownUnfinishedProcess){
                    //SET DONEFLG TO N BECAUSE WE ONLY SET IT TO YES IF WE'VE HAVE WENT TO THE NEXT STEP
                    
                    $doneflg = 'N';
                    
                    //STORE TOTAL QTY FOR CHECK
                    
                    $totalValueToCheck = $qtyToAdd + $lastKnownUnfinishedQty;
                     
                    //IF ADD TO QTY IS LARGER THAN LAST KNOWN GOOD QTY EXIT
                    
                    if($totalValueToCheck > $lastKnownGoodQty){
                        
                        $dataToSendBack = array("error" => "error-qty-to-great");
                        echo json_encode($dataToSendBack);
                        exit;
                        
                    }
                            // BEGIN TRANSACTION
                            if ( sqlsrv_begin_transaction( $conn ) === false ) {
                                $dataToSendBack = array("error" => "error-transaction");
                                echo json_encode($dataToSendBack);
                            }

                        //CALL THE NEXT IDR FUNCTION

                        $newIdtRecNum = getNextIDTRecNum($conn);

                        if(!$newIdtRecNum){
                            sqlsrv_rollback($conn);
                            //SHOW ERROR
                            $dataToSendBack = array("error" => "rollback-y-nextnum");
                            echo json_encode($dataToSendBack);
                            exit;
                        }

                        $insertIdtRecord = createNewIDTRec($conn, $newIdtRecNum, $idtnum, $mcode, $user, $qtyToAdd,$empshift, $mach, $doneflg, $sort_seq);

                        if(!$insertIdtRecord){
                            sqlsrv_rollback($conn);
                            //SHOW ERROR
                            $dataToSendBack = array("error" => "rollback-y-insert");
                            echo json_encode($dataToSendBack);
                            
                            exit;
                        }else{
                            sqlsrv_commit($conn);
                            $dataToSendBack = array("msg" => "data-updated");
                            echo json_encode($dataToSendBack);
                        }
                }else{
                    //IF MCODE ISN'T EQUAL TO LAST KNOWN UNFINISHED CODE THEN WE NEED TO SET THE DONE FLAG TO "Y"
                    //SO WE CAN UPDATE THE LAST PROCESS TO FINISHED
                   
                    $doneflg = 'Y'; 
                    
                    // BEGIN TRANSACTION
                            if ( sqlsrv_begin_transaction( $conn ) === false ) {
                                 $dataToSendBack = array("error" => "error-transaction");
                                echo json_encode($dataToSendBack);
                            }
                        
                        //GET ALL PRIMARY IDS FROM THE IDT RECORD TABLE THAT HAVE A DONE_FLG SET TO "N"
                    
                    $idrSetToNo = "SELECT idt_rec.idt_rec_id as idtrecord,
                                                            idt_rec.sort_seq
                                                            FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                                                            WHERE idt.idt_id = '" . $idtnum . "' AND done_flg = 'N'
                                                            ORDER by idt_rec.sort_seq desc;";
                    
                    $res = sqlsrv_query($conn, $idrSetToNo);
                    //CHECK TO SEE IF WE RETURNED ANYTHING TO UPDATE WITH
                    if($res){//WE HAVE RECORDS TO UPDATE
                        //LOOP THROUGH THE RECORDS AND UPDATE THEM TO DONE AND IF IT FAILS ROLLBACK 
                        
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
                        //IF NOT FAILED SO FAR CALL FOR OUR INSERT
                        
                        //CALL THE NEXT IDR FUNCTION

                        $newIdtRecNum = getNextIDTRecNum($conn);
                            //IF WE DIDNT GET THE NEXT NUMBER TO INSERT ROLLBACK
                        if(!$newIdtRecNum){
                            sqlsrv_rollback($conn);
                            //SHOW ERROR
                            $dataToSendBack = array("error" => "rollback-y-newprocess");
                            echo json_encode($dataToSendBack);
                            exit;
                        }
                        //ELSE INSERT THE NEW RECORD WITH THE DONE FLAG SET TO "N"
                        $insertIdtRecord = createNewIDTRec($conn, $newIdtRecNum, $idtnum, $mcode, $user, $qtyToAdd,$empshift, $mach, 'N', $sort_seq);
                            //IF INSERT FAILS ROLLBACK
                        if(!$insertIdtRecord){
                            sqlsrv_rollback($conn);
                            //SHOW ERROR
                            $dataToSendBack = array("error" => "rollback-y-insert-newprocess");
                            echo json_encode($dataToSendBack);
                            exit;
                        }else{//ELSE COMMIT BECAUSE WE ARE DONE AND SEND BACK COMPLETED MESSAGE;
                            sqlsrv_commit($conn);
                            
                            $dataToSendBack = array("msg" => "data-updated");
                            echo json_encode($dataToSendBack);
                        }
                        
                        
                    }else{
                        sqlsrv_rollback($conn);
                        
                        $dataToSendBack = array("error" => "rollback-y-prev-update");
                        echo json_encode($dataToSendBack);
                        exit;
                    }
                }  
        }
        
    }else{
        
        //ELSE NO ROWS HAVE BEEN SET TO A COMPLETED PROCESS STATE, SO WE NEED TO GET THE MAX VALUE OF M06 BECAUSE THIS IS MORE THAN LIKELY WHAT IT IS 
        
         $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                SUM(idt_rec.qty) AS qty,
                                idt_rec.done_flg AS dflg
                        FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                        WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'N'
                        GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                        ORDER by idt_rec.sort_seq desc;";
        
        $res = sqlsrv_query($conn, $checkqtysql);
        
        if($res){//IF WE HAVE RECORDS NOT COMPLETED ON THE IDT
            //GET THE LAST KNOWN PROCESS AND QTY TO STORE
            
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            
            $lastKnownGoodProcessFromNoSide = $row['mcode'];
            $lastKnownGoodQtyFromNoSide = $row['qty'];
            
            //CHECK TO SEE IF IT IS THE SAME PROCESS
            if($mcode == $lastKnownGoodProcessFromNoSide){//IF SO THEN JUST INSERT RECORD
                
                $doneflg = 'N';
                
                 // BEGIN TRANSACTION
                            if ( sqlsrv_begin_transaction( $conn ) === false ) {
                                 $dataToSendBack = array("error" => "error-transaction");
                                echo json_encode($dataToSendBack);
                            }

                        //CALL THE NEXT IDR FUNCTION

                        $newIdtRecNum = getNextIDTRecNum($conn);

                        if(!$newIdtRecNum){
                            sqlsrv_rollback($conn);
                            
                            $dataToSendBack = array("error" => "rollback-n-nextnum");
                            echo json_encode($dataToSendBack);
                            exit;
                        }

                        $insertIdtRecord = createNewIDTRec($conn, $newIdtRecNum, $idtnum, $mcode, $user, $qtyToAdd,$empshift, $mach, $doneflg, $sort_seq);

                        if(!$insertIdtRecord){
                            sqlsrv_rollback($conn);
                            //SHOW ERROR
                            $dataToSendBack = array("error" => "rollback-n-insert");
                            echo json_encode($dataToSendBack);
                            exit;
                        }else{
                            sqlsrv_commit($conn);
                            $dataToSendBack = array("msg" => "data-updated");
                            echo json_encode($dataToSendBack);
                        }
                
            }else{//ELSE
            //UPDATE LAST PROCESS AND INSERT NEW RECORD SO WE CAN CATCH IT ON THE YES SIDE 
                 //CHECK TO SEE IF LAST KNOWN QTY IS EQUAL TO OR LESS THAN QTY TO ADD IF SO EXIT
                    
                    if($lastKnownGoodQtyFromNoSide < $qtyToAdd){
                        $dataToSendBack = array("error" => "error-qty-to-great");
                        echo json_encode($dataToSendBack);
                        exit;
                    }
                
                 //IF MCODE ISN'T EQUAL TO LAST KNOWN UNFINISHED CODE THEN WE NEED TO SET THE DONE FLAG TO "Y"
                    //SO WE CAN UPDATE THE LAST PROCESS TO FINISHED
                   
                    $doneflg = 'Y'; 
                    
                    // BEGIN TRANSACTION
                            if ( sqlsrv_begin_transaction( $conn ) === false ) {
                                 die( print_r( sqlsrv_errors(), true ));
                            }
                        
                        //GET ALL PRIMARY IDS FROM THE IDT RECORD TABLE THAT HAVE A DONE_FLG SET TO "N"
                    
                    $idrSetToNo = "SELECT idt_rec.idt_rec_id as idtrecord,
                                                            idt_rec.sort_seq
                                                            FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                                                            WHERE idt.idt_id = '" . $idtnum . "' AND done_flg = 'N'
                                                            ORDER by idt_rec.sort_seq desc;";
                    
                    $res = sqlsrv_query($conn, $idrSetToNo);
                    //CHECK TO SEE IF WE RETURNED ANYTHING TO UPDATE WITH
                    if($res){//WE HAVE RECORDS TO UPDATE
                        //LOOP THROUGH THE RECORDS AND UPDATE THEM TO DONE AND IF IT FAILS ROLLBACK 
                        
                        $idrRecArray = array();
                        
                            while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                              
                                array_push($idrRecArray, $row['idtrecord']);                                
                            }
                        
                        foreach($idrRecArray as $record){
                            
                            $updateIdtRec = "UPDATE idt_rec SET done_flg = '" . $doneflg . "' WHERE idt_rec_id = '" . $record . "';";
                            
                            $res = sqlsrv_query($conn, $updateIdtRec);
                            
                           if(!$res){
                               sqlsrv_rollback();
                               
                               $dataToSendBack = array("error" => "rollback-n-prev-update");
                                echo json_encode($dataToSendBack);
                               exit;
                           }
                            
                        }                        
                        //IF NOT FAILED SO FAR CALL FOR OUR INSERT
                        
                        //CALL THE NEXT IDR FUNCTION

                        $newIdtRecNum = getNextIDTRecNum($conn);
                            //IF WE DIDNT GET THE NEXT NUMBER TO INSERT ROLLBACK
                        if(!$newIdtRecNum){
                            sqlsrv_rollback($conn);
                            
                            $dataToSendBack = array("error" => "rollback-n-nextnum-newproc");
                            echo json_encode($dataToSendBack);
                            exit;
                        }
                        //ELSE INSERT THE NEW RECORD WITH THE DONE FLAG SET TO "N"
                        $insertIdtRecord = createNewIDTRec($conn, $newIdtRecNum, $idtnum, $mcode, $user, $qtyToAdd,$empshift, $mach, 'N', $sort_seq);
                            //IF INSERT FAILS ROLLBACK
                        if(!$insertIdtRecord){
                            sqlsrv_rollback($conn);
                            
                            $dataToSendBack = array("error" => "rollback-n-insert-newproc");
                            echo json_encode($dataToSendBack);
                            exit;
                        }else{//ELSE COMMIT BECAUSE WE ARE DONE AND SEND BACK COMPLETED MESSAGE;
                            sqlsrv_commit($conn);
                            
                            $dataToSendBack = array("msg" => "data-updated");
                            echo json_encode($dataToSendBack);
                        }
                        
                        
                    }else{
                        sqlsrv_rollback($conn);
                        
                        $dataToSendBack = array("error" => "rollback-n-update-prev");
                        echo json_encode($dataToSendBack);
                        exit;
                    }
                
                }
            
            }
    }
}else{
    //NO CONNECTION SHOW ERROR
    $dataToSendBack = array("error" => "error-db");
    echo json_encode($dataToSendBack);
}
//CLOSE CONNECTION
sqlsrv_close($conn);
?>