<?php 

class BindCardController extends BaseController
{
    /**
     * 信用卡列表
     * @return multitype:unknown
     */
	public function getBindcardlist()
	{
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	   
	    $bindCards = BindCard::skip($offset)->take($limit)->get();
	    
        return array('bindCards'=>$bindCards);
	}


   
}