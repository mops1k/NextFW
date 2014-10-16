<?php
namespace NextFW\Controller;

use NextFW\Engine as Engine;

class Parser extends Engine\Controller
{
    /* @var NextFW\Module\Parser */
    public $mod;

    use Engine\TSingleton;
    function start()
    {
        $this->tpl->setBlock("breadcrumb",'<a href="/parser">Freelance.ru parser</a>');

        $pQ = new Engine\pQuery();
        $pages = 5;
        $host = "https://freelance.ru";
        $main_url = "https://freelance.ru/projects/";
        $url = "https://freelance.ru/projects/?spec=4";
        $only_pub = true;

        $dom = $pQ->file_get_html($url);
        $pagination = $dom->find("ul.pagination li a");

        $array = [];

        for($i = 0; $i <= $pages; $i++) {
            $array[] = $main_url.str_replace(".","",$pagination[$i]->href);
        }

        $dom = null;
        $info_array = [];

        foreach($array as $url)
        {
            $array = "";
            $str = file_get_contents($url);
            $str = iconv('cp1251','utf-8',$str);
            $dom = $pQ->str_get_html($str);
            $proj_list = $dom->find("div.projects .proj");
            foreach($proj_list as $value)
            {
                $type = str_replace("proj ","",$value->class);
                if(($type == "public" AND $only_pub === true) OR ($only_pub === false)){ } else continue;

                $array[] = [
                    "id" => str_replace(" ","",str_replace("/","",str_replace("projects","",$value->find("a.descr")[0]->href))),
                    "title" => $value->find("a.ptitle span")[0]->plaintext,
                    "avatr" => $host.$value->find("a.avatr img")[0]->src,
                    "cost" => $value->find("span.cost")[0]->plaintext,
                    "href" => $host.$value->find("a.descr")[0]->href,
                    "date" => $value->find("ul li.pdata")[0]->title,
                    "replies" => $value->find("ul li.messages a i")[0]->plaintext,
                    "descr" => $value->find("a.descr")[0]->plaintext,
                    "type" => $type
                ];
            }
            $dom = null;
            $info_array = array_merge($info_array,$array);
        }

        function isort(&$a, $field, $dir = true) {
            $t = call_user_func_array('array_merge_recursive', $a);
            asort($t[$field]);
            $so = array_keys($t[$field]);
            asort($so);            # исправлено 2012-08-31
            $so = array_keys($so);

            $a = array_combine($so, $a);
            $dir ? ksort($a) : krsort($a);
        }

        isort($info_array,'date',false);

        $content = "";
        foreach($info_array as $val)
        {
            $this->tpl->setAll([
                'avatr'=>$val['avatr'],
                'title'=>$val['title'],
                'cost'=>$val['cost'],
                'href'=>$val['href'],
                'date'=>$val['date'],
                'reply'=>$val['replies'],
                'descr'=>$val['descr']
            ]);
            $content .= $this->tpl->subLoad('parser.tpl');
        }
        $this->tpl->setBlock('content',$content);
    }
}