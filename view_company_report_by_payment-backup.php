<?php
//die('We are here'); hey
$prvcmp = select_single_record("company_profit_privileges","*"," company_id = '$_REQUEST[comp_name]'");
	$sp = explode(",",$prvcmp["privilegss"]);
	if(sizeof($sp) == 2)
	{
		$shows = $sp[0];
		$showm = $sp[1];
	}
	else
	{
		if($prvcmp["privilegss"] == "S")
		{
			$shows = $prvcmp["privilegss"];
		}
		if($prvcmp["privilegss"] == "M")
		{
			$showm = $prvcmp["privilegss"];
		}
	}
	
	function tpos($a)
	{
		return strlen(number_format($a,2));
	}


if(!empty($_REQUEST["save"]) && !empty($_POST))
{
	//var_dump($_POST);die();
	$jbsid 		= $_REQUEST["jbsid"];
	$comp_name 	= $_REQUEST["comp_name"];
	$rep_name 	= $_REQUEST["rep_name"];
	//var_dump($_POST,$_GET);die();

	$jdls = select_single_record(
									"job_master a,technician b",
										"a.Technician_ID,
										a.job_id,
										a.Day,
										a.Night,
										a.Sunday,
										Credit_Card_Rate,
										Check_Rate,
										Cash_Rate,
										b.Day as tDay,
										b.Night as tNight,
										b.Sunday as tSunday,
										b.Cash,
										b.Cheque,
										b.CreditCard",
									" a.company_id = '$comp_name' 
									AND a.job_id='$jbsid'
									AND a.company_id = b.company_id
									AND a.Technician_ID = b.Technician_ID
									"
								);
	
	
	
	$jday 			= $jdls["Day"];
	$jnight 		= $jdls["Night"];
	$jsunday 		= $jdls["Sunday"];
	$jcrdtf 		= $jdls["Credit_Card_Rate"];
	$jchkf 			= $jdls["Check_Rate"];
	$jcashf 		= $jdls["Cash_Rate"];
	
	$job_payment_method = $_REQUEST["Job_Payment_Method"]; 
	$job_close_time 	= $_REQUEST["Job_Close_Time"]; 
	$cashs 				= $_REQUEST["vcash"];
	$open_bill 			= $_REQUEST["vopen_bill"];
	//$open_bill 			= "0.00";
	$chks				= $_REQUEST["vChecks"];
	$crdt 				= $_REQUEST["vCredit"];
	
	$tech_prt 		= $_REQUEST["vTech_Part"];
	$comp_prt 		= $_REQUEST["vComp_Part"];
	$tot_amt 		= $_REQUEST["vamount"];

	$ccrdtf 		= $jdls["CreditCard"];
	$cchkf 			= $jdls["Cheque"];
	$ccashf 		= $jdls["Cash"];
	$tday 			= $jdls["tDay"];
	$tnight 		= $jdls["tNight"];
	$tsunday 		= $jdls["tSunday"];
	$tcash 			= $jdls["tCash"];
	$tchks 			= $jdls["tCheque"];
	
	if($job_close_time == "Day")
	{
		if($jday == 0) { $tech_pst = $tday; }else { $tech_pst = $jday; } 
	}
	elseif($job_close_time == "Night")
	{
		if($jnight == 0) { $tech_pst = $tnight; }else { $tech_pst = $jnight; } 
	}
	elseif($job_close_time == "Sunday")
	{
		if($jsunday == 0) { $tech_pst = $tsunday; }else { $tech_pst = $jsunday; } 
	} 

	
	
	//var_dump($tech_pst ,  $tday);die();
	if($jcashf <> 0) { $fees1 = ($cashs * $jcashf)/100;}else { $fees1 = ($cashs * $ccashf)/100;}
	if($jchkf <> 0) { $fees2 = ($chks * $jchkf)/100; }else { $fees2 = ($chks * $cchkf)/100;}
	if($jcrdtf <> 0) { $fees3 = ($crdt * $jcrdtf)/100; }else { $fees3 = ($crdt * $ccrdtf)/100;}
	if($jchkf <> 0) { $fees4 = ($open_bill * $jchkf)/100; }else { $fees4 = ($open_bill * $cchkf)/100;}
	
	$comp_due = $acrdt + $achks + $amt_rev_techs;
	//Technician Due
	$tech_due = $acash + $amt_given_techs;
	
	//Total Fee
	$tot_fees = $fees1 + $fees2 + $fees3 + $fees4;
	
	//Total Profit Calculation
	$tot_profit = ($tot_amt - $tot_fees - $tech_prt - $comp_prt);		
	//Technician Profit calculation 
	$tech_profit = ($tot_profit * ($tech_pst/100));
	//Company Profit calculation 
	$comp_profit = $tot_profit - $tech_profit;
	
	
	if($cashs !=0)
	{
		$balance = -($comp_profit+$tot_fees+$comp_prt);
		$netcash = $balance;
	}
	else
	{
		 $balance =0;
	}
	$update = false;
	if($crdt !=0 || $chks !=0 || $open_bill !=0)
	{
		if($_POST['collector'] == 'company')
		{
		 $balance += $tech_profit + $tech_prt;
		 $update = true;
		}
		elseif ($_POST['collector'] == 'technician')
		{
			//$balance += $tech_profit + $tech_prt;
			$balance -= $comp_profit + $comp_prt;
			$update = true;
		}
		
	}
	if($cashs !=0 && ($crdt !=0 || $chks !=0 || $open_bill !=0))
	{
		 $balance = $crdt + $chks + $open_bill + $netcash;
	}
	if($update)
	{
		update_table("job_master"," Cash='$cashs',Checks='$chks',Credit_Card='$crdt',open_bill='$open_bill',Balance_Due='$balance',Job_Close_Time='$job_close_time',Technician_Parts='$tech_prt',Company_Parts='$comp_prt',Total_Cost = '$tot_amt'"," job_id = '$jbsid'");
		update_table("company_report_job_detail","Cash='$cashs',Checks='$chks',Credit='$crdt',Comp_Profit='$comp_profit',Tech_Prafit='$tech_profit',Fees='$tot_fees',Shift='$job_close_time',Current_due='$balance',tot_amt='$tot_amt',Comp_Part='$comp_prt',Tech_Part='$tech_prt'","Report_id = '$rep_name' and job_id='$jbsid'"); 
		$rep_res = select_single_record("company_report_main","*"," Report_id='".$rep_name."'");
		$job_balance = $res['Current_due'];
		$old = new stdClass();
		$old->curent_due = $rep_res['Current_due'];
		$old->prev_balance = $rep_res['Prev_Bal'];
		
		$old->prev_rep_balance = $old->curent_due - $old->prev_balance;
		
		$sum_res = select_single_record("company_report_job_detail","SUM(`Current_due`) as total"," Report_id='$rep_name'");
		$sum = $sum_res['total'];
		
		$new = new stdClass();
		
		$new->curent_due = $old->prev_rep_balance + $sum;
		$new->prev_balance = $sum;
		update_table("company_report_main","Current_due = '$new->curent_due', Prev_Bal = '$new->prev_balance'" ," Report_id = '".$rep_res['Report_id']."'");
	}
	
}


if($_REQUEST["mail"] ==1)
{
  $compid	= $_REQUEST["comp_name"];
  $rep_name = $_REQUEST["rep_name"];
  $tech_id  = $_REQUEST["tech_id"];
  $jobid	= $_REQUEST["jb_ids"];
  
  $compname = select_single_record("company_master","company_name"," company_id = '$compid'");
  
  $comp_qrys = select_single_record("company_master","*","company_id = '$compid'");
  $rep_qrys = select_single_record("company_report_main","*"," Report_id = '$rep_name'");

  $company_name = $comp_qrys["company_name"];
  $rept_name = $rep_qrys["Report_name"];
  $tech_id = substr($tech_id,0,-1);
  $jb_ids = substr($jb_ids,0,-1);

  $jb_ids = explode("@",$jobid);
  $tchid = explode("@",$tech_id);

  	$rep_id = $rep_name; 
	$select_techs = select_multi_records("company_report_main as a,company_report_job_detail as b","*"," a.Report_id = b.Report_id AND b.Report_id = '$rep_id'"," Group By b.Technician_ID ");
	$get_dates = select_single_record("company_report_main","*"," Report_id = '$rep_id'");
	$rnames = $get_dates["Report_name"];
	$sel_comps = select_single_record("company_master","*","company_id = '$get_dates[Company_id]'");
	$company_names= $sel_comp["company_name"];
	
	// for top
	$select_tech = select_multi_records("company_report_main as a,company_report_job_detail as b","*"," a.Report_id = b.Report_id AND b.Report_id = '$rep_id'"," Group By b.Technician_ID ");
	$get_date = select_single_record("company_report_main","*"," Report_id = '$rep_id'");
	$rname   = $get_date["Report_name"];
	$rnotes1 = stripslashes($get_date["notes"]);
	$rnotes2 = $get_date["report_note2"];
	$pval    = $get_date["Prev_Bal"];
	$cid = $get_date["Company_id"];
	$prv_rep = select_single_record("company_report_main","*"," Report_id < '$rep_name' and Company_id='$cid'","order by Report_id DESC limit 0,1  ");
	$prv_repts = select_single_record("company_report_main","sum(Prev_Bal) as pbal"," Report_id <= '$rep_name' and Company_id='$cid'","order by Report_id DESC  ");
	$smrep =  select_single_record("pending_payment_det","sum(amt_rec_tec) as recs,sum(amt_giv_tec) as givs","comp_id = '$cid' and rep_id <='$rep_id'");
	
   $reportid =  $prv_rep["Report_id"];
   $balfrm = $prv_rep["Current_due"];
   $sel_comp = select_single_record("company_master","*","company_id = '$cid'");
   $company_name= $sel_comp["company_name"];
   
   $dls_qry = select_multi_records("pending_payment_det","*","comp_id = '$cid' and rep_id ='$reportid'");
   $tot_pp = totalrows("pending_payment_det","*","comp_id = '$cid' and rep_id <='$reportid'"," limit 0,1");
	while($pprows = mysql_fetch_array($dls_qry))
	{
		$tot_amt_rec += $pprows["amt_rec_tec"];
		$tot_amt_giv += $pprows["amt_giv_tec"];
	}
   $netbal = $tot_amt_rec - $tot_amt_giv;
   
  $prv_rep_tot = totalrows("company_report_main","*"," Report_id <= '$rep_id' and Company_id='$cid'");

   if($prv_rep_tot > 2)
   {
		$vals = $prv_rep["Current_due"];
		if($tot_pp > 0) { $vals = $vals - $netbal; } 
   }
   else
   {
		$vals = $prv_rep["Prev_Bal"];
   }
												  
   if(($netbal > 0 && $vals > 0) || ($netbal > 0 && $vals < 0))
   {
		$frmbal = $vals + $netbal;
   }
   else if(($netbal < 0 && $vals < 0) || ($netbal < 0 && $vals > 0))
   {
		$frmbal = $vals + $netbal;
   }
   else if($netbal == 0)
   {
		$frmbal = $vals;
   }
   
   $compname = select_single_record("company_master","company_name"," company_id = '$_REQUEST[comp_name]'");
   
	//To get report name												   
	if($prv_rep["Report_name"] <> "") { $prv_name = $prv_rep["Report_name"]; } else { $prv_name = "This is first report"; }
//To get Previous report balance												   
if($vals > 0) { $prvbal1 = number_format(abs($vals),2); } else { $prvbal1 = number_format(abs($vals),2); }

//To get payment Details	
//To get balance forward from report
$netbals = number_format(abs($netbal),2);
$balfrms = number_format(abs($frmbal),2);

$stot_bal = number_format(abs($pval),2); 
$grant_tot = $frmbal + $pval;
$gtot_bal = number_format(abs($grant_tot),2);


  if($vals > 0) { $cprvbal1 = '<font color="green">$'.$prvbal1.'</font>'; } else { $cprvbal1 = '<font color="red">$'.$prvbal1.'</font>';  }
  if($netbal > 0) { $cnetbals = '<font color="green">$'.$netbals.'</font>'; } else { $cnetbals = '<font color="red">$'.$netbals.'</font>';  }
  if($frmbal > 0) { $cbalfrms = '<font color="green">$'.$balfrms.'</font>'; } else { $cbalfrms = '<font color="red">$'.$balfrms.'</font>';  }
  if($pval > 0) { $cstot_bal = '<font color="green">$'.$stot_bal.'</font>'; } else { $cstot_bal = '<font color="red">$'.$stot_bal.'</font>';  }
  if($grant_tot > 0) { $cgtot_bal = '<font color="green">$'.$gtot_bal.'</font>'; } else { $cgtot_bal = '<font color="red">$'.$gtot_bal.'</font>';  }


  $content = '<table width="75%"  cellspacing="0" cellpadding="0" align="center">
  <tr >
    <td colspan="5" align="right">&nbsp;</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Company Name :&nbsp;</td>
    <td colspan="4" align="left" class="input">  '.$company_name.'</td>
  </tr>
    <tr >
    <td width="47%" align="right" class="message">Report Title :&nbsp;</td>
    <td colspan="4" align="left" class="input">  '.$rept_name.'</td>
  </tr>
   <tr >
    <td width="47%" align="right" class="message">Previous balance from report :&nbsp;</td>
    <td align="left" class="input">  '.$prv_name.'</td>
	<td width="38%" align="left">&nbsp;&nbsp;&nbsp;'.$cprvbal1.'</td>
	<td colspan="2" width="10%">&nbsp;</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Payment Made on report :&nbsp;</td>
    <td  align="left" class="input">  '.$prv_name.'</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cnetbals.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Balance forward from report :&nbsp;</td>
    <td  align="left" class="input">  '.$prv_name.'</td>
	<td colspan="3"><div align="left">&nbsp;&nbsp;&nbsp;'.$cbalfrms.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">New report Total :&nbsp;</td>
    <td  align="left">&nbsp;</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cstot_bal.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Grand total :&nbsp;</td>
    <td  align="left" class="input">&nbsp;</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cgtot_bal.'</div></td>
  </tr>  
  <tr class="data">
    <td colspan="5" align="right">&nbsp;</td>
  </tr>';

  for($j=0;$j<sizeof($tchid);$j++)
  {
	  $tech_id = $tchid[$j];
	  $tech_qry = select_single_record("technician","*","Technician_ID = '$tech_id' AND Active ='Y'");
	  $totrows1 = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.Technician_ID = '$tech_id' AND jm.Set_status_to='Completed'");
	 
	  $tech_names = $tech_qry["FirstName"].$tech_qry["LastName"];
	   if($totrows1 > 0)
		 {
	    $content .= '<tr>
    <td  align="center" class="message"><div align="right">Technician Name </div></td>
	<td colspan="4"  align="left"> &nbsp;&nbsp;'.$tech_names.'&nbsp;</td>
		  </tr>
		  <tr>
			<td colspan="5"  align="center" class="input">&nbsp;</td>
		  </tr>
		  <tr>
		 <td colspan="5"  align="center" class="input">';
		 if($showm!=""){$cpc="Comp Profit";}else{$cpc="";}
		$content .= ' <table class="tablbrd"  cellspacing="0" cellpadding="0"  align="center">
		 <tr class="message">
          <td >Job ID</td>
          <td >Date</td>
		  <td >Address</td>
          <td >Total Amount </td>
		  <td >Comp Part</td>
		  <td >Tech Part</td>
          <td >Cash</td>
          <td>Check </td>
          <td>Credit</td>
		  <td>Fees</td>
		  <td>Tech Profit</td>';
		  if($cpc <> ""){
          $content .= '<td>'.$cpc.'&nbsp;</td>';
		  }
		  
          $content .= '<td >Balance</td>
		  <td >Net Balance</td>
        </tr>';
		$tot_net_bal=0;
	  for($i=0;$i<sizeof($jb_ids);$i++)
	  {
		 $jobids = $jb_ids[$i];
		 
		 $tech_qry = select_single_record("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobids' AND jm.Technician_ID = '$tech_id'");
		 $cmp_rep_qry = select_single_record("company_report_job_detail","*"," Report_id='$rep_name' AND job_id='$jobids'");
	
		 $totrows = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobids' AND jm.Technician_ID = '$tech_id'");
		 
		 $cus_names = $tech_qry["FirstName"]." ".$tech_qry["LastName"];
		 $tot_amt = $cmp_rep_qry["tot_amt"];
 		 $address = $tech_qry["Address"];
		 $tech_part = $cmp_rep_qry["Tech_Part"];
		 $comp_part = $cmp_rep_qry["Comp_Part"];
		 $jcash	= $cmp_rep_qry["Cash"];
		 $jcheck =  $cmp_rep_qry["Checks"];
		 $jcredit = $cmp_rep_qry["Checks"];
		 $tech_profit = $cmp_rep_qry["Tech_Prafit"];
		 $comp_profit = $cmp_rep_qry["Comp_Profit"];
		 
		 $tot_fees = $cmp_rep_qry["Fees"];
		 $strdate = convert_date($tech_qry["Started_date"],"m/d/Y");
		 	
			if($totrows >0)
			{
				 $tot_bals = $cmp_rep_qry["Current_due"];
				 $tot_net_bal +=  $tot_bals;
				
					$tot_amt = number_format($tot_amt,2);
					$jcash	= number_format($jcash,2);
					$jcheck	= number_format($jcheck,2);
					$jcredit= number_format($jcredit,2);
					$comp_profit= number_format($comp_profit,2);
					$tech_profit= number_format($tech_profit,2);
					$tot_fees1= number_format($tot_fees,2);
					$tot_bals1= number_format($tot_bals,2);
				 if($showm!=""){$cpv=$comp_profit;}else{$cpc="";}
				$content .= '<tr class="input">
				  <td>'.$jobids.'&nbsp;</td>
				  <td>'.$strdate.'&nbsp;</td>
				  <td>'.$address.'&nbsp;</td>
				  <td align="right">'.$tot_amt.'&nbsp;</td>
				  <td align="right">'.$comp_part.'&nbsp;</td>
				  <td align="right">'.$tech_part.'&nbsp;</td>
				  <td align="right">'.$jcash.'&nbsp;</td>
				  <td align="right">'.$jcheck.'&nbsp;</td>
				  <td align="right">'.$jcredit.'&nbsp;</td>
				  <td align="right">'.$tot_fees1.'&nbsp;</td>
				  <td align="right">'.$tech_profit.'&nbsp;</td>';
				  if($cpv <> "") {
				   $content .= '<td align="right">'.$cpv.'&nbsp;</td>';
				   }
				  if($tot_bals1 < 0 ) { 
				  $content .= '<td align="right"><font color="red">'.$tot_bals1.'</font>&nbsp;</td>';
				  }
				  else
				  {
				   $content .= '<td align="right"><font color="green">'.$tot_bals1.'</font>&nbsp;</td>';
				  }

				  $content .= '<td>&nbsp;</td>
				</tr>';
			 }
	  }//job for loop
	  
	  $content .= '<tr class="input">
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>';
				  if($tot_net_bal < 0 ) { 
				  $content .= '<td align="right"><font color="red">'.number_format($tot_net_bal,2).'</font>&nbsp;</td>';
				  }
				  else
				  {
  				  $content .= '<td align="right"><font color="green">'.number_format($tot_net_bal,2).'</font>&nbsp;</td>';
				  }
				$content .= '</tr>';
	 }
	  $content .= '</table></td>';
   }//tech for loop
   			$content .= '
		  </tr>
		  <tr>
			<td colspan="5"  align="center" class="input">&nbsp;</td>
		  </tr>
		</table>';
			
			// get city form ls_main
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db("ls_main") or die("cannot select the database"); // Select DB
			$offds = select_single_record("office_master","*"," office_id = '$ofidd'");
			$company_url = $offds["url"]; 
			$dbnams = $offds["database_name"]; 
	  
			//Connecting Particular office database 
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db($dbnams) or die("cannot select the database"); // Select DB

			
			$emails = $comp_qrys["Email_ID1"]; 
			$email2 = $comp_qrys["Email_ID2"]; 
			$subject	= "Company Report";
			
			$FromName = "Administrator";
			//sending mail function
			ServerMail($ssmtp,$smptports,$uemail,$upsw,$from_email_id,$FromName,$subject,$content,$emails,$email2);

			header("location:homes.php?comd=view_comp_report&sms=1&comp_name=$compid&rep_name=$rep_name&posteds=1&actions=1");		    
}
else if($_REQUEST["sendpdfonly"]==1 || $_REQUEST["sendwithpdf"]==1)
{
  $tot=0;
  $compid	= $_REQUEST["comp_name"];
  $rep_name = $_REQUEST["rep_name"];
  $tech_id  = $_REQUEST["tech_id"];
  $jobid	= $_REQUEST["jb_ids"];
  
  $compname = select_single_record("company_master","company_name"," company_id = '$compid'");
  $comp_qrys = select_single_record("company_master","*","company_id = '$compid'");
  $rep_qrys = select_single_record("company_report_main","*"," Report_id = '$rep_name'");
  
  $rep_id = $_REQUEST["rep_name"]; 
	$select_techs = select_multi_records("company_report_main as a,company_report_job_detail as b","*"," a.Report_id = b.Report_id AND b.Report_id = '$rep_id'"," Group By b.Technician_ID ");
	$get_dates = select_single_record("company_report_main","*"," Report_id = '$rep_id'");
	$rnames = $get_dates["Report_name"];
	$sel_comps = select_single_record("company_master","*","company_id = '$get_dates[Company_id]'");
	$company_names= $sel_comp["company_name"];
	
	// for top
	$select_tech = select_multi_records("company_report_main as a,company_report_job_detail as b","*"," a.Report_id = b.Report_id AND b.Report_id = '$rep_id'"," Group By b.Technician_ID ");
	$get_date = select_single_record("company_report_main","*"," Report_id = '$rep_id'");
	$rname   = $get_date["Report_name"];
	$rnotes1 = stripslashes($get_date["notes"]);
	$rnotes2 = $get_date["report_note2"];
	$pval    = $get_date["Prev_Bal"];
	$cid = $get_date["Company_id"];
	$prv_rep = select_single_record("company_report_main","*"," Report_id < '$rep_name' and Company_id='$cid'","order by Report_id DESC limit 0,1  ");
	$prv_repts = select_single_record("company_report_main","sum(Prev_Bal) as pbal"," Report_id <= '$rep_name' and Company_id='$cid'","order by Report_id DESC  ");
	$smrep =  select_single_record("pending_payment_det","sum(amt_rec_tec) as recs,sum(amt_giv_tec) as givs","comp_id = '$cid' and rep_id <='$rep_id'");
	
   $reportid =  $prv_rep["Report_id"];
   $balfrm = $prv_rep["Current_due"];
   $sel_comp = select_single_record("company_master","*","company_id = '$cid'");
   $company_name= $sel_comp["company_name"];
   
   $dls_qry = select_multi_records("pending_payment_det","*","comp_id = '$cid' and rep_id ='$reportid'");
   $tot_pp = totalrows("pending_payment_det","*","comp_id = '$cid' and rep_id <='$reportid'"," limit 0,1");
	while($pprows = mysql_fetch_array($dls_qry))
	{
		$tot_amt_rec += $pprows["amt_rec_tec"];
		$tot_amt_giv += $pprows["amt_giv_tec"];
	}
   $netbal = $tot_amt_rec - $tot_amt_giv;
   
  $prv_rep_tot = totalrows("company_report_main","*"," Report_id <= '$rep_id' and Company_id='$cid'");

   if($prv_rep_tot > 2)
   {
		$vals = $prv_rep["Current_due"];
		if($tot_pp > 0) { $vals = $vals - $netbal; } 
   }
   else
   {
		$vals = $prv_rep["Prev_Bal"];
   }
												  
   if(($netbal > 0 && $vals > 0) || ($netbal > 0 && $vals < 0))
   {
		$frmbal = $vals + $netbal;
   }
   else if(($netbal < 0 && $vals < 0) || ($netbal < 0 && $vals > 0))
   {
		$frmbal = $vals + $netbal;
   }
   else if($netbal == 0)
   {
		$frmbal = $vals;
   }
   
   $compname = select_single_record("company_master","company_name"," company_id = '$_REQUEST[comp_name]'");
   
	//To get report name												   
	if($prv_rep["Report_name"] <> "") { $prv_name = $prv_rep["Report_name"]; } else { $prv_name = "This is first report"; }
//To get Previous report balance												   
if($vals > 0) { $prvbal1 = number_format(abs($vals),2); } else { $prvbal1 = number_format(abs($vals),2); }

//To get payment Details	
//To get balance forward from report
$netbals = number_format(abs($netbal),2);
$balfrms = number_format(abs($frmbal),2);

$stot_bal = number_format(abs($pval),2); 
	$grant_tot = $frmbal + $pval;
$gtot_bal = number_format(abs($grant_tot),2);
  
  if($_REQUEST["sendwithpdf"]==1)
	{
  $company_name = $comp_qrys["company_name"];
  $rept_name = $rep_qrys["Report_name"];
  $tech_id = substr($tech_id,0,-1);
  $jb_ids = substr($jb_ids,0,-1);

  $jb_ids = explode("@",$jobid);
  $tchid = explode("@",$tech_id);
  
  if($vals > 0) { $cprvbal1 = '<font color="green">$'.$prvbal1.'</font>'; } else { $cprvbal1 = '<font color="red">$'.$prvbal1.'</font>';  }
  if($netbal > 0) { $cnetbals = '<font color="green">$'.$netbals.'</font>'; } else { $cnetbals = '<font color="red">$'.$netbals.'</font>';  }
  if($frmbal > 0) { $cbalfrms = '<font color="green">$'.$balfrms.'</font>'; } else { $cbalfrms = '<font color="red">$'.$balfrms.'</font>';  }
  if($pval > 0) { $cstot_bal = '<font color="green">$'.$stot_bal.'</font>'; } else { $cstot_bal = '<font color="red">$'.$stot_bal.'</font>';  }
  if($grant_tot > 0) { $cgtot_bal = '<font color="green">$'.$gtot_bal.'</font>'; } else { $cgtot_bal = '<font color="red">$'.$gtot_bal.'</font>';  }
  
  $content = '<table width="75%"  cellspacing="0" cellpadding="0" align="center" border="0">
  <tr >
    <td colspan="5" align="right">&nbsp;</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Company Name :&nbsp;</td>
    <td colspan="4" align="left" class="input">'.$company_name.'</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Report Title :&nbsp;</td>
    <td colspan="4" align="left" class="input">  '.$rept_name.'</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Previous balance from report :&nbsp;</td>
    <td align="left" class="input">  '.$prv_name.'</td>
	<td width="38%" align="left">&nbsp;&nbsp;&nbsp;'.$cprvbal1.'</td>
	<td colspan="2" width="10%">&nbsp;</td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Payment Made on report :&nbsp;</td>
    <td  align="left" class="input">  '.$prv_name.'</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cnetbals.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Balance forward from report :&nbsp;</td>
    <td  align="left" class="input">  '.$prv_name.'</td>
	<td colspan="3"><div align="left">&nbsp;&nbsp;&nbsp;'.$cbalfrms.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">New report Total :&nbsp;</td>
    <td  align="left">&nbsp;</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cstot_bal.'</div></td>
  </tr>
  <tr >
    <td width="47%" align="right" class="message">Grand total :&nbsp;</td>
    <td  align="left" class="input">&nbsp;</td>
	<td colspan="3" ><div align="left">&nbsp;&nbsp;&nbsp;'.$cgtot_bal.'</div></td>
  </tr>  
  <tr class="data">
    <td colspan="5" align="right">&nbsp;</td>
  </tr>';
  for($j=0;$j<sizeof($tchid);$j++)
  {
	  $tech_id = $tchid[$j];
	  $tech_qry1 = select_single_record("technician","*","Technician_ID = '$tech_id' AND Active ='Y'");
	  $totrows1 = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.Technician_ID = '$tech_id' AND jm.Set_status_to='Completed'");
	   
	  $tech_names = $tech_qry1["FirstName"].$tech_qry1["LastName"];
	   if($totrows1 > 0)
		 {
	   $content .= '<tr>
    <td  align="center" class="message"><div align="right">Technician Name </div></td>
	<td colspan="4"  align="left"> &nbsp;&nbsp;'.$tech_names.'&nbsp;</td>
		  </tr>
		  <tr>
			<td colspan="5"  align="center" class="input">&nbsp;</td>
		  </tr>
		  <tr>
		 <td colspan="5"  align="center" class="input">';
		 if($showm!=""){$cp="Comp Profit";}else{$cp=""; $cols1 = 2;} 
		$content .= ' <table class="tablbrd"  cellspacing="0" cellpadding="0"  align="center" border="1">
		 <tr class="message">
          <td >Job ID</td>
          <td >Date</td>
		  <td >Address</td>
          <td >Total Amount </td>
		  <td >Comp Part</td>
		  <td >Tech Part</td>
          <td >Cash</td>
          <td>Check </td>
          <td>Credit</td>
		  <td>Open Bill</td>
		  <td>Fees</td>
		  <td>Tech Profit</td>';
		  if($showm!=""){
          $content .='<td>'.$cp.'&nbsp;</td>';
		  }
          $content .='<td >Balance</td>
		  <td colspan="'.$cols1.'">Net Balance</td>
        </tr>';
		
		$tot_net_bal=0;
	  for($i=0;$i<sizeof($jb_ids);$i++)
	  {
		 $jobids = $jb_ids[$i];
		 
		 $tech_qry = select_single_record("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobids' AND jm.Technician_ID = '$tech_id'");
		 $cmp_rep_qry = select_single_record("company_report_job_detail","*"," Report_id='$rep_name' AND job_id='$jobids'");
	
		 $totrows = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobids' AND jm.Technician_ID = '$tech_id'");
		 //print_r($cmp_rep_qry);
		 $cus_names = $tech_qry["FirstName"]." ".$tech_qry["LastName"];
		 $tot_amt = $cmp_rep_qry["tot_amt"];
 		 $address = $tech_qry["Address"];
		 $open_bill = $tech_qry["open_bill"];
		 $tech_part = $cmp_rep_qry["Tech_Part"];
		 $comp_part = $cmp_rep_qry["Comp_Part"];
		 $jcash	= $cmp_rep_qry["Cash"];
		 $jcheck =  $cmp_rep_qry["Checks"];
		 $jcredit = $cmp_rep_qry["Checks"];
		 $tech_profit = $cmp_rep_qry["Tech_Prafit"];
		 $comp_profit = $cmp_rep_qry["Comp_Profit"];
		// print_r($tech_qry); die;
		 $tot_fees = $cmp_rep_qry["Fees"];
		$strdate = convert_date($tech_qry["Ended_date"],"m/d/Y");
		 	
			if($totrows >0)
			{
				 $tot_bals = $cmp_rep_qry["Current_due"];
				 $tot_net_bal +=  $tot_bals;
				
					$tot_amt = number_format($tot_amt,2);
					$jcash	= number_format($jcash,2);
					$jcheck	= number_format($jcheck,2);
					$jcredit= number_format($jcredit,2);
					$comp_profit= number_format($comp_profit,2);
					$tech_profit= number_format($tech_profit,2);
					$tot_fees1= number_format($tot_fees,2);
					$tot_bals1= number_format($tot_bals,2);
					if($showm!=""){$ccp=$comp_profit; }else{$ccp=""; }
					
				
				$content .= '<tr class="input">
				  <td>'.$jobids.'&nbsp;</td>
				  <td>'.$strdate.'&nbsp;</td>
				  <td>'.$address.'&nbsp;</td>
				  <td align="right">'.$tot_amt.'&nbsp;</td>
				  <td align="right">'.$comp_part.'&nbsp;</td>
				  <td align="right">'.$tech_part.'&nbsp;</td>
				  <td align="right">'.$jcash.'&nbsp;</td>
				  <td align="right">'.$jcheck.'&nbsp;</td>
				  <td align="right">'.$jcredit.'&nbsp;</td>
				   <td align="right">'.$open_bill.'</td>
				  <td align="right">'.$tot_fees1.'&nbsp;</td>
				  <td align="right" >'.$tech_profit.'&nbsp;</td>';
				  if($showm!=""){
					  $content .= '<td align="right">'.$ccp.'&nbsp;</td>';
				  }
				  if($tot_bals1 < 0 ) { 
					  $content .= '<td align="right" ><font color="red">'.$tot_bals1.'</font>&nbsp;</td>';
				  }
				  else
				  {
				   $content .= '<td align="right"><font color="green">'.$tot_bals1.'</font>&nbsp;</td>';
				  }
				  $content .= '<td colspan="'.$cols1.'">&nbsp;</td>
				</tr>';
			 }
	  }//job for loop
	  
	  $content .= '<tr class="input">
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>';
				   if($showm!=""){
				  $content .= '<td>&nbsp;</td>';
				  }
				  if($tot_net_bal < 0 ) { 
				  $content .= '<td align="right"><font color="red">'.number_format($tot_net_bal,2).'</font>&nbsp;</td>';
				  }
				  else
				  {
					  $content .= '<td align="right"><font color="green">'.number_format($tot_net_bal,2).'</font>&nbsp;</td>';
				  }
				  $tot+=$tot_net_bal;
				$content .= '</tr>';
	 }

	  $content .= '</table></td>';
   }//tech for loop
   			
			 if($tot > 0) { 
											 $tota_b=  $off_report_name." Office has to pay to the $compname[company_name]  :<font color='green'> $".number_format(abs($tot),2)."</font>"; 
											 } else { 
											 $tota_b= " $compname[company_name] has to pay to the Office  $off_report_name :<font color='red'> $".number_format(abs($tot),2)."</font>";  
											 } 
			$content .= '
		  </tr>
		  <tr>
			<td colspan="5"  align="center" class="input">'.$tota_b.'</td>
		  </tr>
		</table>';
	}
	//echo $content; die;
		
	
	 	//PDF DATA STARTS
	define('FPDF_FONTPATH','font/');
	require('../pdf/fpdf.php');
	require('../pdf/mysql_table.php');
	require('../pdf/rotation.php');
	class PDF extends PDF_MySQL_Table
	{
	function Header()
	{
	}
	
	function RotatedText($x,$y,$txt,$angle)
	{
		//Text rotated around its origin
		$this->Rotate($angle,$x,$y);
		$this->Text($x,$y,$txt);
		$this->Rotate(0);
	}
	}
	 
	$pdf=new PDF();
	$pdf->AddPage();

//****************************************************
//$pdf->Line(x,y,x1,y1);
//if x and x1 are same then it is a vertical line
//if y and y1 are same then it is a horizondal line
//****************************************************

	
	$cnt=0; 
	$acnt =0;
	
		/*$pdf->SetFont('Arial','B',10);
		$pdf->text(85,20,"View Company Report");
		$pdf->SetFont('Arial','B',6);
		$pdf->text(85,30,"Company Name : ");
		$pdf->SetFont('Arial','',6);
		$pdf->text(105,30,$company_name);
		$pdf->SetFont('Arial','B',6);
		$pdf->text(89,35,"Report Title : ");
		$pdf->SetFont('Arial','',6);
		$pdf->text(105,35,$rname);
		$pdf->SetFont('Arial','B',6);
		$pdf->text(80.5,40,"Report Created Date :");
		
		$pdf->SetFont('Arial','',6);
		$pdf->text(110,40,convert_date($get_date["Create_date"],"m/d/Y"));
		$pdf->SetFont('Arial','B',6);
		$pdf->text(77,44,$rname." For ".$company_name. " As on ".convert_date($get_date["Create_date"],"m/d/Y"));
		
		$pdf->SetFont('Arial','',6);
		
	$a =60;
	$b=55;*/
	
	
		$pdf->SetFont('Arial','B',10);
		$pdf->text(84,15,"View Company Report");
		$pdf->SetFont('Arial','B',6);
		$pdf->text(85,19,"Company Name : ");
		$pdf->SetFont('Arial','',6);
		$pdf->text(105,19,$company_name);
		$pdf->SetFont('Arial','B',6);
		$pdf->text(89,22,"Report Title : ");
		$pdf->SetFont('Arial','',6);
		$pdf->text(105,22,$rnames);
		$pdf->SetFont('Arial','B',6);
		$pdf->text(80.5,25,"Report Created Date :");
		$pdf->SetFont('Arial','',6);
		$pdf->text(105,25,convert_date($get_date["Create_date"],"m/d/Y"));
		$pdf->SetFont('Arial','B',6);
		$pdf->text(77,28,$rname." For ".$company_name. " As on ".convert_date($get_date["Create_date"],"m/d/Y"));
		$pdf->SetFont('Arial','',6);
		$pdf->text(55,32,"Previous balance from report");
		$pdf->text(90,32,$prv_name);
		
		/*$pdf->SetTextColor(10,200,2);
		$pdf->SetTextColor(255,0,0);
		$pdf->SetTextColor(0,0,0);*/
		if($vals > 0)
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(120,32,"$ ".$prvbal1);
			$pdf->SetTextColor(0,0,0);
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(120,32,"$ ".$prvbal1);
			$pdf->SetTextColor(0,0,0);
			
		}
		///$pdf->SetTextColor(0,0,0);
		$pdf->text(59.6,34,"Payment Made on report");
		$pdf->text(90,34,$prv_name);
		//$pdf->text(110,34,$netbals);
		if($netbal >0)
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(120,34,"$ ".$netbals);
			$pdf->SetTextColor(0,0,0);
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(120,34,"$ ".$netbals);
			$pdf->SetTextColor(0,0,0);
			
		}
		//$pdf->SetTextColor(0,0,0);
		
		
		
		$pdf->text(56,36,"Balance forward from report");
		$pdf->text(90,36,$prv_name);
		if($frmbal > 0)
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(120,36,"$ ".$balfrms);
			$pdf->SetTextColor(0,0,0);
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(120,36,"$ ".$balfrms);
			$pdf->SetTextColor(0,0,0);
			
		}
		///$pdf->SetTextColor(0,0,0);
		///$pdf->text(110,36,$balfrms);
		
		$pdf->text(67.5,38,"New report Total");
		///$pdf->text(90,38,$prv_name);
		if($pval >0)
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(120,38,"$ ".$stot_bal);
			$pdf->SetTextColor(0,0,0);
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(120,38,"$ ".$stot_bal);
			$pdf->SetTextColor(0,0,0);
			
		}
		//$pdf->SetTextColor(0,0,0);
		//$pdf->text(110,38,$stot_bal);
		
		$pdf->text(73,40,"Grand total");
		//$pdf->text(90,40,$prv_name);
		if($grant_tot>0)
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(120,40,"$ ".$gtot_bal);
			$pdf->SetTextColor(0,0,0);
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(120,40,"$ ".$gtot_bal);
			$pdf->SetTextColor(0,0,0);
			
		}
	$a=48;
	$b=42;

	while($trow = mysql_fetch_array($select_techs))
	{
		
		$pdf->SetFont('Arial','',6);
		$tech_id = $trow["Technician_ID"];
		$balance = $trow["Current_due"];
		$comp_profit = $trow["Comp_Profit"];
		$tech_profit = $trow["Tech_Prafit"];
		$curdate = convert_date($trow["Create_date"],"m/d/Y");
		$tech_qry = select_single_record("technician","*","Technician_ID = '$tech_id'");
		$totrows1 = totalrows("technician","*","Technician_ID = '$tech_id'");
		$tech_names = $tech_qry["FirstName"].$tech_qry["LastName"];
		$sel_job = select_multi_records("company_report_job_detail","*"," Report_id='$rep_id' AND Technician_ID = '$tech_id'");
		$totrowss = totalrows("company_report_job_detail","*"," Report_id='$rep_id' AND Technician_ID = '$tech_id'");
		$pdf->line(15,$b+2,195,$b+2);
		$pdf->line(15,$b+8,195,$b+8);
		$pdf->SetFont('Arial','B',6);
		$pdf->text(20,$b,"Technician Name :");
		$pdf->SetFont('Arial','',6);
		$pdf->text(40,$b,$tech_names);
		
			$pdf->SetFont('Arial','B',6);
			$pdf->SetFont('Arial','B',6);
			$pdf->text(17,$a,"#");
			$pdf->text(20,$a,"Job ID");
			$pdf->text(31,$a,"Date");
			$pdf->text(47,$a,"Address");
			$pdf->text(64,$a,"Tot Amt");
			$pdf->text(75,$a,"Tech Part");
			$pdf->text(87,$a,"Comp Part");
			$pdf->text(102,$a,"Cash");
			$pdf->text(112,$a,"Check");
			$pdf->text(123,$a,"Credit");
			$pdf->text(131,$a,"OpenBill");
			$pdf->text(142,$a,"Fee");
			$pdf->text(150,$a,"Tech Pft");
			if($showm!=""){
			$pdf->text(162,$a,"Comp Pft");
			}
			$pdf->text(174,$a,"Balance");
			$pdf->text(186,$a,"Net Bal");
		$tot_bal =0;		
		$count =0;										
		$a =$a+3;
		$b =$b+3;
		while($jrows = mysql_fetch_array($sel_job))
		{ 
			$pdf->SetFont('Arial','',6);
			$a =$a+4;
			$b =$b+4;
			$jobid = $jrows["job_id"];
			$sel_dls = select_single_record("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobid' AND jm.Technician_ID = '$tech_id'");
			$totrowss = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobid' AND jm.Technician_ID = '$tech_id'");
			$enddate = convert_date($sel_dls["Ended_date"],"m/d/Y");
			
			$balance = $jrows["Current_due"];
			$cont = $count;
			$count++;
			$pdf->text(16,$a,$count);
			$pdf->text(22,$a,$jobid);
			$pdf->text(29,$a,$enddate);
			$pdf->text(47,$a,$sel_dls["Address"]);
			$pdf->text(72-tpos($jrows["tot_amt"]),$a,number_format($jrows["tot_amt"],2));
			$pdf->text(85-tpos($jrows["Tech_Part"]),$a,number_format($jrows["Tech_Part"],2));
			$pdf->text(96-tpos($jrows["Comp_Part"]),$a,number_format($jrows["Comp_Part"],2));
			$pdf->text(106-tpos($jrows["Cash"]),$a,number_format($jrows["Cash"],2));
			$pdf->text(118-tpos($jrows["Checks"]),$a,number_format($jrows["Checks"],2));
			$pdf->text(128-tpos($jrows["Credit"]),$a,number_format($jrows["Credit"],2));
			$pdf->text(140-tpos($jrows["Credit"]),$a,number_format($sel_dls["open_bill"],2));
			$pdf->text(149-tpos($jrows["Fees"]),$a,number_format($jrows["Fees"],2));
			$pdf->text(160-tpos($jrows["Tech_Prafit"]),$a,number_format($jrows["Tech_Prafit"],2));
			if($showm!="")
			{
				$pdf->text(173-tpos($jrows["Comp_Profit"]),$a,number_format($jrows["Comp_Profit"],2));
			}
			if($balance < 0)
			{
				$pdf->SetTextColor(255,0,0);
				$pdf->text(183-tpos($balance),$a,number_format($balance,2));
			}
			else
			{
				$pdf->SetTextColor(10,200,2);
				$pdf->text(183-tpos($balance),$a,number_format($balance,2));
			}
			
			$pdf->SetTextColor(0,0,0);
			$balance = $jrows["Current_due"];
			$cont = $count;
			
			$cnt++; 
			$tot_bal +=$balance; 
			$acnt++;
			
		} 
		$hol_bal +=$tot_bal;
		
		//For Total Balance
		if($tot_bal < 0)
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->text(185,$a+3,number_format($tot_bal,2));
		}
		else
		{
			$pdf->SetTextColor(10,200,2);
			$pdf->text(185,$a+3,number_format($tot_bal,2));
		}
		
		$pdf->SetTextColor(0,0,0);
		 if($tot_bal > 0) 
		 { 
			 $pdf->SetFont('Arial','B',6);
			 $pdf->text(120,$a+10,"$off_report_name office has to pay to $compname[company_name] :");
			 $pdf->SetTextColor(10,200,2);
			 $pdf->text(185,$a+10,"$".number_format(abs($tot_bal),2));	 
		 } 
		 else
		 { 
			 $pdf->SetFont('Arial','B',6);
			 $pdf->text(120,$a+10,"$compname[company_name] has to pay to the $off_report_name office :");
			 $pdf->SetTextColor(255,0,0);
			 $pdf->text(185,$a+10,"$".number_format(abs($tot_bal),2));
		 } 
		
		$pdf->SetTextColor(0,0,0);
		$a=$a+30;
		$b=$b+30;
	}
		
		if($hol_bal < 0) 
		{ 
			$pdf->text(120,$a-4," $compname[company_name] has to pay to the $off_report_name office :");
			$pdf->SetTextColor(255,0,0);
			$pdf->SetFont('Arial','B',6);
			$pdf->text(185,$a-4,"$".number_format(abs($hol_bal),2));
		//echo " Technicians Have To Pay To The $compname[company_name]     <font color='red'>$".number_format(abs($hol_bal),2)."</font>";
		}else 
		{ 
			$pdf->text(120,$a-4,"$off_report_name office has to pay to the $compname[company_name] :");
			$pdf->SetTextColor(10,200,2);
			$pdf->SetFont('Arial','B',6);
			$pdf->text(185,$a-4,"$".number_format(abs($hol_bal),2));
		//echo "Company Has To Pay To The Technicians    <font color='green'>$".number_format(abs($hol_bal),2)."</font>";
		}
		//PDF DATA ENDS
	

	$pdf->Output('../pdf/comp_rep/'.$rname.'_'.$rep_id.'.pdf','F');
	
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db("ls_main") or die("cannot select the database"); // Select DB
			$offds = select_single_record("office_master","*"," office_id = '$ofidd'");
			$company_url = $offds["url"]; 
			$dbnams = $offds["database_name"]; 
			$from_email_id = $offds["from_email_id"]; 
			//Connecting Particular office database 
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db($dbnams) or die("cannot select the database"); // Select DB

			 $emails = $comp_qrys["Email_ID1"]; 
			 $email2 = $comp_qrys["Email_ID2"]; 
			 
			 $subject	= "Company PDF Report";
			
			/* Mailing Server Code */
			
			// get city form ls_main
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db("ls_main") or die("cannot select the database"); // Select DB
			$offds = select_single_record("office_master","*"," office_id = '$ofidd'");
			$company_url = $offds["url"]; 
			$dbnams = $offds["database_name"]; 
		  
			//Connecting Particular office database 
			mysql_connect("localhost",$dbuname,$dbpsw) or die("cannot connect to database"); 
			mysql_select_db($dbnams) or die("cannot select the database"); // Select DB

			$mail = new PHPMailer();
			
			$mail->IsSMTP();// send via SMTP
			if($ssmtp == "smtp.gmail.com")
			{
				$mail->SMTPSecure = "ssl";// sets the prefix to the servier
			}
			$mail->Host     = $ssmtp; // SMTP servers
			$mail->SMTPAuth = true;     // turn on SMTP authentication
			$mail->Username = $uemail;  // SMTP username
			$mail->Password = $upsw; // SMTP password
			$mail->Port 	= $smptports; // SMTP Port

			$mail->WordWrap = 500;
			if($_REQUEST["sendpdfonly"]==1 || $_REQUEST["sendwithpdf"]==1)
			{
				
				$mail->AddAttachment('../pdf/comp_rep/'.$rname.'_'.$rep_id.'.pdf', "Viewcompany.pdf");
			}
			$mail->IsHTML(true);  
			$mail->From     = $from_email_id;
			$mail->FromName = "Administrator";
			$mail->AddAddress($emails); // optional name
			$mail->AddAddress($email2); // optional name
			$mail->Subject  =  $subject;
			$mail->Body     =  $content;
			$mail->Send();
			$msg="Mail Send Successfully!";
			/* End */			
}

if($_REQUEST["dsms"] == 1)
{
	$msg = "job deleted Successfully!";
}

	$hol_bal=0;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>MANAGE ADMIN</title>
</head>
<script type="text/javascript" src="../script/wz_tooltip.js"></script>

<body>
<form name="tech_frm" method="post">
<input type="hidden" name="jobid" id="jobid" value="<?=$jobid?>"> 
<table width="100%" cellpadding="0" cellspacing="0" class="tablbrd">
	<tr>
	  <td colspan="5" align="center"  class="title"> VIEW COMPANY REPORT sala</td>
	</tr>
	<tr>
	  <td width="3%" align="center" class="button">&nbsp;</td>
	  <td width="3%" align="center" class="button">&nbsp;</td>
	  <td width="3%" align="center" class="button">&nbsp;</td>
	  <td width="72%" align="center" class="button">&nbsp;</td>
	  <td width="19%" align="center" class="button"><b></b></td>
	</tr> 
	<?php if($msg <> "") { ?>
		  <tr>
			<td colspan="5" class="alert_green"><?php echo $msg; ?></td>
		  </tr>
		  <? } ?>
	<tr>
	<td colspan="5" align="center" class="button">
		<table width="75%" border="0" cellspacing="2" cellpadding="2" class="tablbrd" align="center">
							<tr >
								<td width="42%" align="right" class="message"><div align="right">Company Name</div> </td>
								<td colspan="4" align="left" class="input">
								<?php //echo $comp_qry["company_id"]; 
									$qryid = select_single_record("user_master","*"," Username = '$user'");
									if($qryid['office_id'] == "")
									{
										$cnum = totalrows("company_master","*"," username='$user' ");
										if($cnum > 0)
										{
											$whr = " username='$user' and is_active='Y'";
										}
										else
										{
											$whr ="";
										}
									}
									else
									{
										$whr ="";
									}

									$select_comp = select_multi_records("company_master","*",$whr," Order By company_name ASC");
									
								?>
								<select name="comp_name" id="comp_name" onChange="getrep();">
								<option value="">--Select--</option>
								<?php
								
								while($cmrow = mysql_fetch_array($select_comp))
								{?>
								  <option value="<?php echo $cmrow["company_id"]; ?>" <?php if($_REQUEST["comp_name"] == $cmrow["company_id"]){ echo "selected"; }?>><?php echo $cmrow["company_name"]; ?></option>
								<? } ?>
								</select>								</td>
								</tr>
										  <?   
										  	   if($_REQUEST["posteds"] ==1)
											   {
												   $wherecus = " company_id = '$_REQUEST[comp_name]'"; 
												   $select_rep = select_multi_records("company_report_main","*",$wherecus ,'ORDER BY Report_id DESC');
												   $totalrows = totalrows("company_report_main","*",$wherecus);
											   }
											   
										  ?>
												 <tr>
												   <td  align="center" class="message"><div align="right">Report Title </div></td>
												   <td colspan="4"  align="center" class="input"><select name="rep_name" id="rep_name" onChange="gettech();">
												   <option value="">--Select--</option>
												   <?php
													  $tot_amt_rec = 0;
													  $tot_amt_giv =0;
													 
												    while($rowp = mysql_fetch_array($select_rep))
													{ 
													
													?>
													  <option value="<?php echo $rowp["Report_id"]; ?>" <?php if($rowp["Report_id"] == $_REQUEST["rep_name"]){ echo "Selected"; }?>><?php echo $rowp["Report_id"]." - ".$rowp["Report_name"]; ?></option>
													<? } ?>
												   </select>
												   (ID Report Title)&nbsp;</td>
												 </tr>
												 <?php 
												 if($_REQUEST["actions"] == 1)
												 {
												   $rep_id = $_REQUEST["rep_name"];
												   $c_id = $_REQUEST["comp_name"];
												   
												   $data = new stdClass();
												 
												   $select_tech = select_multi_records("company_report_main as a,company_report_job_detail as b","*"," a.Company_id = '$c_id' AND a.Report_id = b.Report_id AND b.Report_id = '$rep_id'"," Group By b.Technician_ID ");
												   $get_date = select_single_record("company_report_main","*"," Report_id = '$rep_id' AND Company_id = '$_cid'");
												   $rname   = $get_date["Report_name"];
												   $rnotes1 = stripslashes($get_date["notes"]);
												   $rnotes2 = stripslashes($get_date["report_note2"]);
												   $pval    = $get_date["Prev_Bal"];
												   $prv_rep = select_single_record("company_report_main","*"," Report_id < '$rep_id' and Company_id='$c_id'","order by Report_id DESC limit 0,1  ");
												   $prv_repts = select_single_record("company_report_main","sum(Prev_Bal) as pbal"," Report_id <= '$rep_id' and Company_id='$c_id'","order by Report_id DESC  ");
												  $smrep =  select_single_record("pending_payment_det","sum(amt_rec_tec) as recs,sum(amt_giv_tec) as givs","comp_id = '$c_id' and rep_id <='$rep_id'");
												   
												   $reportid =  $prv_rep["Report_id"];
												   $balfrm = $prv_rep["Current_due"];
												   $sel_comp = select_single_record("company_master","*","company_id = '$_REQUEST[comp_name]'");
												   $company_name= $sel_comp["company_name"];
												   
												   $dls_qry = select_multi_records("pending_payment_det","*","comp_id = '$c_id' and rep_id ='$reportid'");
												   $tot_pp = totalrows("pending_payment_det","*","comp_id = '$c_id' and rep_id <='$reportid'"," limit 0,1");
												   
													while($pprows = mysql_fetch_array($dls_qry))
													{
														$tot_amt_rec += $pprows["amt_rec_tec"];
														$tot_amt_giv += $pprows["amt_giv_tec"];
													}
												   $netbal = $tot_amt_rec - $tot_amt_giv;
												   
												   
												  $prv_rep_tot = totalrows("company_report_main","*"," Report_id <= '$rep_id' and Company_id='$c_id'");

												   if($prv_rep_tot > 2)
												   {
												   		$vals = $prv_rep["Current_due"];
														if($tot_pp > 0) { $vals = $vals - $netbal; } 
												   }
												   else
												   {
												   		$vals = $prv_rep["Prev_Bal"];
												   }
												   if(($netbal > 0 && $vals > 0) || ($netbal > 0 && $vals < 0))
												   {
												   	 	$frmbal = $vals + $netbal;
												   }
												   else if(($netbal < 0 && $vals < 0) || ($netbal < 0 && $vals > 0))
												   {
												   		$frmbal = $vals + $netbal;
												   }
												   else if($netbal == 0)
												   {
												   		$frmbal = $vals;
												   }
												   
												   $compname = select_single_record("company_master","company_name,company_id"," company_id = '$c_id'");
												   //$compname["company_name"];
												   //echo "<br>s s ".$ss."<br>".$ss1."<br>";
												   //echo "$prv_rep[Prev_Bal]<br>$frmbal  ".$ss =  $frmbal + $prv_rep["Current_due"];
												   
												   /*else
												   {
												   	  //$frmbal = $prv_rep["Prev_Bal"] - $netbal;
												   }*/
													
													//To get report name												   
													if($prv_rep["Report_name"] <> "") { $prv_name = $prv_rep["Report_name"]; } else { $prv_name = "This is first report"; }
//To get Previous report balance												   
if($vals > 0) { $prvbal1 = "<font color='green'>$".number_format(abs($vals),2)."</font>"; } else { $prvbal1 = "<font color='red'>$".number_format(abs($vals),2)."</font>"; }

	   //To get payment Details	
	   if($netbal > 0) { $netbals = "<font color='green'>$".number_format(abs($netbal),2)."</font>"; } else { $netbals = "<font color='red'>$".number_format(abs($netbal),2)."</font>"; }
	   //To get balance forward from report
	   if($frmbal > 0) { $balfrms = "<font color='green'>$".number_format(abs($frmbal),2)."</font>"; } else { $balfrms = "<font color='red'>$".number_format(abs($frmbal),2)."</font>"; }
	   										

$new_bal = select_single_record("company_report_job_detail a, company_report_main b","sum(a.Current_due) as newbal"," a.Report_id = b.Report_id AND a.Report_id = '$rep_id' AND b.Company_id = '$c_id'");

//if($pval > 0) { $stot_bal = "<font color='green'>$".number_format(abs($pval),2)."</font>"; } else { $stot_bal = "<font color='red'>$".number_format(abs($pval),2)."</font>"; }

if($new_bal["newbal"] > 0) { $stot_bal = "<font color='green'>$".number_format(abs($new_bal["newbal"]),2)."</font>"; } else { $stot_bal = "<font color='red'>$".number_format(abs($new_bal["newbal"]),2)."</font>"; }
													//To get grant total
													$grant_tot = $frmbal + $new_bal["newbal"];
if($grant_tot > 0) { $gtot_bal = "<font color='green'>$".number_format(abs($grant_tot),2)."</font>"; } else { $gtot_bal = "<font color='red'>$".number_format(abs($grant_tot),2)."</font>"; }
												 ?>
												 <tr>
												   <td   align="center" class="message"><div align="right">Report Created Date&nbsp;</div></td>
												   
												   <td width="48%" colspan="4"  align="center" class="input"><?php echo convert_date($get_date["Create_date"],"m/d/Y"); ?>&nbsp;</td>
	     										 </tr>
												 <tr>
												   <td class="message"><div align="right">Previous balance from report&nbsp;</div></td>
												   <td colspan="2" class="message" nowrap="nowrap"><div align="left"><?=$prv_name?></div></td>
												   <td width="48%" colspan="2"  align="center" class="input"><?php echo $prvbal1; ?>&nbsp;</td>
	     										 </tr>
												 <tr>
												   <td class="message"><div align="right">Payment Made on report&nbsp;</div></td>
												   <td colspan="2" class="message" nowrap="nowrap"><div align="left"><?=$prv_name?></div></td>
												   <td width="48%" colspan="2"  align="center" class="input"><?php echo $netbals; ?>&nbsp;</td>
	     										 </tr>
												 <tr>
												   <td class="message"><div align="right">Balance forward from report&nbsp;</div></td>
												   <td colspan="2" class="message" nowrap="nowrap"><div align="left"><?=$prv_name?></div></td>
												   <td width="48%" colspan="2"  align="center" class="input"><?php echo $balfrms; ?>&nbsp;</td>
	     										 </tr>
												 <tr>
												   <td class="message"><div align="right">New report Total&nbsp;</div></td>
												   <td colspan="2" class="message" nowrap="nowrap">&nbsp;</div></td>
												   <td width="48%" colspan="2"  align="center" class="input"><?php echo $stot_bal; ?>&nbsp;</td>
	     										 </tr> 
												 <tr>
												   <td class="message"><div align="right">Grand total&nbsp;</div></td>
												   <td colspan="2" class="message" nowrap="nowrap">&nbsp;</td>
												   <td width="48%" colspan="2"  align="center" class="input"><?php echo $gtot_bal; ?>&nbsp;</td>
	     										 </tr>
												 
												 <?php if($_REQUEST["sms"] == 1)
												 {?>
												 <tr>
												   <td colspan="5"  align="center" class="message"><?php echo "<font color='green'>Mail Sent Successfully!</font>"; ?>&nbsp;</td>
											     </tr>
												 <? } ?>

												 <?
												 
												 $last_repid = select_single_record("company_report_main","Report_id,Company_id","Company_id = '$c_id'","Order By Report_id DESC Limit 1");
												 //var_dump($c_id,$get_date);
												 if (($last_repid['Company_id'] != $c_id) || empty($last_repid['Company_id']) )
												 {
												 	
												 	?>
												 	<tr>
													   <td colspan="1" class="title"><div align="right">Access Denied!</div></td>
													   <td colspan="4" class="title">Do not try to access reports that are not yours. Thank you for using LMS</td>
												     </tr>
												 	<?
												 	
												 }
												 else 
												 {
												 	$data->loop = new stdClass();
												 	$data->loop->cash = "Cash";
												 	$data->loop->credit = "Credit";
												 	$data->loop->checks = "Checks";
												 	$data->loop->callback = "CallBack";
												 $data->money = new stdClass();
												 $data->money->cash = new stdClass();
												   $data->money->cash->in = 0;
												   $data->money->cash->out = 0;
												   
												   $data->money->credit = new stdClass();
												   $data->money->credit->in = 0;
												   $data->money->credit->out = 0;
												   
												   $data->money->check = new stdClass();
												   $data->money->check->in = 0;
												   $data->money->check->out = 0;
												   
													
												 

												 	
												   //$data->money->cash->balance = $data->money->cash->in - $data->money->cash->out;
												   
												 	?>
												 	
														 <tr>
														   <td colspan="5"  align="center" class="title"><?php echo $rname." For ".$company_name." As on  ".convert_date($get_date["Create_date"],"m/d/Y");?>&nbsp;</td>
													     </tr>
												 	<?
												 	
												 $cnt=0; 
												 $acnt =0;
												 
												 while($trow = mysql_fetch_array($select_tech))
												 {
												 	
												    $tech_id = $trow["Technician_ID"];
													$Phone = $throw["Phone"];
													$phones = splitphone($Phone);
													$balance = $trow["Current_due"];
													$comp_profit = $trow["Comp_Profit"];
													$tech_profit = $trow["Tech_Prafit"];
													$curdate = convert_date($trow["Create_date"],"m/d/Y");
												   	
													$tech_qry = select_single_record("technician","*","Technician_ID = '$tech_id'");
													$totrows1 = totalrows("technician","*","Technician_ID = '$tech_id'");
													$tech_names = $tech_qry["FirstName"].$tech_qry["LastName"];
												
													//Technician Details Window Function
													$message = technician_details($_REQUEST['comp_name'],$tech_id);
	 												
												?>
												 <input type="hidden" name="tch_id<?php echo $cnt; ?>" id="tch_id<?php echo $cnt; ?>" value="<?php echo $tech_id; ?>">
												 <tr>
												   <td colspan="2"  align="center" class="message"><div align="right">Technician Name&nbsp;</div></td>
												   <td colspan="3"  align="center" class="input">&nbsp;<span onmouseover="Tip('<?=$message?>')" onmouseout="UnTip()"> <?php echo $tech_names; ?></span>&nbsp;</td>
											     </tr>
											     <?
											     foreach ($data->loop as $field)
	 												{
													$sel_job = select_multi_records("company_report_job_detail","*"," Report_id='$rep_id' AND Technician_ID = '$tech_id'");
													$totrowss = totalrows("company_report_job_detail","*"," Report_id='$rep_id' AND Technician_ID = '$tech_id'");
											     ?>
											     <tr>
												   <td colspan="1"  align="center" class="message"><div align="right">Jobs paid with:&nbsp;</div></td>
												   <td colspan="4"  align="center" class="input">&nbsp;<span onmouseover="Tip('This list only has the jobs paid with <?=$field?>')" onmouseout="UnTip()"> <?php echo $field; ?></span>&nbsp;</td>
											     </tr>
												 <tr>
												   <td colspan="5"  align="center" class="input">
												   <table class="tablbrd"  border="0" cellspacing="2" cellpadding="2"  align="center">
												   <tr class="message">
												     <td >#</td>
												     <td >Job ID</td>
												     <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
													 <td >Edit</td>
													 <? } ?>
													 <td >Date</td>
												     <td >Address</td>
												     <td >Tot Amt </td>
												     <td >Tech Part </td>
												     <td >Comp Part </td>
												     <td >Cash</td>
												     <td>Check </td>
												     <td>Credit</td>
												     <!--
												     <td>Open Bill </td>
												     -->
												     <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"])) && !empty($_REQUEST["jbid"])) { ?>
													 <td>Paid With</td>
													 <td>Paid To</td>
													 <td>Submit</td>
													 <? } ?>
												     <td>Fee</td>
												     <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"])) && !empty($_REQUEST["jbid"])) { ?>
													 <td>Job Closed Time </td>
													 <? } ?>
												     <!--
												     <td>Job Closed Time </td>
												     -->
												     <td>Tech Profit</td>
													 <?php if($shows!="" || empty($comp_login) && !empty($officeids["office_id"])){ ?>
													 <td>Comp Profit</td>
													 <? } ?>
													 <td>Balance</td>
													 <td>Net Balance</td>
													 <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
													 <td>Remove</td>
													 <? } ?>
												     </tr>
													<? 	
													$tot_bal =0;		
													$count = 0;	
													
													 $tot_amts = 0;
													 $tot_cmprts = 0;
													 $tot_techprt =0;
													 $tot_cash = 0;
													 $tot_chk =0; 
													 $tot_crdt =0;
													 $tot_openbill =0;
													 $fee_tot = 0;
													 $totcmp_prft = 0;
													 $tottech_prft = 0;
													while($jrows = mysql_fetch_array($sel_job))
													{
														if($field == "CallBack")
														{
														  if($jrows["Cash"] != 0 || $jrows["Checks"] != 0 || $jrows["Credit"] != 0)
														  {
														  	continue;
														  }
														}
														else 
														{
															if($jrows[$field] == 0)
															{
															  	continue;
															}
														}
													  $jobid = $jrows["job_id"];
   											    	  $sel_dls = select_single_record("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobid' AND jm.Technician_ID = '$tech_id'");
													  $totrowss = totalrows("job_master as jm,customer_master as cu","*","jm.customer_id = cu.customer_id AND jm.job_id = '$jobid' AND jm.Technician_ID = '$tech_id'");
													  $enddate = convert_date($sel_dls["Ended_date"],"m/d/Y");
													  
													  $balance = $jrows["Current_due"];
													  $cont = $count;
													  $count++;
													  
													 
													  ?>
	   												 <input type="hidden" name="jb_id<?php echo $acnt; ?>" id="jb_id<?php echo $acnt; ?>" value="<?php echo $jobid; ?>">
													   <tr class="input">
													   <td><?php echo $count; ?>&nbsp;</td>
												   	   <td>
													   <?php if(empty($comp_login) && !empty($officeids["office_id"])) { ?>
													   <a style="cursor:pointer" onclick="javascript: window.open('homes.php?comd=add_job&edit=1&cview=1&id=<?php echo $jobid; ?>','_blank','width=1250,height=350,left=200,top=200,scrollbars,resizable').focus();"><?php echo $jobid; ?></a> 
													   <? } else { 
													   echo $jobid; } ?>
													   </td>
													   <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
													   <td>&nbsp;<input name="image" type="image" src="../images/comment_edit.png" onClick="javascript:editdata(<?php echo $jobid; ?>,<?php echo $_REQUEST["comp_name"]; ?>,<?php echo $_REQUEST["rep_name"]; ?>);" >&nbsp;</td>
													   <? } ?>
												   	   <td><?php echo $enddate; ?></td>
												   	   <td><?php echo $sel_dls["Address"]; ?></td>
												   	   <?php if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input type="text" size="6" name="vamount" maxlength="8" id="vamount" value="<?=$jrows["tot_amt"]?>" onBlur="javascript:numformat();"></td>
												   	   <? } else { ?>
													   <td><div align="right"><?php echo number_format($jrows["tot_amt"],2);?></div></td>
													   <? } if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input type="text" size="6" name="vTech_Part"  maxlength="8" id="vTech_Part" value="<?=$jrows["Tech_Part"]?>" onBlur="javascript:numformat();"></td>
												   	   <? } else { ?>
														<td><div align="right"><?php echo number_format($jrows["Tech_Part"],2); ?></div></td>
													   <?php }
													   if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input type="text" size="6" name="vComp_Part"  maxlength="8" id="vComp_Part" value="<?=$jrows["Comp_Part"]?>" onBlur="javascript:numformat();"></td>
												   	   <? } else { ?>												   	   
												   	   <td><div align="right"><?php echo number_format($jrows["Comp_Part"],2); ?></div></td>
												   	   <?php } 
												   	   if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input disabled type="text" size="6" name="vcash"  maxlength="8" id="vcash" value="<?=$jrows["Cash"]?>" onBlur="javascript:numformat();"></td>
													   <? } else {?>
													   <td><div align="right"><?php echo number_format($jrows["Cash"],2); ?></div></td>
												   	   <? }
												   	   if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input disabled type="text" size="6" name="vChecks"  maxlength="8" id="vChecks" value="<?=$jrows["Checks"]?>" onBlur="javascript:numformat();"></td>
													   <? } else {?>
													   <td><div align="right"><?php echo number_format($jrows["Checks"],2); ?></div></td>
													   <? }
													   if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input disabled type="text" size="6" name="vCredit"  maxlength="8" id="vCredit" value="<?=$jrows["Credit"]?>" onBlur="javascript:numformat();"></td>
													   <? } else {?>
												   	   <td><div align="right"><?php echo number_format($jrows["Credit"],2); ?></div></td>
												   	   <? }
												   	   /*
												   	   if($jobid == $_REQUEST["jbid"]) { ?>
													   <td><input type="text" size="6" name="vopen_bill"  maxlength="8" id="vopen_bill" value="<?=$sel_dls["open_bill"]?>" onBlur="javascript:numformat();"></td>
													   <? } else {?>
													   <td><div align="right"><?php echo number_format($sel_dls["open_bill"],2);?></div></td>
												   	   <? }
												   	   */
													   ?>
													   
												   	   <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"])) && !empty($_REQUEST["jbid"])) { 
													     if($jobid == $_REQUEST["jbid"]) {
													   ?>
													   <td align="center">
													   		<select name="Job_Payment_Method" id="Job_Payment_Method">
																<option value="Cash" <?php if($field == "Cash"){ echo "Selected"; }?>>Cash</option>
																<option value="Credit" <?php if($field == "Credit"){ echo "Selected"; }?>>Credit</option>
																<option value="Checks" <?php if($field == "Checks"){ echo "Selected"; }?>>Check</option>
															</select>
													   </td>
													   <td>
													   <nobr><input type="radio" name="collector" value="company" /> Company</nobr><br />
													   	<nobr><input type="radio" name="collector" value="technician" /> Technician</nobr>
													   </td>
													   <td align="center"><input name="image" type="image" src="../images/button_ok.png" onClick="javascript:savedata(<?=$jobid?>,<?=$_REQUEST["comp_name"]?>,<?=$_REQUEST["rep_name"]?>);">&nbsp;</td>
													   <? } else { ?>
													   <td ><?=$field?></td>													   
													   <td >&nbsp;</td>													   
													   <? } } ?>
												   	   <td><div align="right"><?php echo number_format($jrows["Fees"],2); ?></div></td>
												   	   <?php if($jobid == $_REQUEST["jbid"]) { ?>
													   <td>
													   		<select name="Job_Close_Time" id="Job_Close_Time">
																<option value="Day" <?php if($sel_dls["Job_Close_Time"] == "Day"){ echo "Selected"; }?>>Day</option>
																<option value="Night" <?php if($sel_dls["Job_Close_Time"] == "Night"){ echo "Selected"; }?>>Night</option>
																<option value="Sunday" <?php if($sel_dls["Job_Close_Time"] == "Sunday"){ echo "Selected"; }?>>Sunday</option>
															</select>
															</td>
													   <? } else {?>
													   <!--<td align="center"><?= $sel_dls["Job_Close_Time"]?>&nbsp;</td>-->
													   <? } ?>
                                                       <td><div align="right"><?php echo number_format($jrows["Tech_Prafit"],2); ?></div></td>
												   	   <?php if($shows!="" || empty($comp_login) && !empty($officeids["office_id"])){ ?>
													   <td><div align="right"><?php echo number_format($jrows["Comp_Profit"],2); ?></div></td>
													   <? } ?>
												   	   <td><div align="right"><?php if($balance < 0 ) { echo "<font color='red'>".number_format($balance,2)."</font>";}else { echo "<font color='green'>".number_format($balance,2)."</font>"; } ?></div></td>
												   	   <td>&nbsp;</td>
												   	   <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
													   <td align="center"><input name="image" type="image" src="../images/delete_icon.gif" onClick="javascript:delfun('<?=$jobid?>','<?=$_REQUEST['comp_name']?>','<?=$_REQUEST["rep_name"]?>');"></td>
													   <? } ?>
											   	     </tr>
												   	 <?
												   	 $tot_bal +=$balance;
												   	 $acnt++;
													 
													  $tot_amts += $jrows["tot_amt"];
													 $tot_cmprts += $jrows["Comp_Part"];
													 $tot_techprt += $jrows["Tech_Part"];
													 $tot_cash += $jrows["Cash"];
													 $tot_chk += $jrows["Checks"]; 
													 $tot_crdt += $jrows["Credit"];
													 $fee_tot += $jrows["Fees"];
													 $totcmp_prft += $jrows["Comp_Profit"];
													 $tottech_prft += $jrows["Tech_Prafit"];
													 $tot_openbill += $sel_dls["open_bill"];
													 }?>
													 
													 <tr class="input">
												     <td>&nbsp;</td>
												     <td>&nbsp;</td>
												     <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
													 <td>&nbsp;</td>
													 <? } ?>
												     <td>&nbsp;</td>
												     <td>&nbsp;</td>
												     <td><div align="right"><? echo number_format($tot_amts,2);?></div></td>
												     <td><div align="right"><? echo number_format($tot_techprt,2);?></div></td>
												     <td><div align="right"><? echo number_format($tot_cmprts,2);?></div></td>
												     <td><div align="right"><? echo number_format($tot_cash,2);?></div></td>
												     <td><div align="right"><? echo number_format($tot_chk,2);?></div></td>
												     <td><div align="right"><? echo number_format($tot_crdt,2);?></div></td>
												     
												     <!--<td><div align="right"><? echo number_format($tot_openbill,2);?></div></td>-->
												     <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"])) && !empty($_REQUEST["jbid"])) { ?>
													 <td>&nbsp;</td>
													 <td>&nbsp;</td>
													 <td>&nbsp;</td>
													 <? } ?>
												     <td><div align="right"><? echo number_format($fee_tot,2);?></div></td>
												     <td>&nbsp;</td>
												     <td><div align="right"><? echo number_format($tottech_prft,2);?></div></td>
													 <? if($shows!="" || empty($comp_login) && !empty($officeids["office_id"])){ ?>
												     <td><div align="right"><? echo number_format($totcmp_prft,2);?></div></td>
												     <? } ?>
													 <td><div align="right"><? //echo number_format($tot_amts,2);?>
													 </div></td>
												     <td><div align="right">
												       <?php 
												       if($tot_bal < 0){ echo "<font color='red'>".number_format($tot_bal,2)."</font>";}else { echo "<font color='green'>".number_format($tot_bal,2)."</font>"; } 
												       $hol_bal +=$tot_bal;
												       ?>
												       </div></td>
												       
													   <?php if(($last_repid["Report_id"] == $rep_id) && (empty($comp_login) && !empty($officeids["office_id"]))) { ?>
												     <td>&nbsp;</td>
													 <? } ?>
												     </tr>
												   <tr><td colspan="21" class="message">&nbsp;</td></tr>
												   </table>												   </td>
												 </tr>
												 <tr>
													 <td colspan="11" class="message">&nbsp;
													 <?php 
													 if($tot_bal > 0) { 
													 echo $off_report_name."Office has to pay to the $compname[company_name]  :<font color='green'> $".number_format(abs($tot_bal),2)."</font> in ".$field; 
													 } else { 
													 echo " $compname[company_name] has to pay to the $off_report_name office  :<font color='red'> $".number_format(abs($tot_bal),2)."</font> in".$field;  
													 } ?>
	                                                 </td>
                                                 </tr>
                                                 
											   		<?
											   			$data->balance->{$field}->total 			= $tot_amts;
											   			$data->balance->{$field}->balance 			= $tot_bal;
											   			$data->balance->{$field}->tech_parts 		= $tot_techprt;
											   			$data->balance->{$field}->company_parts 	= $tot_cmprts;
											   			$data->balance->{$field}->tech_profits 		= $tottech_prft;
											   			$data->balance->{$field}->company_profits 	= $totcmp_prft;
											   			$data->balance->{$field}->fees 				= $fee_tot;
											   			$data->balance->{$field}->open_bills 		= $tot_openbill;
											   			$cnt++;
	 												}
	 												?>
	 											<tr>
												   <td colspan="5"class="title" style="background:#5C5C5C;color:#FFF;height:35px;">Total balance for <?php echo $tech_names; ?>&nbsp;</td>
											     </tr>
											     <tr>
                                                 <td colspan="5"  align="center" class="input">
                                                 	<table class="tablbrd"  border="0" cellspacing="2" cellpadding="2"  align="center">
												   <tr class="message">
												     <td>Jobs Paid With </td>
												     <td>Gross Income </td>
												     <td>Tech Parts </td>
												     <td>Comp Parts </td>
												     <td>Cash</td>
												     <td>Check </td>
												     <td>Credit</td>
												     <!--<td>Open Bills </td>-->
												     <td>Total Fees</td>
												     <td>Tech Profits</td>
													 <?php if($shows!="" || empty($comp_login) && !empty($officeids["office_id"])){ ?>
													 <td>Comp Profit</td>
													 <? } ?>
													 <td>Balance</td>
													 <td>Net Balance</td>
												     </tr>
												     <?
												     foreach ($data->balance as $key => $val)
												     {
												     	//var_dump($val);die;
													     ?>
	                                                 	<tr class="input">
													     <td><?=$key?></td>
													     <td><div align="right"><? echo number_format($val->total,2);?></div></td>
													     <td><div align="right"><? echo number_format($val->tech_parts,2);?></div></td>
													     <td><div align="right"><? echo number_format($val->company_parts,2);?></div></td>
													     <td><div align="right"><? echo ($key == "Cash")? number_format($val->total,2) : '';?></div></td>
													     <td><div align="right"><? echo ($key == "Checks")? number_format($val->total,2):'';?></div></td>
													     <td><div align="right"><? echo ($key == "Credit")? number_format($val->total,2):'';?></div></td>
													     <!--
													     <td><div align="right"><? echo number_format($val->open_bills,2);?></div></td>
													     -->
													     <td><div align="right"><? echo number_format($val->fees,2);?></div></td>
													     
													     <td><div align="right"><? echo number_format($val->tech_profits,2);?></div></td>
														 <? if($shows!="" || empty($comp_login) && !empty($officeids["office_id"])){ ?>
													     <td><div align="right"><? echo number_format($val->company_profits,2);?></div></td>
													     <? } ?>
														 <td><div align="right"><? //echo number_format($tot_amts,2);?>
														 </div></td>
													     <td><div align="right">
													     <?
													     //var_dump($hol_bal);die;
													     ?>
													       <?php
													       if($val->balance < 0)
													       {
													       		echo "<font color='red'>".number_format($val->balance,2)."</font>";
													       }
													       else
													       {
													       		echo "<font color='green'>".number_format($val->balance,2)."</font>";
													       }
													       //$hol_bal +=$val->balance;
													       ?>	
													       </div>
													       </td>
													     </tr>
													   <?
												     }
												   ?>
												     <tr><td colspan="19" class="message">&nbsp;</td></tr>
												   
												   </table>
												   </td>
												 </tr>
                                                 </td>
                                                 </tr>
	 												<?
												 }
												 }
											   
												 }
											   //previous payment for old pending payment
											   /*$comp_id=$_REQUEST["comp_name"];
		$rep_max_id_c = select_single_record("company_report_main","max(Report_id) as maxid"," Company_id='$comp_id'");
									   
		$mx_rpt_id = $rep_max_id_c['0'];
									   
		$tot_get_id_c=select_single_record("pending_payment_det","max(id) as max_id"," comp_id='$comp_id' and rep_id='$mx_rpt_id'");
		$tot_get_maxvalue =select_single_record("pending_payment_det","*"," id='$tot_get_id_c[max_id]'");
		// print_r($tot_get_id_c);
		echo $aaa = $tot_get_maxvalue['balance'];
									   
		if($tot_get_id_c['max_id']=="")
		{
		//if pending paymenting payment empty
		$tot_prvbal= $aaa;
		}*/
		
		 $hol_bal=$hol_bal-$tot_prvbal;
											   
											   ?>
											   <input type="hidden" name="conts1" id="conts1" value="<?php echo $acnt;?>">
												 <?php if($_REQUEST["comp_name"] <> "" && $_REQUEST["rep_name"] <> "") {?>
												 <tr>
												   <td colspan="5"   class="message"><?php if($_REQUEST["comp_name"] <> "") {if($hol_bal < 0) { echo " $compname[company_name] has to pay to the $off_report_name office <font color='red'>$".number_format(abs($hol_bal),2)."</font>";}else { echo $off_report_name." Office has to pay to the $compname[company_name]    <font color='green'>$".number_format(abs($hol_bal),2)."</font>";} }?>&nbsp;</td>
												 </tr>
												 <?php } if($_REQUEST["comp_name"] <> "" && $_REQUEST["rep_name"] <> "") {?>
												 <tr>
												   <td colspan="2"  align="center" class="message"><div align="right">Report Notes1</div></td>
												   <td colspan="3"  align="center" class="message"><div align="left"><?php echo $rnotes1; ?></div></td>
											     </tr>
												 <?php if(empty($comp_login) && !empty($officeids["office_id"])) { ?>
												 <tr>
												   <td colspan="2"  align="center" class="message"><div align="right">Report Notes2</div></td>
												   <td colspan="3"  align="center" class="message"><div align="left"><?php echo $rnotes2; ?></div></td>
											     </tr>
												 <? } else if(!empty($comp_login) && empty($officeids["office_id"])){ ?>
												 <tr><td colspan="5" class="message_note"><font color="green">Green - indicates office has to pay the amount to <? echo "$compname[company_name]"; ?></font> <br><font color="red">Red indicates <? echo "$compname[company_name]"; ?> has to pay the amount to office</font></td></tr>
												 <? } ?>
												 <tr>
												   <td colspan="5"  align="center" class="message">								  
												   <?php  if(empty($comp_login) && !empty($officeids["office_id"])) { ?>
												   <a onClick="javascript:getval();" style="cursor:pointer ">Re Send Mail</a>&nbsp; |
												   <a onClick="javascript:getval_spdfonly();" style="cursor:pointer ">Send PDF Only</a>&nbsp; |
                                                   <a onClick="javascript:getval_sendwithpdf();" style="cursor:pointer ">Send with PDF</a>&nbsp;|
												   <a onClick="javascript:getval_cpdf();" style="cursor:pointer ">View PDF</a>&nbsp;
												   <? } else {?>
												   <a onClick="javascript:getval_cpdf();" style="cursor:pointer ">View PDF</a>&nbsp;
												   <? } ?>
												   </td>
												 </tr>
												 <? } else if($_REQUEST["comp_name"] == "" || $_REQUEST["rep_name"] == ""){?>
												 <tr>
												   <td colspan="5"  align="center" class="message">Please Select The Company Name And Report Title&nbsp;</td>
												 </tr>
												 <? } ?>
												 <tr>
												   <td colspan="5"  align="center" class="input">&nbsp;</td>
												 </tr>

											<? //$cnt++; } ?>
											<input type="hidden" name="conts1" id="conts1" value="<?php echo $cont;?>">
											<?
										//}else { if($_REQUEST["comp_name"] <> "") {?>
										 <? //} } ?>
				 <input type="hidden" name="tconts" id="tconts" value="<?php echo $cnt;?>">
	  </table>
	  </td>
    </tr>
</table>
</form>
</body>
</html>
<script language="javascript">
$(document).ready(function(){
	var close_time = $('#Job_Close_Time');
	var payment_collector = $("#job_frm input[name='collector']");
	var payment_collector_tech = $("input[name='collector'][value=technician]");
	var payment_collector_company = $("input[name='collector'][value=company]");
	payment_collector_tech.removeAttr('checked');
	payment_collector_company.removeAttr('checked');
	close_time.attr('disabled','disabled');
	$('#vamount').change(function(){
		var selected = $('#Job_Payment_Method');
		var amount = $('#vamount');
		var input_cash = $('#vcash');
		var input_check = $('#vChecks');
		var input_credit = $('#vCredit');
		if(selected.val() == "Cash")
		{
			input_check.val('0.00');
			input_credit.val('0.00');
			input_cash.val(amount.val());
		}
		else if(selected.val() == "Credit")
		{
			input_check.val('0.00');
			input_credit.val(amount.val());
			input_cash.val('0.00');
		}
		else if(selected.val() == "Checks")
		{
			input_check.val(amount.val());
			input_credit.val('0.00');
			input_cash.val('0.00');
		}
	});
	$('#Job_Payment_Method').change(function(){
		
		var amount = $('#vamount');
		var input_cash = $('#vcash');
		var input_check = $('#vChecks');
		var input_credit = $('#vCredit');
		
		var method = $(this).val();
		if(method == "Cash")
		{
			input_check.val('0.00');
			input_credit.val('0.00');
			input_cash.val(amount.val());
			close_time.val('Day');
			payment_collector_company.removeAttr('checked');
			payment_collector_company.attr('disabled',true);
			payment_collector_tech.attr('checked','checked');
		}
		else if(method == "Checks")
		{
			input_check.val(amount.val());
			input_credit.val('0.00');
			input_cash.val('0.00');
			close_time.val('Night');
			payment_collector_tech.removeAttr('checked');
			payment_collector_company.removeAttr('disabled');
			payment_collector_company.attr('checked','checked');
		}
		else if(method == "Credit")
		{
			input_check.val('0.00');
			input_credit.val(amount.val());
			input_cash.val('0.00');
			close_time.val('Night');
			payment_collector_tech.removeAttr('checked');
			payment_collector_company.removeAttr('disabled');
			payment_collector_company.attr('checked','checked');
		}
	}).change();
	
});
function getrep()
{
 var cmp = document.getElementById("comp_name").value;
 window.location = 'homes.php?comd=view_comp_report&comp_name='+cmp+'&posteds=1';
}
function gettech()
{
  var cmp = document.getElementById("comp_name").value;
  var rep = document.getElementById("rep_name").value;

  window.location = 'homes.php?comd=view_comp_report&rep_name='+rep+'&comp_name='+cmp+'&actions=1&posteds=1';
}
function getval()
{
  var cmp = document.getElementById("comp_name").value;
  var rep_name = document.getElementById("rep_name").value;
  var conts = document.getElementById("conts1").value;
  var tconts = document.getElementById("tconts").value;

	  var tcid = "";
	  var jb_ids= "";
	 for(var j=0;j<tconts;j++)
	 {   
	 	var tech_id = document.getElementById("tch_id"+j+"").value;
		tcid += tech_id;
		tcid += "@";
	 }
	for(var i=0;i<conts;i++)
	  { 
			 var jb_id = document.getElementById("jb_id"+i+"").value;
			 jb_ids += jb_id;
			 jb_ids += "@";
	  }
		if(tcid != "" && jb_ids != "")
		{
			window.location = 'homes.php?comd=view_comp_report&posteds=1&actions=1&mail=1&comp_name='+cmp+'&rep_name='+rep_name+'&tech_id='+tcid+'&jb_ids='+jb_ids;
		}
		 // alert(tcid+" : "+jb_ids);return false;
}
function getval()
{
  var cmp = document.getElementById("comp_name").value;
  var rep_name = document.getElementById("rep_name").value;
  var conts = document.getElementById("conts1").value;
  var tconts = document.getElementById("tconts").value;

	  var tcid = "";
	  var jb_ids= "";
	 for(var j=0;j<tconts;j++)
	 {   
	 	var tech_id = document.getElementById("tch_id"+j+"").value;
		tcid += tech_id;
		tcid += "@";
	 }
	for(var i=0;i<conts;i++)
	  { 
			 var jb_id = document.getElementById("jb_id"+i+"").value;
			 jb_ids += jb_id;
			 jb_ids += "@";
	  }
		if(tcid != "" && jb_ids != "")
		{
			window.location = 'homes.php?comd=view_comp_report&posteds=1&actions=1&mail=1&comp_name='+cmp+'&rep_name='+rep_name+'&tech_id='+tcid+'&jb_ids='+jb_ids;
		}
		 // alert(tcid+" : "+jb_ids);return false;
}
function getval_cpdf()
{
  var cmp = document.getElementById("comp_name").value;
  var rep_name = document.getElementById("rep_name").value;
  var conts = document.getElementById("conts1").value;
  var tconts = document.getElementById("tconts").value;

	  var tcid = "";
	  var jb_ids= "";
	 for(var j=0;j<tconts;j++)
	 {   
	 	var tech_id = document.getElementById("tch_id"+j+"").value;
		tcid += tech_id;
		tcid += "@";
	 }
	for(var i=0;i<conts;i++)
	  { 
			 var jb_id = document.getElementById("jb_id"+i+"").value;
			 jb_ids += jb_id;
			 jb_ids += "@";
	  }
		if(tcid != "" && jb_ids != "")
		{
			var test="<? echo $officeids["office_id"]?>";
			if(test=="")
			{
				 oid="test";
			}
			else
			{
				 oid="<? echo $officeids["office_id"]?>";
			}
			//return false;
			//window.open = 'homes.php?comd=create_pdf_comp_report&posteds=1&actions=1&mail=1&comp_name='+cmp+'&rep_name='+rep_name+'&tech_id='+tcid+'&jb_ids='+jb_ids;
			//alert("view_company_pdf_report.php?rep_name="+rep_name+"&comp_login="+<?//$comp_login;?>+"&oid="+<//$officeids["office_id"];?>);
			//return false;
			window.open ("view_company_pdf_report.php?rep_name="+rep_name+"&comp_login="+<?=$comp_login;?>+"&oid="+oid,"mywindow","menubar=1,resizable=1,width=650,height=750");
		}
		 // alert(tcid+" : "+jb_ids);return false;
}
function getval_spdfonly()
{
  var cmp = document.getElementById("comp_name").value;
  var rep_name = document.getElementById("rep_name").value;
  var conts = document.getElementById("conts1").value;
  var tconts = document.getElementById("tconts").value;

	  var tcid = "";
	  var jb_ids= "";
	 for(var j=0;j<tconts;j++)
	 {   
	 	var tech_id = document.getElementById("tch_id"+j+"").value;
		tcid += tech_id;
		tcid += "@";
	 }
	for(var i=0;i<conts;i++)
	  { 
			 var jb_id = document.getElementById("jb_id"+i+"").value;
			 jb_ids += jb_id;
			 jb_ids += "@";
	  }
		if(tcid != "" && jb_ids != "")
		{
			window.location = 'homes.php?comd=view_comp_report&posteds=1&actions=1&sendpdfonly=1&comp_name='+cmp+'&rep_name='+rep_name+'&tech_id='+tcid+'&jb_ids='+jb_ids;
		}
		 // alert(tcid+" : "+jb_ids);return false;
}
function getval_sendwithpdf()
{
  var cmp = document.getElementById("comp_name").value;
  var rep_name = document.getElementById("rep_name").value;
  var conts = document.getElementById("conts1").value;
  var tconts = document.getElementById("tconts").value;

	  var tcid = "";
	  var jb_ids= "";
	 for(var j=0;j<tconts;j++)
	 {   
	 	var tech_id = document.getElementById("tch_id"+j+"").value;
		tcid += tech_id;
		tcid += "@";
	 }
	for(var i=0;i<conts;i++)
	  { 
			 var jb_id = document.getElementById("jb_id"+i+"").value;
			 jb_ids += jb_id;
			 jb_ids += "@";
	  }
		if(tcid != "" && jb_ids != "")
		{
			window.location = 'homes.php?comd=view_comp_report&posteds=1&actions=1&sendwithpdf=1&comp_name='+cmp+'&rep_name='+rep_name+'&tech_id='+tcid+'&jb_ids='+jb_ids;
		}
}

function delfun(jid,cmpid,repid)
{
	var val = confirm("You want to delete the job");
	if(val)
	{
		//alert(jid+" : "+cmpid); return false;
		document.tech_frm.method="post";
		document.tech_frm.action="delete_reportedjod.php?comp_name="+cmpid+"&jbids="+jid+"&repid="+repid;
		document.tech_frm.submit();
		//window.location = "homes.php?comd=deljobs&comp_name="+cmpid+"&jbids="+jid;
	}
}
function editdata(jid,cid,rid)
{
	document.tech_frm.method="post";
	document.tech_frm.action='homes.php?comd=view_comp_report&actions=1&posteds=1&comp_name='+cid+'&rep_name='+rid+'&jbid='+jid;
	document.tech_frm.submit();
}

function savedata(jid,cid,rid)
{
	var input_cash = $('#vcash');
	var input_check = $('#vChecks');
	var input_credit = $('#vCredit');
	var close_time = $('#Job_Close_Time');
	input_cash.removeAttr('disabled');
	input_check.removeAttr('disabled');
	input_credit.removeAttr('disabled');
	close_time.removeAttr('disabled');
	
	var vcash = document.tech_frm.vcash.value;
	var vChecks = document.tech_frm.vChecks.value;
	var vCredit = document.tech_frm.vCredit.value;
	//var vopen_bill = document.tech_frm.vopen_bill.value;
	var vopen_bill = "0.00";
	var vamount = document.tech_frm.vamount.value;
	var htotal = eval(vcash)+eval(vChecks)+eval(vCredit)+eval(vopen_bill);
	
	if(htotal <= vamount)
	{
		document.tech_frm.method="post";
		document.tech_frm.action='homes.php?comd=view_comp_report&actions=1&posteds=1&save=1&comp_name='+cid+'&rep_name='+rid+'&jbsid='+jid;
		document.tech_frm.submit();
	}
	else
	{
		alert("Total Amount does not match to the sum of Cash/Check/Credit/Open Bill");
		return false;
	}
}

function numformat()
{
	var vcash = document.tech_frm.vcash.value;
	var vChecks = document.tech_frm.vChecks.value;
	var vCredit = document.tech_frm.vCredit.value;
	var vopen_bill = document.tech_frm.vopen_bill.value;
	var vamount = document.tech_frm.vamount.value;
	var vTech_Part = document.tech_frm.vTech_Part.value;
	var vComp_Part = document.tech_frm.vComp_Part.value;

	
	document.getElementById("vamount").value = new NumberFormat(vamount).toFormatted();
	document.getElementById("vTech_Part").value = new NumberFormat(vTech_Part).toFormatted();
	document.getElementById("vComp_Part").value = new NumberFormat(vComp_Part).toFormatted();
	document.getElementById("vcash").value = new NumberFormat(vcash).toFormatted();
	document.getElementById("vChecks").value = new NumberFormat(vChecks).toFormatted();
	document.getElementById("vCredit").value = new NumberFormat(vCredit).toFormatted();
	document.getElementById("vopen_bill").value = new NumberFormat(vopen_bill).toFormatted();
}

</script>
