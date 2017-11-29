<?php 

class BannerController extends BaseController
{
    /**
     * 横幅列表
     * @return multitype:unknown
     */
	public function getBannerlist()
	{
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	   
	    $bannerList = Banner::orderBy('Sort','desc')->take($limit)->get();
	    
        return array('bannerList'=>$bannerList);
	}


   
}