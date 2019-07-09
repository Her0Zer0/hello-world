<?php

include"../connect.php";
include"../functions.php";

if($conn){//IF CONNECTION TRUE
    
    $idtnum = cleanString($_POST['idtnum']);
    
    $idtsql ="SELECT idt_id AS idtnum, 
                        prtnum AS prtnum, 
                        lotnum, 
                        SUBSTRING(CONVERT(VARCHAR,created_date),0,12) AS fifo 
                        FROM idt WHERE idt_id = '" . $idtnum . "' AND to_idt IS NULL;";
    //GET IDT RECORD
        $idtres = sqlsrv_query($conn, $idtsql);
    $num_of_rows = sqlsrv_has_rows($idtres);
    if($num_of_rows < 1){
        $dataToSendBack = array("error" => "error-get-idt");
        echo json_encode($dataToSendBack);
        exit;
        
    }else{
        
        $idtrow = sqlsrv_fetch_array($idtres, SQLSRV_FETCH_ASSOC);
        
        //GET LAST KNOWN GOOD QTY
        
        $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                    SUM(idt_rec.qty) AS qty,
                                    idt_rec.done_flg AS dflg
                            FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                            WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'Y'
                            GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                            ORDER by idt_rec.sort_seq desc;";
        
        $res = sqlsrv_query($conn, $checkqtysql);
        $num_of_rows = sqlsrv_has_rows($res);
        if ($num_of_rows>0){
            
            $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
            $storedQty = $row['qty'];
            
            
        }else{
            //TRY TO SEE IF WE HAVE ANY TO RETURN THAT IS NOT DONE
            
            $checkqtysql = "SELECT TOP 1 idt_rec.mcode AS mcode,        
                                        SUM(idt_rec.qty) AS qty,
                                        idt_rec.done_flg AS dflg
                                FROM idt INNER JOIN idt_rec ON idt.idt_id = idt_rec.idt_id 
                                WHERE idt.idt_id = '" . $idtnum . "' AND idt_rec.done_flg = 'N'
                                GROUP BY idt_rec.mcode,idt_rec.sort_seq,idt_rec.done_flg
                                ORDER by idt_rec.sort_seq desc;";
            
           $res = sqlsrv_query($conn, $checkqtysql);
            
            if($res){
                $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
                $storedQty = $row['qty'];
            }
        }
        
    
        
        $dataToSendBack = array("idtnum" => $idtrow['idtnum'], "prtnum" => $idtrow['prtnum'], "lotnum" => $idtrow['lotnum'],"fifo" => $idtrow['fifo'],"lastKnownGoodQty" => $storedQty);
        
        echo json_encode($dataToSendBack);
    }

    
}else{//IF NO CONNECTION SHOW ERROR
    $dataToSendBack = array("error" => "error-db");
}

sqlsrv_close($conn);
?>