<?php
//GET CONNECTED
include"../connect.php";
//GET FUNCTIONS
include"../functions.php";
//CHECK IF CONNECTION SUCCESSFUL
if($conn){ //IF SUCCESS, CONTINUE
//1). STORE OUR VARIABLES
    
    $idt_id;    //Idt label number and primary key to idt table
    $idt_rec_id;    //Idt record primary key for idt_rec table
    $prtnum = $_POST["prtnum"]; // Part number
    $lotnum = $_POST["lotnum"]; //Lot number of product
    $mcode = getMcode($_POST["currentOperation"]);   //Process code
    $empnum = $_POST["empnum"];    //Employee number
    $usrshift = $_POST['empShift'];
    $qty = $_POST["qty"];   //Quantity
    $mach = $_POST['mach'];
    $arewedone = $_POST['arewedone'];   //done_flg = "Y" | "N"
    $sortseq = getProcessSeq($_POST["currentOperation"]);  //Sort sequence of our rows
    $fromidt;   //variable to store old idt number if split occurs
    
    
    //START OUR TRANSACTION 
    if(!sqlsrv_begin_transaction($conn)){
        echo json_encode($idtArray = array("error" => "error-transaction"));
        exit;
    }
//2). GET THE NEXT NUMBER FOR OUR IDT TABLE
    $idt_id = getNextIDTNum($conn);
//3). GET THE NEXT NUMBER FOR OUR IDT RECORD TABLE
    $idt_rec_id = getNextIDTRecNum($conn);
    
    if($idt_id && $idt_rec_id){
//4). START THE TRANSACTION WITH CREAT NEW IDT FUNCTION FROM FUNCTIONS.PHP
        $createIdt = createNewIDT($conn,$idt_id,$prtnum,$lotnum);
//5). START THE TRANSACTION WITH CREATE NEW IDT RECORD FUNCTION FROM FUNCTIONS.PHP
        $createIdtRec = createNewIDTRec($conn, $idt_rec_id, $idt_id, $mcode, $empnum, $qty, $usrshift, $mach, $arewedone, $sortseq);
        
//6). CHECK IF BOTH ARE SUCCESSFULL
        
        if($createIdt && $createIdtRec){
            //COMMIT CHANGES
            sqlsrv_commit($conn);
            //IF SUCCESS SEND BACK IDT NUMBER
            echo json_encode($idtArray = array("idtnum" => $idt_id));
        }else{
            //ROLLBACK
            sqlsrv_rollback($conn);
            $myerrors ="Create Idt " . print_r($createIdt,true) . " " . "Create Idt Rec " . print_r($createIdtRec,true);
            //IF ERROR, ROLLBACK TRANSACTIONS AND SEND ERROR THEN EXIT SCRIPT
            echo json_encode($idtArray = array("error" => "error-rollback", "myerror"=> $myerrors));
            exit;
        }
       
    }
    
} else {//IF ERROR, SEND ERROR AND EXIT
    echo json_encode($idtArray = array("error" => "error-db"));
    exit;
}     
//CLOSE CONNECTION
sqlsrv_close($conn);
?>