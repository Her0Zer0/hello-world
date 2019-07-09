<?php

//GET CONNECTED
include"../connect.php";
//GET FUNCTIONAL
include"../functions.php";
//CHECK FOR CONNECTIONS
if($conn){
    
    $idtnum = cleanString($_POST['idtnum']);
    $qtyToSplit = cleanString($_POST['qty']);
    $mcodeArray = array();
    $idtRecArray = array();
    
    //GET THE IDT INFORMATION
    
    $sql = "SELECT * FROM idt WHERE idt_id = '" . $idtnum . "';";
    
    $res = sqlsrv_query($conn, $sql);
    
    
    if($res){
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        
        //GET OUR PART NUMBER AND LOTNUMBER FOR NEXT IDT
        
        $prtnum = $row['prtnum'];
        $lotnum = $row['lotnum'];
        
        //GET THE MCODES TO SPLIT
    
        $sql = "SELECT DISTINCT mcode FROM idt_rec WHERE idt_id = '" . $idtnum . "';";
        
        $res = sqlsrv_query($conn, $sql);
        
        if($res){
            
            while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                array_push($mcodeArray, $row['mcode']);
            }
        }
        
        //GET THE LAST GREATEST QTY TO SPLIT FROM
        
        $sql = "SELECT top 1 idt_rec.mcode AS mcode,        
                            SUM(idt_rec.qty) AS qty,
                            idt_rec.done_flg AS dflg        
                    FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                    WHERE idt.idt_id = '" . $idtnum . "'
                    GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                    ORDER by idt_rec.sort_seq desc;";
        
        $res = sqlsrv_query($conn, $sql);
        
        if($res){
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            //STORE OUR LAST GREATEST QTY
            
            $lastGreatestQtyStored = $row['qty'];
            
        }
        
        //CHECK TO SEE IF THE MCODE ARRAY WAS FILLED PROPERLY AND IF THE LAST KNOWN GREATEST QTY 
        //IS GREATER THAN THE QTY TO SPLIT 
        
        if($lastGreatestQtyStored > $qtyToSplit && sizeof($mcodeArray) > 0){
            
            
            foreach($mcodeArray as $code){
                 $sql = "EXEC dbo.getIdtSplitData " . $qtyToSplit . "," . $idtnum . "," . $code. ";" ;

               $res = sqlsrv_query($conn, $sql);
            
            if($res){
                
                while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                    array_push($idtRecArray,array(  "idtrecord" => $row['idtrecord'],
                                                "mcode" => $row['mcode'],
                                                "qtytoinsert" => $row['qty_to_split'],
                                                "qtytoupdate" => $row['new_qty'],
                                                "usr_id" => $row['usr_id'],
                                                "usr_shift" => $row['usr_shift'],
                                                "mach" => $row['machine'],
                                                "doneflg" => $row['dflg'],
                                                "sort_seq" => $row['sort_seq'],
                                                "cdate" => $row['created_date']
                                             ));
                }//END WHILE LOOP
                
            }
               
            }//END FOREACH LOOP
            //BEGIN INSERTS AND UPDATES
            //CREATE THE NEW IDT AND ITS RECORDS
            
            /* Begin the transaction. */
            if ( sqlsrv_begin_transaction( $conn ) === false ) {
                 $dataToSendBack = array("msg" => "error-split");
                 echo json_encode($dataToSendBack);
                 exit;
            }
            
            //GET THE NEXT IDT NUMBER
            $newIdtNum = getNextIDTNum($conn);
//            
            $insertIdt = "INSERT INTO idt(idt_id, prtnum, lotnum, created_date, from_idt)
                                VALUES('" . $newIdtNum . "','" . $prtnum . "','" . $lotnum . "',CURRENT_TIMESTAMP,'" . $idtnum . "')";
            $idtRes = sqlsrv_query($conn, $insertIdt);
            
            for($i = 0; $i < sizeof($idtRecArray); $i++){
             
                $nextIdtRecNum = getNextIDTRecNum($conn);            
            
                $insertIdtRec = "INSERT INTO idt_rec(idt_rec_id, idt_id, mcode, usr_id, qty, usr_shift, machine, done_flg,created_date, sort_seq)
                                    VALUES('" . $nextIdtRecNum . "',
                                        '" . $newIdtNum . "',
                                        '" . $idtRecArray[$i]['mcode'] . "',
                                        '" . $idtRecArray[$i]['usr_id']. "',
                                        '" . $idtRecArray[$i]['qtytoinsert'] . "',
                                        '" . $idtRecArray[$i]['usr_shift'] . "',
                                        '" . $idtRecArray[$i]['mach'] . "',
                                        '" . $idtRecArray[$i]['doneflg'] . "',
                                        '" . $idtRecArray[$i]['cdate'] . "',
                                        '" . $idtRecArray[$i]['sort_seq'] . "'
                                        );";
                //BEGIN THE UPDATES TO THE PREVIOUS RECORDS
                
                $updateSql = "UPDATE idt_rec SET qty = '" . $idtRecArray[$i]['qtytoupdate'] . "' WHERE idt_rec_id = '" . $idtRecArray[$i]['idtrecord'] . "';";
                
                $idtRecUpdateRes = sqlsrv_query($conn, $updateSql);
                
                $idtRecRes = sqlsrv_query($conn, $insertIdtRec);
                
            }//END FOREACH LOOP
           
            
            if($idtRes && $idtRecRes && $idtRecUpdateRes){
                sqlsrv_commit($conn);
                
                $dataToSendBack = array("msg" => $newIdtNum);
                echo json_encode($dataToSendBack);
                
                
            }else{
                sqlsrv_rollback($conn);
                $dataToSendBack = array("msg" => "error-split");
                echo json_encode($dataToSendBack);
                exit;
            }
            
            
            
        }else{
            $dataToSendBack = array("msg" => "error-data");
            echo json_encode($dataToSendBack);
            exit;
        }
        
        
    }//ELSE SOMETING HAPPENED

}else{
    $dataToSendBack = array("msg" => "error-db");
    echo json_encode($dataToSendBack);
    exit;
}

sqlsrv_close($conn);
?>