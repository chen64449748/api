<?php 

class BillController extends BaseController
{
    /**
     * 账单列表
     * @return multitype:unknown
     */
	public function getBilllist()
	{
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $startTime = $this->data['startTime'];
	    $endTime = $this->data['endTime'];
	    $status = $this->data['status'];
	    $type = $this->data['type'];
	   
	    $billList = Bill::select()->where('UserId',$this->user['UserId']);
	    //起始时间
	    if($startTime != ''){
	        $billList = $billList->where('created_at','>',$startTime);
	    }
	    //结束时间
	    if($endTime != ''){
	        $billList = $billList->where('created_at','<',$endTime);
	    }
	    //状态
	    if($status != ''){
	        $billList = $billList->where('status',$status);
	    }
	    //类型
	    if($type != ''){
	        $billList = $billList->where('Type',$type);
	    }
	    $billList = $billList->skip($offset)->take($limit)->get();
	    
 	    if(!$billList->isEmpty()){
	        foreach ($billList as $key => &$val){
	            $val->creditNumber = '';
	            $val->BankNumber = '';
	            //获取信用卡号
	            if($val->CreditId != ''){
	                $val->creditNumber = BankdCard::where('Id',$val['CreditId'])->where('Type',2)->pluck('CreditNumber');
	            }
	            //获取银行卡号
	            if($val->BankId != ''){
	                $val->BankNumber = BankdCard::where('Id',$val['BankId'])->where('Type',1)->pluck('CreditNumber');
	            }
	        }
 	    }
        return array('billList'=>$billList);
	}
	
	/**
	 * 账单详情页
	 * @return multitype:unknown
	 */
	public function getBilldetail()
	{
	    $billDetail = BillDetail::select('xyk_billdetails.*','CreditNmuber')
	                               ->leftJoin('xyk_userbinddcard','xyk_billdetails.CreditId','=','xyk_userbinddcard.CreditId')
	                               ->where('BillId',$this->data['billId'])
	                               ->get();
	     
	    return $billDetail;
	}


   
}