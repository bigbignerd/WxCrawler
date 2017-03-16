# WxCrawler
使用PHP简单的爬取微信文章内容

可以获取的信息包括：文章html富文本，无图html文本，以及文章的基本信息：标题、作者、封面图片、公众号等信息。

使用方式：

$crawler = new WxCrawler();
$content = $crawler->crawByUrl($url);

content数组结构为：
```
[
    [
        'date' => '',//发布日期
        'title'=> '',//标题
        'digest'=> '',//描述
        'content_url'=> '',//文章链接
        'cover'=> '',//文章封面链接
        'wechatname'=> '',//微信公众号名称
    ],
    'content_html' => '',
    'content_text' => '',
]
```
