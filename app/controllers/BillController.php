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
	   
	    $billList = Bill::where('UserId',$this->user['UserId'])->skip($offset)->take($limit)->get();
	    
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