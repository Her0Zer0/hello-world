<?php

/*
*
*   TODO 
*       SET THE ERRORS FOR THE MERGE IDT SCREEN AND CLEAN UP THE HTML
*           SEE IF WE CAN SEND THE USER TO VIEW IDT ON SUCCESS
*           FURTHER TEST THE MERGE OF IDTS
*           
*
*
*/
include"../connect.php";

include"../functions.php";

if($conn){
    //GET VARIABLES
    
    $idtToMerge = cleanString(strtoupper($_POST['fromidt']));//this record will not be available after this till it is pulled for a report
    $idtToAccept = cleanString(strtoupper($_POST['toidt']));//this will be the only visible idt after this
    $dataToSendBack = array();
    
    //SET TO_IDT COLUMN TO EQUAL IDTTOACCEPT ON IDT RECORD THAT IS MERGED
    

    
    //INSERT RECORDS INTO MERGED IDT
    
    $sql = "SELECT prtnum, lotnum FROM idt WHERE idt_id = '" . $idtToMerge . "' AND to_idt IS NULL;";
        
        $res = sqlsrv_query($conn, $sql);
        $num_rows = sqlsrv_has_rows($res);
   
    if($num_rows > 0){
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        $partToMerge = $row['prtnum'];
        $lotToMerge = $row['lotnum'];
        
    }else{
        
        $sql="SELECT to_idt FROM idt WHERE idt_id = '" . $idtToMerge . "';";
        
            $mergeRes = sqlsrv_query($conn, $sql);
        
        if($mergeRes){
            $row = sqlsrv_fetch_array($mergeRes, SQLSRV_FETCH_ASSOC);
                $mergedWith = $row['to_idt'];
        }else{
            $mergedWith = 'another IDT';
        }
        
        echo "<div class='alert alert-warning'><p>Sorry, unable to merge " . $idtToMerge . " because it has already been merged with " . $mergedWith . ".</p></div>";
        exit;
        
        
    }
    //GET THE IDT INFO TO COMPARE.
    $sql = "SELECT prtnum, lotnum FROM idt WHERE idt_id = '" . $idtToAccept . "' AND to_idt IS NULL;";
        
        $res = sqlsrv_query($conn, $sql);
    $num_rows = sqlsrv_has_rows($res);
    
    if($num_rows > 0){
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        $partToAccept = $row['prtnum'];
        $lotToAccept = $row['lotnum'];
        
    }else{
        
        $sql="SELECT to_idt FROM idt WHERE idt_id = '" . $idtToAccept . "';";
        
            $acceptRes = sqlsrv_query($conn, $sql);
        
        if($acceptRes){
            $row = sqlsrv_fetch_array($acceptRes, SQLSRV_FETCH_ASSOC);
                $acceptRes = $row['to_idt'];
        }else{
            $acceptRes = 'another IDT';
        }
        
        echo "<div class='alert alert-warning'><p>Sorry, unable to merge " . $idtToAccept . " because it has already been merged with " . $acceptRes . ".</p></div>";
        exit;
    }
    
    //CHECK TO SEE IF THEY ARE THE SAME LOT AND PART NUMBER
    
//    if($partToAccept == $partToMerge && $lotToAccept == $lotToMerge){
//        echo "Part number and Lot number has to match to merger idts.";
//        exit;
//    }
    
    if($partToAccept != $partToMerge && $lotToAccept != $lotToMerge){
        echo    "<div class='alert alert-warning'><p>Part numbers and Lot numbers have to match in order to merge IDT's.</p>
                    <br/>
                    <table class='table'>
                        <tr><th>IDT Number</th><th>Part Number</th><th>Lot Number</th></tr>
                        <tr><td>" . $idtToMerge . "</td><td>" . $partToMerge . "</td><td>" . $lotToMerge . "</td></tr>
                        <tr><td>" . $idtToAccept . "</td><td>" . $partToAccept . "</td><td>" . $lotToAccept . "</td></tr>
                    </table>
                </div>";
        exit;
        
    }
      /* Begin the transaction. */
        if ( sqlsrv_begin_transaction( $conn ) === false ) {
             echo    "<div class='alert alert-warning'><p>There was a problem with this transaction. Please contact your administrator.</p></div>". var_dump(sqlsrv_errors());
            exit;
        }   
    //GET THE RECORDS TO INSERT
    $sql = "SELECT  idt_rec.idt_id AS idtnum,
            idt_rec.mcode AS mcode,
            idt_rec.usr_id AS empnum,
            idt_rec.qty AS qty,
            idt_rec.usr_shift AS empshift,
            idt_rec.machine AS mach,
            idt_rec.done_flg AS doneflg,
            idt_rec.created_date AS cdate, 
            idt_rec.sort_seq AS sort_seq 
    FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
    WHERE idt_rec.idt_id = '" . $idtToMerge . "';";
    
    $res = sqlsrv_query($conn, $sql);
    
    $num_rows = sqlsrv_has_rows($res);


    
    if($num_rows > 0){
                    
        $runNum = 0;
        $insertNum = 0;
        $breakNum = 0; 
       
        
        while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
            
            $runNum++;
            //GENERATE THE NEXT IDT RECORD NUMBER EVERY LOOP FOR INSERT
            $newIdtRec = getNextIDTRecNum($conn);
                //CHECK TO MAKE SURE WE AREN'T INSERTING NULL OR EMPTY STRINGS INTO TABLES
                if($row['mcode'] == '' || $row['mcode'] == NULL){ $breakNum++;} 
                if($row['empnum'] == '' || $row['empnum'] == NULL){ $breakNum++;} 
                if($row['qty'] =='' || $row['qty'] ==NULL){ $breakNum++;} 
                if($row['empshift'] == '' || $row['empshift'] == NULL){ $breakNum++;} 
                if($row['mach'] == ''|| $row['mach'] == NULL){ $breakNum++;} 
                if($row['doneflg'] == '' || $row['doneflg'] == NULL){ $breakNum++;}  
                if($row['cdate'] == '' || $row['cdate'] == NULL){ $breakNum++;} 
                if($row['sort_seq'] == '' || $row['sort_seq'] == NULL){ $breakNum++;} 
                if($breakNum > 0){
                
                echo    "<div class='alert alert-warning'><p>There was a problem while updating the information. Please contact your administrator.</p></div>";
                sqlsrv_rollback($conn);
                exit;
            }
            
            $mergeInsert = "INSERT INTO idt_rec(idt_rec_id, idt_id, mcode, usr_id, qty, usr_shift, machine, done_flg,created_date, sort_seq)
                                    VALUES('" . $newIdtRec . "',
                                        '" . $idtToAccept . "',
                                        '" . $row['mcode'] . "',
                                        '" . $row['empnum']. "',
                                        '" . $row['qty'] . "',
                                        '" . $row['empshift'] . "',
                                        '" . $row['mach'] . "',
                                        '" . $row['doneflg'] . "',
                                        '" . $row['cdate'] . "',
                                        '" . $row['sort_seq'] . "'
                                        );";
                        
                $insertRes = sqlsrv_query($conn, $mergeInsert);
            
            
            if($newIdtRec && $insertRes){
                $insertNum++;
            }
            
        }
        
        //IF OUR NUMBER OF LOOPS AND INSERTS MATCH CONTINUE WITH THE UPDATE
        if($runNum == $insertNum){
                           $sql = "UPDATE idt SET [to_idt] = '" . $idtToAccept . "' WHERE idt_id = '" . $idtToMerge . "';";
        
                        $res = sqlsrv_query($conn, $sql);
                            if($res){
                                sqlsrv_commit($conn);
                                echo "<div class='alert alert-success'><p>" . $idtToMerge . " has been successfully merged with " . $idtToAccept . ".</p></div>";
                    
                            }else{
                                echo    "<div class='alert alert-warning'><p>There was a problem while updating the information. Please contact your administrator.</p></div>";
                                sqlsrv_rollback($conn);
                                exit;
                            }
                    }else{
                        echo    "<div class='alert alert-warning'><p>There was a problem while updating the information. Please contact your administrator.</p></div>";
                                sqlsrv_rollback($conn);
                                exit;
        }
    }else{
        echo  "<div class='alert alert-warning'><p>There is a problem with " . $idtToMerge . ", please check it in the View IDT screen to confirm that it has records to merge.  If so please contact your administrator to let him know.</p></div>";
        exit;
    }
    
    
    
}else{
    //NO CONNECTION
    echo  "<div class='alert alert-warning'><p>Looks like we are having trouble connecting to the database.  If this problem persists please let your local administrator know.</p></div>";
        exit;
}

sqlsrv_close($conn);
?>