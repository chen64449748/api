<?php 

class NewsController extends BaseController
{
    /**
     * 新闻列表
     * @return multitype:unknown
     */
	public function getNews()
	{
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $news = News::where('IsDisplay','1')->skip($offset)->take($limit)->get();
	    
        return array('news'=>$news);
	}
	
	/**
	 * 新闻详情
	 */
	public function getNewsdetail(){
	    $newsId = $this->data['newsId'];
	    $newsDetail = News::where('Id',$newsId)->get();
	    return $newsDetail;
	}

   
}