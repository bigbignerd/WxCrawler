<?php

/**
* 微信公众号文章爬取类
* 使用方法：
* $crawler = new WxCrawler();
* $content = $crawler->crawByUrl($url);
*/
class WxCrawler
{
	/** @var 代理  */
	protected $agent = [
		"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AcooBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
        "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
        "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
        "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
        "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
        "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
        "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
        "Mozilla/5.0 (X11; U; Linux; en-US) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.6",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
        "Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5",
        "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
        "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
        "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
	];
	public $host = '';
	public $header = '';
	public $referer = '';
	public $antiLeech = '';

	public function __construct($host='', $referer='', $proxy=false)
	{
		/** @var 初始化curl信息  */
		$this->header  = $this->agent[rand(0,count($this->agent) - 1)];
		$this->referer = empty($referer)?'http://weixin.sogou.com/' : $referer;
		$this->host    = empty($host)?'weixin.sogou.com' : $host;
		/** @var 处理微信图片的防盗链 */
		$this->antiLeech = 'http://'.$_SERVER['SERVER_NAME'].'/tool.php?url=';
	}
	/**
	 * 爬取内容
	 * @author bignerd
	 * @since  2016-08-16T10:13:58+0800
	 * @param  $url
	 */
	public function _get($url)
	{
		$ch=curl_init($url);
		$options = [
			CURLOPT_USERAGENT => $this->agent,
			CURLOPT_REFERER => $this->referer,
		];
		curl_setopt_array($ch,$options);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,60);

        $output=curl_exec($ch);

        return $output;
	}
	public function crawByUrl($url)
	{
		$content = $this->_get($url);
		$basicInfo = $this->articleBasicInfo($content);
		list($content_html, $content_text) = $this->contentHandle($content);

		return array_merge($basicInfo,['content_html' => $content_html,'content_text' => $content_text]);
	}
	/**
	 * 处理微信文章源码，提取文章主体，处理图片链接
	 * @author bignerd
	 * @since  2016-08-16T15:59:27+0800
	 * @param  $content 抓取的微信文章源码
	 * @return [带图html文本，无图html文本]
	 */
	public function contentHandle($content)
	{
        $content_html_pattern = '/<div class="rich_media_content " id="js_content">(.*?)<\/div>/s';
        preg_match_all($content_html_pattern, $content, $html_matchs);
        $content_html = $html_matchs[0][0];
        /** @var  带图片html文本 */
        $content_html = preg_replace_callback('/data-src="(.*?)"/', function($matches){
        	return 'src='.$this->antiLeech.urlencode($matches[1]);
        }, $content_html);
        /** @var  无图html文本 */
        $content_text = preg_replace('/<img.*?>/s','',$content_html);

        return [$content_html,$content_text];
	}
	/**
	 * 获取文章的基本信息
	 * @author bignerd
	 * @since  2016-08-16T17:16:32+0800
	 * @param  $content 文章详情源码
	 * @return $basicInfo
	 */
	public function articleBasicInfo($content)
	{	
		//待获取item                
		$item = [
					'ct' => 'date',//发布时间
					'msg_title' => 'title',//标题
					'msg_desc' => 'digest',//描述
					'msg_link' => 'content_url',//文章链接
					'msg_cdn_url' => 'cover',//封面图片链接
					'nickname' => 'wechatname',//公众号名称
				];
		$basicInfo = [
			'author' => '',
			'copyright_stat' => '',
		];
		foreach ($item as $k => $v) {
			$pattern = '/ var '.$k.' = "(.*?)";/s';
			preg_match_all($pattern,$content,$matches);
			if(array_key_exists(1, $matches) && !empty($matches[1][0])){
				$basicInfo[$v] = $this->htmlTransform($matches[1][0]);
			}else{
				$basicInfo[$v] = '';
			}
		}
		/** 获取作者 */
		preg_match('/<em class="rich_media_meta rich_media_meta_text">(.*?)<\/em>/s', $content, $matchAuthor);
		if(!empty($matchAuthor[1])) $basicInfo['author'] = $matchAuthor[1];
		/** 文章类型 */
		preg_match('/<span id="copyright_logo" class="rich_media_meta meta_original_tag">(.*?)<\/span>/s', $content, $matchType);
		if(!empty($matchType[1])) $basicInfo['copyright_stat'] = $matchType[1];

		return $basicInfo;
	}
	/**
	 * 特殊字符转换
	 * @author bignerd
	 * @since  2016-08-16T17:30:52+0800
	 * @param  $string
	 * @return $string
	 */
	public function htmlTransform($string)
	{
		$string = str_replace('&quot;','"',$string);
        $string = str_replace('&amp;','&',$string);
        $string = str_replace('amp;','',$string);
        $string = str_replace('&lt;','<',$string);
        $string = str_replace('&gt;','>',$string);
        $string = str_replace('&nbsp;',' ',$string);
        $string = str_replace("\\", '',$string);
        return $string;
	}
}

?>
