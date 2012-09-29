<?php
 /** The main function is getSQL which is at the end of the files. 
   * At the top of the file there are some methods to encode the value etc to avoid SQL injection and so on.
   * In the middle you will find the functions that decode the elementary filter in a SQL condition
   *revision r112 by dandfra on Mar 06, 2008 
   */
 /*Utility function start*/
   function isDecimalNumber($n) {
     return (string)(float)$n === (string)$n;
   }
   
   function calculateValue($value){
     if(count($value)===0){
       return("");
     } else {
       return(str_replace("'","''",$value[0]->value));
     }
   }
   function escapeForLike($value){
     $value=str_replace("\\","\\\\",$value);
     $value=str_replace("_","\\_",$value);
     $value=str_replace("%","\\%",$value);
     return($value);
   }
 /*Utility function end*/
 /*Decoding functions start. They have the field and the value as parameters, and returns the SQL condition
   */
   function string_equals($field,$value){
     $val=calculateValue($value);
     if($val===""){//in Oracle empty string and null value are the same thing
       return($field." is null");
     } else {
       return($field."='".$val."'");
     } 
   }
   
   function string_different($field,$value){
     $val=calculateValue($value);
     if($val===""){//in Oracle empty string and null value are the same thing
       return($field."is not null");
     } else {
       return($field."<>'".$val."'");
     } 
   }
   
   function string_list($field,$value){
     $values=Array();
     $toReturn="";
     $nullValue=false;
     foreach($value as $val){
       $val=calculateValue(Array($val));
       if($val==="" && !$nullValue){
         $toReturn="(".$field." is null";
         $nullValue=true;
       } else {
         $values[]=$val;
       }
     }
     if(count($values)>0){
       if(strlen($toReturn)>0){
         $toReturn.=" or";
       }
       $toReturn.=" ".$field." in ('".implode("','",$values)."')";
     }
     if($nullValue){
       $toReturn.=")";
     }
     return($toReturn);
   }
   
   function string_not_in_list($field,$value){
     return("not ".string_list($field,$value));
   }
   
   function string_starts_with($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return ("1=1");
     }
     $val=escapeForLike($val);
     return($field." like '".$val."%' ");
   }
   
   function string_ends_with($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return ("1=1");
     }
     $val=escapeForLike($val);
     return($field." like '%".$val."' ");
   }
   
   function string_contains($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return ("1=1");
     }
     $val=escapeForLike($val);
     return($field." like '%".$val."%' ");
   }
   
   function string_doesnt_contains($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return ("1=1");
     }
     $val=escapeForLike($val);
     return($field." not like '%".$val."%' ");
   }
   
   function number_equals($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     }elseif(isDecimalNumber($val)){
       return($field."=".$val);
     }else{
       return("1<>1");//it's not a number, always false
     }
   }
   
   function number_differents($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is not null");
     }elseif(isDecimalNumber($val)){
       return($field."<>".$val);
     }else{
       return("1=1");//it's not a number, always true
     }
   }
   
   function number_greater($field,$value){
     $val=calculateValue($value);
     if(isDecimalNumber($val)){
       return($field.">".$val);
     }else{
       return("1<>1");//it's not a number, always false
     }
   }
   
   function number_less_or_equal($field,$value){
     $val=calculateValue($value);
     if(isDecimalNumber($val)){
       return($field."<=".$val);
     }else{
       return("1<>1");//it's not a number, always false
     }
   }
   
   function number_less($field,$value){
     $val=calculateValue($value);
     if(isDecimalNumber($val)){
       return($field."<".$val);
     }else{
       return("1<>1");//it's not a number, always false
     }
   }
   
   function number_greater_or_equal($field,$value){
     $val=calculateValue($value);
     if(isDecimalNumber($val)){
       return($field.">=".$val);
     }else{
       return("1<>1");//it's not a number, always false
     }
   }
   
   function number_range($field,$value){
     if(count($value)!==2){
       return("1<>1"); //if I don't have 2 numbers always return false
     }
     
     $valFrom=Array(0=>$value[0]);
     $valTo=Array(0=>$value[1]);
     
     return("(".number_greater_or_equal($field,$valFrom). " and ".number_less_or_equal($field,$valTo).")");
   }
   
   function date_equal($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     } else {
       return($field."=STR_TO_DATE('".$val."','%Y-%m-%d  %H:%i:%s')");
     }
   }
   
   function date_greater($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     } else {
       return($field.">STR_TO_DATE('".$val."','%Y-%m-%d  %H:%i:%s')");
     }
   }
   
   function date_less_or_equal($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     } else {
       return($field."<=STR_TO_DATE('".$val."','%Y-%m-%d  %H:%i:%s')");
     }
   }
   
   function date_less($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     } else {
       return($field."<STR_TO_DATE('".$val."','%Y-%m-%d  %H:%i:%s')");
     }
   }
   
   function date_greater_or_equal($field,$value){
     $val=calculateValue($value);
     if($val===""){
       return($field." is null");
     } else {
       return($field.">=STR_TO_DATE('".$val."','%Y-%m-%d  %H:%i:%s')");
     }
   }
   
   function date_range($field,$value){
     if(count($value)!==2){
       return("1<>1"); //if I don't have 2 dates always return false
     }
     
     $valFrom=Array(0=>$value[0]);
     $valTo=Array(0=>$value[1]);
     
     return("(".date_greater_or_equal($field,$valFrom). " and ".date_less_or_equal($field,$valTo).")");
   }
   function date_period($field,$value){
     $val=calculateValue($value);
     if($val==='LAST_YEAR'){
       $val="DATE_ADD(NOW(), INTERVAL -1 year)";
     }elseif ($val==='LAST_MONTH'){
       $val="DATE_ADD(NOW(), INTERVAL -1 month)";
     }elseif ($val==='LAST_WEEK'){
       $val="DATE_ADD(NOW(), INTERVAL -7 day)";
     }elseif ($val==='LAST_DAY'){
       $val="DATE_ADD(NOW(), INTERVAL -1 day)";
     }elseif ($val==='LAST_HOUR'){
       $val="DATE_ADD(NOW(), INTERVAL -1 hour)";
     }elseif ($val==='LAST_QUARTER'){
       $val="DATE_ADD(NOW(), INTERVAL -3 month)";
     } else {
       return("1<>1");
     }
     return($field." > ".$val);
   }
   /*Decoding functions end.*/
   /** This one is used to associate an operator id to the function that resolves the elementary filter in a SQL filter.
     * If you have custom operators just add them to the array with the function name
     */
   $mapping=Array(      
       'NUMBER_EQUAL' => 'number_equals',
       'NUMBER_NOT_EQUAL'=> 'number_differents',
       'NUMBER_GREATER'=> 'number_greater',
       'NUMBER_GREATER_OR_EQUAL'=> 'number_greater_or_equal',
       'NUMBER_LESS'=> 'number_less',
       'NUMBER_LESS_OR_EQUAL'=> 'number_less_or_equal',
       'NUMBER_RANGE'=> 'number_range',
       'STRING_EQUAL'=> 'string_equals',
       'STRING_EQUALS'=> 'string_equals', //legacy, to remove once 0.2.0 will be out
       'STRING_DIFFERENT'=> 'string_different',
       'STRING_CONTAINS'=> 'string_contains',
       'STRING_DOESNT_CONTAIN'=> 'string_doesnt_contains',
       'STRING_STARTS_WITH'=> 'string_starts_with',
       'STRING_ENDS_WITH'=> 'string_ends_with',
       'STRING_LIST'=> 'string_list',
       'STRING_NOT_IN_LIST'=> 'string_not_in_list',
       'DATE_EQUAL'=>'date_equal',
       'DATE_GREATER'=>'date_greater',
       'DATE_GREATER_OR_EQUAL'=>'date_greater_or_equal',
       'DATE_LESS'=>'date_less',
       'DATE_LESS_OR_EQUAL'=>'date_less_or_equal',
       'DATE_RANGE'=>'date_range',
       'DATE_PERIOD'=>'date_period'
   );
   
   /** Core function that you should call to obtain the SQL filter 
     */
   function getSQL($filterObj){
     global $mapping;
     if($filterObj==null)
       return("1=1");
     if(isset($filterObj->operatorId)){
       return($mapping[$filterObj->operatorId]($filterObj->fieldId,$filterObj->values));
     } else {
       $leftSql=getSQL($filterObj->left);
       $rightSql=getSQL($filterObj->right);
       return("(".$leftSql." ".$filterObj->logicalOperator." ".$rightSql.")");
     }
   }
   
 ?>
